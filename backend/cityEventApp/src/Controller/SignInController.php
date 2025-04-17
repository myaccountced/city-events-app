<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Security\Core\Security;
use DateTime;
use DateInterval;
use Firebase\JWT\JWT;

/**
 * Backend handler for sign in page
 */
class SignInController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private string $privateKey;
    private string $publicKey;
    private JWTTokenManagerInterface $jwtManager;
    private $jwtEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher,
                                JWTTokenManagerInterface $jwtManager, JWTEncoderInterface $jwtEncoder)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
        $this->jwtEncoder = $jwtEncoder;
    }

    /**
     * Route for signing in
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/auth/signin", name: "auth_signin", methods: ["POST"])]
    public function signIn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Check if the request contains a token and if it's an array
        if (isset($data['tokens']) && is_array($data['tokens'])) {
            $usernames = [];

            // Process each token individually
            foreach ($data['tokens'] as $token) {
                try {
                    // Decode and verify the JWT token using the public key and RS256
                    $decoded = $this->jwtEncoder->decode($token);

                    // Check the expiration time and return the username if valid
                    if ($decoded['exp'] - $decoded['iat'] > 7200) { // Token is valid if expiry is more than 2 hours
                        $usernames[] = $decoded['username'];
                    } else {
                        $usernames[] = 'not remembered';
                    }
                } catch (\Exception $e) {
                    $usernames[] = 'expired'; // If decoding fails, mark token as expired
                }
            }

            return new JsonResponse(['usernames' => $usernames], Response::HTTP_OK);
        }

        // Check for identifier (username or email) and password
        $identifier = $data['identifier'] ?? null;
        $password = $data['password'] ?? null;
        $rememberMe = $data['rememberMe'] ?? false;

        if (!$identifier || !$password) {
            return new JsonResponse(['error' => 'Identifier (username or email) and password are required'], Response::HTTP_UNAUTHORIZED);
        }

        // Authenticate the user
        $user = $this->entityManager->getRepository(User::class)->findOneByIdentifier($identifier);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return new JsonResponse(['error' => 'Invalid identifier (username or email) or password'], Response::HTTP_UNAUTHORIZED);
        }

        // Set expiration time based on the rememberMe flag
        $expirationTime = $rememberMe ? 31536000 : 7200; // 1 year vs. 2 hours

        $scope = '';
        if ($user->isModerator()) {
            $scope = 'moderator';
        }

        $payload = [
          'exp' => time() + $expirationTime
        ];

        $token = $this->jwtManager->createFromPayload($user, $payload);

        // Return the token to the client
        return new JsonResponse([
            'token' => $token,
            'username' => $user->getUsername(),
            'userId' => $user->getId(),
            'scope' => $scope,
        ], Response::HTTP_OK);
    }


    /**
     * This method verifies if the given authorization string matches that of a moderators.
     * @param string $auth JWT token received on signIn
     * @return bool true if the token belongs to a moderator and the token has yet to expire, otherwise false
     */
    public function verifyModeratorAuthentication(string $auth): bool
    {
        try {
            $this->privateKey = file_get_contents(__DIR__ . '\..\..\private.key');
            $this->publicKey = file_get_contents(__DIR__ . '\..\..\public.key');

            $jwtData = JWT::decode($auth, new Key($this->publicKey, 'RS256'));
        }
        catch (\Exception $e) {
            return false;
        }

        $now = time();

        // JWT has expired
        if ($now > $jwtData->exp) {
            return false;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $jwtData->username]);

        if ($user && $user->isModerator()) {
            return true;
        }

        return false;
    }

    #[Route("/create_token", name: "create_token", methods: ["POST"])]
    public function login(Request $request): JsonResponse
    {
        // Retrieve the data from the request (username and password)
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        // Authenticate the user (you can modify this based on your auth logic)
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user || !password_verify($password, $user->getPassword())) {
            return new JsonResponse(['message' => 'Invalid credentials'], 401);
        }
        // Set the token expiration to one year from now
        $expiration = new DateTime();
        $expiration->add(new DateInterval('P1Y'));  // Add 1 year
        // Generate the JWT token with the custom expiration
        $token = $this->jwtManager->create($user, ['exp' => $expiration->getTimestamp()]);
        // Return the token to the client
        return new JsonResponse(['token' => $token]);
    }
}