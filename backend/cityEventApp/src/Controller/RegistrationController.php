<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Subscription;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class RegistrationController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserRepository $userRepository,EntityManagerInterface $entityManager,
                                ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
    }

    /* POST REQUEST - Handle Post request to create an account */
    #[Route('/api/registration', name:'create_user',methods:['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Extract fields
        $username = $data['username'] ? trim($data['username']) :  '';
        $email = $data['email'] ? trim($data['email']) : '';
        $password = $data['password'] ? trim($data['password']): '';

        // Create new user object
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($password); // plain password for validation
        $user->setRoles(['ROLE_REGISTERED']);
        // Hash the password before storing it
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);


        // Validate the user entity based on the validation annotations
        $errors = $this->validator->validate($user);
        $user->setPassword($hashedPassword); // hashed password for storing in database

        // If there are validation errors, return them in the response
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();  // Set field as key and message as value
            }
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid data',
                'errors' => $errorMessages],
                Response::HTTP_BAD_REQUEST);
        }

        // Check duplicated data
        $duplicate = [];
        // Check if username already exists
        $existingUserByUsername = $this->userRepository->findOneBy(['username' => $data['username'] ?? null]);
        $existingUserByEmail = $this->userRepository->findOneBy(['email' => $data['email'] ?? null]);
        if ($existingUserByUsername) {
            $duplicate['username'] = 'Username already exists';
        }
        if ($existingUserByEmail) {
            $duplicate['email'] = 'Email already exists';
        }
        if ($duplicate) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Duplicated data',
                'errors' => $duplicate],
                JsonResponse::HTTP_CONFLICT);
        }

        // If validation passes, persist and save the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Your account has been created'],
        Response::HTTP_CREATED);
    }




}