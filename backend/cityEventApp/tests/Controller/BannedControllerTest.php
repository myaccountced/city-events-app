<?php

namespace App\Tests\Controller;


use App\Entity\Banned;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\tests\DataFixtures\AppUserFixture;
use App\Tests\tests\DataFixtures\BannedFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use App\Controller\BannedController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BannedControllerTest extends KernelTestCase
{
    protected DatabaseToolCollection $databaseTool;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->controller = $container->get(BannedController::class);

        // Load fixtures
        $this->loadFixtures([new BannedFixtures(),
            new AppUserFixture()]);
    }

    protected static function getKernelClass(): string
    {
        if (!class_exists($class = 'App\Kernel')) {
            throw new \RuntimeException(sprintf('Class "%s" doesn\'t exist or cannot be autoloaded. Check that the KERNEL_CLASS value in phpunit.xml matches the fully-qualified class name of your Kernel or override the "%s::createKernel()" method.', $class, static::class));
        }

        return $class;
    }
    private function loadFixtures(array $fixtureInstances): void
    {
        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($fixtureInstances);
    }

    public function testBanUser(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $bannedRepository = $this->entityManager->getRepository(Banned::class);

        // Find a user to ban
        $user = $userRepository->findOneBy(['username' => 'username1']);
        $this->assertNotNull($user);

        // Get the validator from the container
        $validator = self::getContainer()->get(ValidatorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        // Instantiate the controller with the proper arguments
        $controller = new BannedController($validator, $bannedRepository, $userRepo);

        // Build a request to ban the user
        $request = new Request(
            [], // query parameters
            [], // request (POST) parameters (ignored if content is provided)
            [], // attributes
            [], // cookies
            [], // files
            [], // server
            json_encode([
                'userId' => $user->getId(),
                'reason' => 'Abuse of System'
            ])
        );
        $response = $controller->banUser($request, $this->entityManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(JsonResponse::HTTP_CREATED, $response->getStatusCode());

        $this->entityManager->refresh($user);

        // access the banned relation directly from the user
        $banned = $user->getBanned();
        $this->assertNotNull($banned);
        $this->assertEquals('Abuse of System', $banned->getReason());

        // also check via the banned repository:
        $bannedFromRepo = $bannedRepository->findOneBy(['userId' => $user->getId()]);
        $this->assertNotNull($bannedFromRepo);
        $this->assertEquals('Abuse of System', $bannedFromRepo->getReason());

        // Verify the response includes userId, reason, and bannedDate
        $this->assertArrayHasKey('userId', $data);
        $this->assertArrayHasKey('reason', $data);
        $this->assertArrayHasKey('bannedDate', $data);
        $this->assertEquals($user->getId(), $data['userId']);
        $this->assertEquals('Abuse of System', $data['reason']);
        $this->assertNotNull($data['bannedDate']);
    }

    public function testUnbanUser(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $bannedRepository = $this->entityManager->getRepository(Banned::class);

        $user = $userRepository->findOneBy(['username' => 'username1']);
        $this->assertNotNull($user);

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $controller = new BannedController($validator, $bannedRepository, $userRepo);

        // Build a request to ban the user
        $request = new Request(
            [], // query parameters
            [], // request (POST) parameters (ignored if content is provided)
            [], // attributes
            [], // cookies
            [], // files
            [], // server
            json_encode([
                'userId' => $user->getId(),
                'reason' => 'Abuse of System'
            ])
        );
        $response = $controller->banUser($request, $this->entityManager);
        $this->entityManager->refresh($user);


        // Build a request to unban the user
        $request = new Request(
            [], // query parameters
            [], // request parameters
            [], // attributes
            [], // cookies
            [], // files
            [], // server
            json_encode(['userId' => $user->getId()])
        );

        $response = $controller->unbanUser($request, $this->entityManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());

        // Verify that the banned record has been removed from the database
        $banned = $bannedRepository->findOneBy(['userId' => $user->getId()]);
        $this->assertNull($banned);
    }

    public function testBanNonExistentUser(): void
    {
        $bannedRepository = $this->entityManager->getRepository(Banned::class);

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $controller = new BannedController($validator, $bannedRepository, $userRepo);

        // Build a request with a non-existent user ID using JSON content
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['userId' => 99999,
                'reason' => 'Abuse of System'])
        );

        $response = $controller->banUser($request, $this->entityManager);

        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertEquals('User not found', $data['message']);
    }

    public function testUnbanNonBannedUser(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $bannedRepository = $this->entityManager->getRepository(Banned::class);

        // Retrieve a user that is not banned
        $user = $userRepository->findOneBy(['username' => 'username2']);
        $this->assertNotNull($user);

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $controller = new BannedController($validator, $bannedRepository, $userRepo);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['userId' => $user->getId()])
        );

        $response = $controller->unbanUser($request, $this->entityManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);

        // Expecting a 404 response because the user is not banned
        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('User is not banned', $data['message']);
    }

    public function testBanUserWithInvalidReason(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $bannedRepository = $this->entityManager->getRepository(Banned::class);

        // Find a user to ban
        $user = $userRepository->findOneBy(['username' => 'username1']);
        $this->assertNotNull($user);

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $controller = new BannedController($validator, $bannedRepository, $userRepo);

        // Test with a reason that is too short (empty string)
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode([
                'userId' => $user->getId(),
                'reason' => ''
            ])
        );

        $response = $controller->banUser($request, $this->entityManager);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Reason is required and must be a valid string', $data['message']);

        // Test with a reason that is too long (over 255 characters)
        $longReason = str_repeat('a', 256);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode([
                'userId' => $user->getId(),
                'reason' => $longReason
            ])
        );

        $response = $controller->banUser($request, $this->entityManager);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Reason must be between 1 and 255 characters', $data['message']);
    }
}



