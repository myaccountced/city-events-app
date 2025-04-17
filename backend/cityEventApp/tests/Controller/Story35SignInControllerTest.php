<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class Story35SignInControllerTest extends WebTestCase
{
    protected EntityManagerInterface $entityManager;
    private $client;
    private $jwtManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Load environment variables
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');

        // Create the client and boot the kernel
        $this->client = static::createClient();
        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    // Test successful sign-in with username
    public function testSignInSuccessfulUsingUsername(): void
    {
        $this->client->request('POST', '/auth/signin', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'identifier' => 'username1', // Correct username
            'password' => '@Password1',  // Correct password
        ]));

        $this->assertEquals(JsonResponse::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseContent, 'Response should contain a JWT token');
    }

    // Test failed sign-in with wrong password for the correct username
    public function testSignInFailedWrongPasswordUsingUsername(): void
    {
        $this->client->request('POST', '/auth/signin', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'identifier' => 'username1',  // Correct username
            'password' => 'wrongpassword',// Incorrect password
        ]));

        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Invalid identifier (username or email) or password']),
            $this->client->getResponse()->getContent()
        );
    }

    // Test successful sign-in with email
    public function testSignInSuccessfulUsingEmail(): void
    {
        $this->client->request('POST', '/auth/signin', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'identifier' => 'username1@example.com', // Correct email
            'password' => '@Password1',              // Correct password
        ]));

        $this->assertEquals(JsonResponse::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseContent, 'Response should contain a JWT token');
    }

    // Test failed sign-in with wrong password for the correct email
    public function testSignInFailedWrongPasswordUsingEmail(): void
    {
        $this->client->request('POST', '/auth/signin', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'identifier' => 'username1@example.com',  // Correct email
            'password' => 'wrongpassword',            // Incorrect password
        ]));

        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Invalid identifier (username or email) or password']),
            $this->client->getResponse()->getContent()
        );
    }

    // Test sign-in with an expired token, not remembered token (2 hour expiry), and remembered token (1 year expiry)
    public function testSignInWithExpiredToken2hToken1yToken(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);
        $jwtExpired = $this->createExpiredJwtToken($user);
        $jwt2Hour = $this->createJwtToken2h($user);
        $jwt1Year = $this->createJwtToken1y($user);

        $this->client->request('POST', '/auth/signin', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'tokens' => [$jwtExpired, $jwt2Hour, $jwt1Year],
        ]));

        $this->assertEquals(JsonResponse::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['usernames' => ['expired','not remembered','username1']]),
            $this->client->getResponse()->getContent()
        );
    }

    // Helper to create an expired JWT token
    private function createExpiredJwtToken(User $user): string
    {
        $payload = [
            'iat' => time() - 3600, // Issued 1 hour ago
            'exp' => time() - 600,  // Expired 10 minutes ago
            'username' => $user->getUsername(),
        ];

        return $this->jwtManager->createFromPayload($user, $payload);
    }

    // Helper to create a valid JWT token with 2 hours expiration
    private function createJwtToken2h(User $user): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + 7200, // 2 hours expiration
            'username' => $user->getUsername(),
        ];

        return $this->jwtManager->createFromPayload($user, $payload);
    }

    // Helper to create a valid JWT token with 1 year expiration
    private function createJwtToken1y(User $user): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + 31536000, // 1 year expiration
            'username' => $user->getUsername(),
        ];

        return $this->jwtManager->createFromPayload($user, $payload);
    }
}
