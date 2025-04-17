<?php

// uploads/Controller/RegistrationControllerTest.php

namespace App\Tests\Controller;

use App\Tests\tests\DataFixtures\AppUserFixture;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegistrationControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private $client;

    protected function setUp(): void
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');

        // Create the client and boot the kernel only through static::createClient()
        $this->client = static::createClient();
        //$this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $userFixtures = new AppUserFixture();
        $refRepo = new \Doctrine\Common\DataFixtures\ReferenceRepository($this->entityManager);
        $userFixtures->setReferenceRepository($refRepo);
        $userFixtures->load($this->entityManager);
    }

    protected static function getKernelClass(): string
    {
        if (!class_exists($class = 'App\Kernel')) {
            throw new \RuntimeException(sprintf('Class "%s" doesn\'t exist or cannot be autoloaded. Check that the KERNEL_CLASS value in phpunit.xml matches the fully-qualified class name of your Kernel or override the "%s::createKernel()" method.', $class, static::class));
        }

        return $class;
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }

    public function testSuccessfulRegistrationLowBoundary()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'user5',
            'email' => 'newuser5@example.com',
            'password' => 'password'
        ]));

        $this->assertEquals(JsonResponse::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'success', 'message' => 'Your account has been created']),
            $this->client->getResponse()->getContent()
        );

        // Retrieve the entity manager from the client's container
        //$entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Check if the user is stored in the database
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'username' => 'user5',
            'email' => 'newuser5@example.com'
        ]);

        // Assert that the user exists in the database
        $this->assertNotNull($user, 'User should be stored in the database');
        $this->assertEquals('user5', $user->getUsername());
        $this->assertEquals('newuser5@example.com', $user->getEmail());
    }
    public function testSuccessfulRegistrationLowBoundary2()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'user6',
            'email' => 'newuser6@example.com',
            'password' => 'password'
        ]));

        $this->assertEquals(JsonResponse::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'success', 'message' => 'Your account has been created']),
            $this->client->getResponse()->getContent()
        );

        // Retrieve the entity manager from the client's container
        //$entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Check if the user is stored in the database
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'username' => 'user6',
            'email' => 'newuser6@example.com'
        ]);

        // Assert that the user exists in the database
        $this->assertNotNull($user, 'User should be stored in the database');
        $this->assertEquals('user6', $user->getUsername());
        $this->assertEquals('newuser6@example.com', $user->getEmail());
    }


    public function testSuccessfulRegistrationHighBoundary()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'user2user2user2user2user2',
            'email' => 'newuser2@example.com',
            'password' => 'password20password20'
        ]));

        $this->assertEquals(JsonResponse::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'success', 'message' => 'Your account has been created']),
            $this->client->getResponse()->getContent()
        );

        // Retrieve the entity manager from the client's container
        //$entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Check if the user is stored in the database
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'username' => 'user2user2user2user2user2',
            'email' => 'newuser2@example.com'
        ]);

        // Assert that the user exists in the database
        $this->assertNotNull($user, 'User should be stored in the database');
        $this->assertEquals('user2user2user2user2user2', $user->getUsername());
        $this->assertEquals('newuser2@example.com', $user->getEmail());
    }

    public function testRegistrationWithUsernameMissing()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => '',
            'email' => 'newuser2@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['username' => 'Username is required']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithShortUsername()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'user',
            'email' => 'newuser2@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['username' => 'Username must be at least 5 characters long']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithLongUsername()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'user1user2user3user4user56',
            'email' => 'newuser2@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['username' => 'Username cannot be longer than 25 characters']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithEmailMissing()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'newuser2',
            'email' => '',
            'password' => 'password123'
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['email' => 'Email is required']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithPasswordMissing()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'newuser2',
            'email' => 'newuser2@example.com',
            'password' => ' '
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['password' => 'Password is required']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithInvalidEmail()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'newuser2',
            'email' => 'invalid-email',
            'password' => 'password123'
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['email' => 'In valid email']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithDuplicateUsername()
    {
        //$client = static::createClient();

        // First, create a user with the same username
        /*$user = new User();
        $user->setUsername('existinguser');
        $user->setEmail('existing@example.com');
        $user->setPassword('password123'); // Set a plain password for simplicity

        // Retrieve the entity manager from the client's container
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();*/

        // Using Fixture to stimulate data in database

        // Try to register with the same username
        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'username1',
            'email' => 'newuser3@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(JsonResponse::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Duplicated data',
                'errors' => ['username' => 'Username already exists']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithDuplicateEmail()
    {
        //$client = static::createClient();

        // First, create a user with the same email
        /*$user = new User();
        $user->setUsername('anotheruser');
        $user->setEmail('existing@example.com');
        $user->setPassword('password123');

        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();*/

        // Using Fixture to stimulate data in database

        // Try to register with the same email
        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'newuser4',
            'email' => 'username1@example.com',
            'password' => 'password123'
        ]));

        $this->assertEquals(JsonResponse::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Duplicated data',
                'errors' => ['email' => 'Email already exists']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithShortPassword()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'newuser5',
            'email' => 'newuser5@example.com',
            'password' => 'short'
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['password' => 'Password must be at least 8 characters long']]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testRegistrationWithLongPassword()
    {
        //$client = static::createClient();

        $this->client->request('POST', '/api/registration', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'newuser5',
            'email' => 'newuser5@example.com',
            'password' => 'long1long2long3long45'
        ]));

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'error',
                'message' => 'Invalid data',
                'errors' => ['password' => 'Password cannot be longer than 20 characters']]),
            $this->client->getResponse()->getContent()
        );
    }
}
