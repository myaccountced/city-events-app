<?php
namespace App\Controller;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\GoogleAuthService;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

class AuthController extends AbstractController
{
    private $googleAuthService;
    private JWTTokenManagerInterface $jwtManager;
    public function __construct(GoogleAuthService $googleAuthService,
                        JWTTokenManagerInterface $jwtManager)
    {
        $this->googleAuthService = $googleAuthService;
        $this->jwtManager = $jwtManager;
    }
    /**
     * @Route("/authgoogle-login", name="api_google_login", methods={"POST"})
     * This method is to handle POST request from frontend (auth view) to backend to register/sign-in account
     * if user data is not available, then create a new record for the user;
     * otherwise, let them sign in with token send back
     */
    #[Route('/auth/google-login', name:'auth_google_login',methods:['POST'])]
    public function googleLogin(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['idToken']??null;
        if (!$idToken) {
            return new JsonResponse(['error' => 'missing google id token'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $userData = $this->googleAuthService->getUserData($idToken);
            if ($userData) {
                $googleId = $userData['id']??null;
                $email = $userData['email']??null;
                $username = $userData['name']??$email;
                if (!$googleId || !$email || !$username) {
                    return new JsonResponse(['error' => 'missing required data'], Response::HTTP_BAD_REQUEST);
                }
                // Query user data from data if this user already exist?
                $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                // if the email is not exist in database, we will create a new record
                if (!$existingUser) {
                    // make sure there is no record with the same google Id, otherwise, return BADRequest
                    if ($em->getRepository(User::class)->findOneBy(['googleId' => $googleId]))
                    {
                        return new JsonResponse(['error' => 'conflict database'], Response::HTTP_BAD_REQUEST);
                    }
                    $existingUser = new User();
                    $existingUser->setGoogleId($googleId);
                    $existingUser->setUsername($username);
                    $existingUser->setEmail($email);
                    $existingUser->setPassword("**********");
                    $existingUser->setRoles(["ROLE_REGISTERED"]);
                    $em->persist($existingUser);
                    $em->flush();
                }
                else {// if the user already registered, check if they have linked their account or not?
                    if ($existingUser->getGoogleId() !== null & $existingUser->getGoogleId() !== $googleId) {
                        return new JsonResponse(['error' => 'conflict database'], Response::HTTP_BAD_REQUEST);
                    }
                    $existingUser->setGoogleId($googleId);
                    $em->persist($existingUser);
                    $em->flush();
                }
                if ($existingUser->getGoogleId() === null) {
                    $existingUser->setGoogleId($googleId);
                    $em->persist($existingUser);
                    $em->flush();
                }
                else if ($existingUser->getGoogleId() === $googleId) {
                    $existingUser->setGoogleId($googleId);
                    $em->persist($existingUser);
                    $em->flush();
                }

                // Retrieve back data from database to send back to frontend
                $existingUser = $em->getRepository(User::class)->findOneBy(['googleId' => $googleId]);
                // after having their account in database, let user log in
                // check SignInController to decide what information need to send back to frontend
                $expirationTime = 7200; //2 hours
                $scope = '';
                if ($existingUser->isModerator()) {
                    $scope = 'moderator';
                }
                $payload = [
                    'exp' => time() + $expirationTime
                ];
                $token = $this->jwtManager->createFromPayload($existingUser, $payload);
                // Return the token to the client
                return new JsonResponse([
                    'token' => $token,
                    'username' => $existingUser->getUsername(),
                    'userId' => $existingUser->getId(),
                    'scope' => $scope,
                ], Response::HTTP_OK);
            } else {
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid user data'], 400);
            }
        } catch (\Exception $e) {
            // there is something crashed the database
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/auth/verify-creds', name:'verify_creds',methods:['POST'])]
    public function verifyCredentials(Request $request, EntityManagerInterface $em): JsonResponse
    {

        throw NotImplementedException::notImplemented();
    }

}