<?php

namespace App\Controller;

use App\Entity\Banned;
use App\Entity\User;
use App\Repository\BannedRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use HttpResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BannedController extends AbstractController
{
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    private BannedRepository $bannedRepository;

    public function __construct(ValidatorInterface $validator, BannedRepository $bannedRepository, UserRepository $userRepository)
    {
        $this->validator = $validator;
        $this->bannedRepository = $bannedRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Ban a user.
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/banuser', name: 'ban_user', methods: ['POST'])]
    public function banUser(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Extract data from request
        $userId = $data['userId'] ?? null;
        $reason = $data['reason'] ?? null;

        // Validate inputs
        if (empty($userId) || !is_numeric($userId)) {
            return new JsonResponse(['message' => 'User ID is required and must be a valid integer'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($reason) || !is_string($reason)) {
            return new JsonResponse(['message' => 'Reason is required and must be a valid string'], Response::HTTP_BAD_REQUEST);
        }

        // Validate reason length
        if (strlen($reason) < 1 || strlen($reason) > 255) {
            return new JsonResponse(['message' => 'Reason must be between 1 and 255 characters'], Response::HTTP_BAD_REQUEST);
        }

        // Find the user by ID
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the user is already banned
        $existingBan = $em->getRepository(Banned::class)->findOneBy(['userId' => $userId]);
        if ($existingBan) {
            return new JsonResponse(['message' => 'User is already banned'], Response::HTTP_CONFLICT);
        }

        // Create a new banned record
        $banned = new Banned();
        $banned->setUserId($user);
        $banned->setReason($reason);
        $banned->setDatetime(new \DateTime()); // Set the ban time

        // Validate the banned entity
        $errors = $this->validator->validate($banned);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['message' => 'Invalid data', 'errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Save to database
        try {
            $em->persist($banned);
            $em->flush();

            // Return the banned user details
            return new JsonResponse([
                'userId' => $user->getId(),
                'reason' => $banned->getReason(),
                'bannedDate' => $banned->getDatetime()->format('Y-m-d H:i:s'),
                'message' => 'User successfully banned',
            ], Response::HTTP_CREATED);
        } catch (\Exception) {
            return new JsonResponse(['message' => 'Database error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove a ban for a specific user.
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/unbanuser', name: 'unban_user', methods: ['DELETE'])]
    public function unbanUser(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;

        if (empty($userId) || !is_numeric($userId)) {
            return new JsonResponse(['message' => 'User ID is required and must be a valid integer'], Response::HTTP_BAD_REQUEST);
        }

        // Find the banned record
        $banned = $em->getRepository(Banned::class)->findOneBy(['userId' => $userId]);
        if (!$banned) {
            return new JsonResponse(['message' => 'User is not banned'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Remove the banned record
            $em->remove($banned);
            $em->flush();

            return new JsonResponse(['message' => 'User successfully unbanned'], Response::HTTP_OK);
        } catch (\Exception) {
            return new JsonResponse(['message' => 'Database error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/checkbanned/{userId}', name: 'check_banned', methods: ['GET'])]
    public function checkBanned(int $userId, UserRepository $userRepository): JsonResponse
    {
        // Load the user entity
        $user = $userRepository->findOneBy(['id' => $userId]);

        // If the user does not exist, not found.
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if($user->getBanned() === null){
            return new JsonResponse(['banned' => false], Response::HTTP_OK);
        }else{
            // The banned relation exists, so the user is banned.
            $banned = $user->getBanned();
            return new JsonResponse([
                'banned'     => true,
                'reason'     => $banned->getReason(),
                'bannedDate' => $banned->getDatetime()->format('Y-m-d H:i:s')
            ],Response::HTTP_OK);
        }
    }

}
