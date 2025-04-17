<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception as DoctrineException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class SubscriptionController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private SubscriptionRepository $subscriptionRepository;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository,
                                SubscriptionRepository $subscriptionRepository, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->validator = $validator;
    }

    /**
     * Purpose: to handle POST request from frontend. create new Subscription record of specific user
     * @param Request $request: containing username, subscription plan id [1,2]
     * @return JsonResponse
     */
    #[Route('/api/subscription', name: 'create_subscription', methods: ['POST'])]
    public function createSubscription(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate input
        $username = $data['username'] ?? null;
        $selectedPlan = $data['selectedPlan'] ?? null;

        if (!$username || !$selectedPlan) {
            //var_dump($request);
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);

        }

        // Find the user by username
        $user = $this->userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Retrieve the most recent active subscription
        $recentSubscription = $this->subscriptionRepository->findRecentActiveSubscription($user->getId());

        // Determine the start date for the new subscription
        $startDate = $recentSubscription? $recentSubscription->getExpireDate() : new \DateTimeImmutable();

        // Calculate expireDate based on selectedPlan
        if ($selectedPlan == 1) {
            $expireDate = $startDate->add(new \DateInterval('P30D')); // 30 days
        } elseif ($selectedPlan == 2) {
            $expireDate = $startDate->add(new \DateInterval('P365D')); // 365 days
        } else {
            return new JsonResponse(['error' => 'Invalid plan selected'], Response::HTTP_BAD_REQUEST);
        }

        // Create a new subscription
        $subscription = new Subscription();
        $subscription->setUserId($user->getId());
        $subscription->setStartDate($startDate);
        $subscription->setExpireDate($expireDate);

        // Validate subscription data
        $errors = $this->validator->validate($subscription);
        if (count($errors) > 0) {
            return new JsonResponse(['validation error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $user->addSubscription($subscription);

        // Save the new subscription
        try {
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();
        } catch (DoctrineException $e) {
            return new JsonResponse(['error' => 'Database error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'message' => 'Subscription created successfully',
            'isPremium' => true,
            //'daysRemaining' => $daysRemaining,
            'expireDate' => $expireDate->format('Y-m-d\TH:i:s')
        ], Response::HTTP_CREATED);
    }

    /**
     * Method to handle GET request from frontend.
     * to get Subscription record of specific user
     */
    #[Route('/api/subscription/{username}', name: 'get_subscription', methods: ['GET'])]
    public function getSubscriptionStatus(string $username): JsonResponse
    {
        // Find the user by username
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Fetch subscription details for the user
        $recentSubscription = $this->subscriptionRepository->findRecentActiveSubscription($user->getId());

        if (!$recentSubscription) {
            // User has no subscription
            return new JsonResponse([
                'message' => 'No subscription for this user',
                'isPremium' => false,
                'expireDate' => null,
            ], Response::HTTP_OK);
        }


        return new JsonResponse([
            'message' => 'Subscription found',
            'isPremium' => true,
            'expireDate' => $recentSubscription->getExpireDate()->format('Y-m-d\TH:i:s'),
        ], Response::HTTP_OK);
    }
}
