<?php

namespace App\Controller;

use App\Entity\Banned;
use App\Entity\User;
use App\Enum\NotificationMethods;
use App\Enum\NotificationTimings;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Backend handler for sign in page
 */
class UserController extends AbstractController
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Route for getting users. The request object can contain queries to set the offset,
     *  limit, sort category, sort direction, and search username/email like.
     *
     * @param Request $request request for users
     * @param EntityManagerInterface $em Entity manager
     * @return JsonResponse response containing a JSON array of users, or an empty JSON array
     */
    #[Route("/users", name: "users", methods: ["GET"])]
    public function getUsers(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Checking for queries, or setting as defaults
        $userLike = (string)$request->query->get('like', '');
        $userOffset = (int)$request->query->get('offset', 0);
        $userLimit = (int)$request->query->get('limit', 20);
        $userSort = (string)$request->query->get('sort', 'username');
        $userSortDirection = $request->query->get('reverse') ? 'DESC' : 'ASC';

        // Validate limit and offset
        if ($userLimit <= 0 || $userOffset < 0) {
            return new JsonResponse(['message' => 'Invalid limit or offset'], Response::HTTP_BAD_REQUEST);
        }


        // Finding the users
        $repo = $this->entityManager->getRepository(User::class);
        $results = $repo->findUserLike($userLike, $userSort, $userSortDirection, $userLimit, $userOffset);

        // Prepare the response array
        $returnArray = [];
        foreach ($results as $user) {
            // $subArray = [];
            $oldestSub = null;
            foreach ($user->getSubscriptions() as $sub) {
                // getting the oldest active subscription
                if ($sub->getExpireDate() > new \DateTime()) {
                    if ($oldestSub == null || $oldestSub['expires'] < $sub->getExpireDate()) {
                        $oldestSub = ['expires' => $sub->getExpireDate(), 'starts' => $sub->getStartDate()];
                    }
                }
            }

            // Check if the user is banned
            $banned = $em->getRepository(Banned::class)->findOneBy(['userId' => $user->getId()]);

            // Build user data
            $subArray = $oldestSub;
            $userArray = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'creationDate' => $user->getCreationDate(),
                'mod' => $user->isModerator(),
                'subscriptions' => $subArray,
                'isBanned' => $banned !== null,
                'reason' => $banned ? $banned->getReason() : null,
                'bannedAt' => $banned && $banned->getDatetime() ? $banned->getDatetime()->format('Y-m-d H:i:s') : null
            ];

            $returnArray[] = $userArray;
        }

        return new JsonResponse($returnArray);
    }

    /** This Route is for finding one user
     * Only one param is presesnt - the ID of the user you wish to return
     * @param int $userId
     * @param UserRepository $userRepository
     * @return JsonResponse - a single user object
     */
    #[Route("/user/{userId}", name: "one_user", methods: ["GET"])]
    public function getOneUser(int $userId, UserRepository $userRepository): JsonResponse
    {
        // Load the user entity
        $user = $userRepository->findOneBy(['id' => $userId]);

        // If the user does not exist, not found.
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        // Prepare the response array
        $returnArray = [];
        $oldestSub = null;
        foreach ($user->getSubscriptions() as $sub) {
            // getting the oldest active subscription
            if ($sub->getExpireDate() > new \DateTime()) {
                if ($oldestSub == null || $oldestSub['expires'] < $sub->getExpireDate()) {
                    $oldestSub = ['expires' => $sub->getExpireDate(), 'starts' => $sub->getStartDate()];
                }
            }
        }

        // Build user data
        $subArray = $oldestSub;
        $userArray = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'creationDate' => $user->getCreationDate(),
            'mod' => $user->isModerator(),
            'subscriptions' => $subArray,
        ];

        $returnArray[] = $userArray;

        return new JsonResponse($returnArray);
    }

    /**
     * This method is to handle GET request from frontend to retrieve user's notification setting
     * @param EntityManagerInterface $em - get current user to retrieve data
     * @return JsonResponse - containing information relating notification setting
     */
    #[Route('api/user/notification-preferences', name:'user_notification', methods: ['GET'])]
    public function getPreferences(EntityManagerInterface $em): JsonResponse
    {
        // this method(0 is handled by JWT or backend session, that retrieve data based on
        // the token saved on the current session, if the user do not exist, it means invalid token
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        // Fetch user from database (if needed)
        //$user = $em->getRepository(User::class)->find($user->getId());

        return new JsonResponse([
            'wants_notifications' => $user->getWantsNotifications(),
            'notification_methods' => $user->getNotificationMethods(),
            'notification_time' => $user->getNotificationTimes(),
        ], Response::HTTP_OK);
    }

    /**
     * This method is to update user notification setting
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse - containing updated notification setting
     */
    #[Route('api/user/save-notification-preferences', methods: ['POST'])]
    public function savePreferences(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }
        $data = json_decode($request->getContent(), true);
        $requiredKeys = ['wants_notifications', 'notification_methods', 'notification_time'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                return new JsonResponse(['error' => "Missing required field: $key"], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate notification_methods values
        $notificationMethods = [];
        foreach ($data['notification_methods'] as $method) {
            $enumValue = NotificationMethods::tryFrom($method);
            if (!$enumValue) {
                return new JsonResponse(['error' => "Invalid notification method: $method"], Response::HTTP_BAD_REQUEST);
            }
            $notificationMethods[] = $enumValue;
        }

        // Validate notification_timings values
        $notificationTimings = [];
        foreach ($data['notification_time'] as $timing) {
            $enumValue = NotificationTimings::tryFrom($timing);
            if (!$enumValue) {
                return new JsonResponse(['error' => "Invalid notification time: $timing"], Response::HTTP_BAD_REQUEST);
            }
            $notificationTimings[] = $enumValue;
        }

        $user->setWantsNotifications($data['wants_notifications']);
        $user->setNotificationMethods($notificationMethods);
        $user->setNotificationTimes($notificationTimings);
        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'wants_notifications' => $user->getWantsNotifications(),
            'notification_methods' => $user->getNotificationMethods(),
            'notification_time' => $user->getNotificationTimes(),
        ], Response::HTTP_OK);
    }
}
