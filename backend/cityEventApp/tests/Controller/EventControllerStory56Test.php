<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Entity\EventInteraction;
use App\Entity\User;
use App\Enum\EventInteractionStatus;
use App\Controller\EventController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class EventControllerStory56Test extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private EventController $eventController;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;
    private User $testUser;
    private Event $testEvent;

    protected function setUp(): void
    {
        parent::setUp();
        // Load environment variables
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        // Get the token storage and request stack services
        $this->tokenStorage = self::getContainer()->get('security.token_storage');
        $this->requestStack = self::getContainer()->get('request_stack');

        // Get or create the controller
        $this->eventController = self::getContainer()->get(EventController::class);

        // Fetch or create test user
        $this->testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'username1']);

        // Fetch or create test event
        $this->testEvent = $this->entityManager
            ->getRepository(Event::class)
            ->findOneBy(['eventTitle' => 'Test 1']);

        // Clear any existing interactions
        $existingInteractions = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);

        foreach ($existingInteractions as $interaction) {
            $this->entityManager->remove($interaction);
        }

        $this->entityManager->flush();
    }

    private function loginUser(User $user): void
    {
        // Create a token for the user
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        // Create a request and add it to the stack
        $request = new Request();
        $this->requestStack->push($request);
    }

    public function testGetUserInteractionStatusWhenNone(): void
    {
        // Login the test user
        $this->loginUser($this->testUser);

        // Access the entity manager directly to verify initial state
        $initialInteractions = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);
        $this->assertEmpty($initialInteractions, 'Should have no interactions before test');

        // Call the controller method directly
        $response = $this->eventController->getEventInteractions($this->entityManager,$this->testEvent->getId());

        // Verify expected counts
        $this->assertEquals(0, $response['interestedCount']);
        $this->assertEquals(0, $response['attendingCount']);
        $this->assertEmpty($response['userInteractions']);
    }

    public function testGetUserInteractionStatusWhenInterested(): void
    {
        // Create an interaction with interested status
        $interaction = new EventInteraction();
        $interaction->setEvent($this->testEvent);
        $interaction->setUser($this->testUser);
        $interaction->setStatus(EventInteractionStatus::INTERESTED);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        // Login the test user
        $this->loginUser($this->testUser);

        // Call the controller method directly
        $response = $this->eventController->getEventInteractions($this->entityManager,$this->testEvent->getId());

        // Assert interaction state is correct
        $this->assertEquals(EventInteractionStatus::INTERESTED->value, $response['userInteractions'][0]['status']);

        // Cleanup
        $this->entityManager->remove($interaction);
        $this->entityManager->flush();
    }

    public function testGetUserInteractionStatusWhenAttending(): void
    {
        // Create an interaction with attending status
        $interaction = new EventInteraction();
        $interaction->setEvent($this->testEvent);
        $interaction->setUser($this->testUser);
        $interaction->setStatus(EventInteractionStatus::ATTENDING);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        // Login the test user
        $this->loginUser($this->testUser);

        // Call the controller method directly
        $response = $this->eventController->getEventInteractions($this->entityManager,$this->testEvent->getId());

        // Assert interaction state is correct
        $this->assertEquals(EventInteractionStatus::ATTENDING->value, $response['userInteractions'][0]['status']);

        // Cleanup
        $this->entityManager->remove($interaction);
        $this->entityManager->flush();
    }

    public function testGetInteractionCounts(): void
    {
        // Create several interactions with different users to test counting
        $user1 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'username2']);

        $user2 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'username11']);

        $interaction1 = new EventInteraction();
        $interaction1->setEvent($this->testEvent);
        $interaction1->setUser($user1);
        $interaction1->setStatus(EventInteractionStatus::ATTENDING);
        $this->entityManager->persist($interaction1);

        $interaction2 = new EventInteraction();
        $interaction2->setEvent($this->testEvent);
        $interaction2->setUser($user2);
        $interaction2->setStatus(EventInteractionStatus::INTERESTED);
        $this->entityManager->persist($interaction2);

        $interaction3 = new EventInteraction();
        $interaction3->setEvent($this->testEvent);
        $interaction3->setUser($this->testUser);
        $interaction3->setStatus(EventInteractionStatus::ATTENDING);
        $this->entityManager->persist($interaction3);

        $this->entityManager->flush();

        // Call the controller method directly
        $response = $this->eventController->getEventInteractions($this->entityManager,$this->testEvent->getId());


        // Assert counts are correct (1 interested, 2 attending)
        $this->assertEquals(1, $response['interestedCount']);
        $this->assertEquals(2, $response['attendingCount']);

        // Cleanup
        $this->entityManager->remove($interaction1);
        $this->entityManager->remove($interaction2);
        $this->entityManager->remove($interaction3);
        $this->entityManager->flush();
    }

    public function testToggleInterestFromNoneToInterested(): void
    {
        // Login the test user
        $this->loginUser($this->testUser);

        // Create a JSON request
        $requestData = json_encode([
            'eventID' => $this->testEvent->getId(),
            'userID' => $this->testUser->getUsername()
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $this->requestStack->push($request);

        // Call the controller method directly
        $response = $this->eventController->toggleInterest($request, $this->entityManager);

        // Verify database state
        $interaction = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findOneBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);

        $this->assertNotNull($interaction);

        $this->assertEquals(EventInteractionStatus::INTERESTED, $interaction->getStatus());

        // Cleanup
        $this->entityManager->remove($interaction);
        $this->entityManager->flush();
    }

    public function testToggleInterestFromInterestedToNone(): void
    {
        $interaction = new EventInteraction();
        $interaction->setEvent($this->testEvent);
        $interaction->setUser($this->testUser);
        $interaction->setStatus(EventInteractionStatus::INTERESTED);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        $this->loginUser($this->testUser);

        $requestData = json_encode([
            'eventID' => $this->testEvent->getId(),
            'userID' => $this->testUser->getUsername()
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->setMethod('DELETE');
        $request->headers->set('Content-Type', 'application/json');
        $this->requestStack->push($request);

        $response = $this->eventController->toggleInterest($request, $this->entityManager);

        $this->assertEquals(200, $response->getStatusCode());

        $interaction = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findOneBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);

        $this->assertNull($interaction, 'Interaction should be removed when status is set to NO_INTERACTION');
    }

    public function testToggleInterestFromAttendingToInterested(): void
    {
        $interaction = new EventInteraction();
        $interaction->setEvent($this->testEvent);
        $interaction->setUser($this->testUser);
        $interaction->setStatus(EventInteractionStatus::ATTENDING);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        $this->loginUser($this->testUser);

        $requestData = json_encode([
            'eventID' => $this->testEvent->getId(),
            'userID' => $this->testUser->getUsername()
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $this->requestStack->push($request);

        $response = $this->eventController->toggleInterest($request, $this->entityManager);

        $this->assertEquals(200, $response->getStatusCode());

        $interaction = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findOneBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);

        $this->assertNotNull($interaction);
        $this->assertEquals(EventInteractionStatus::INTERESTED, $interaction->getStatus());

        $this->entityManager->remove($interaction);
        $this->entityManager->flush();
    }

    public function testToggleAttendanceFromNoneToAttending(): void
    {
        $this->loginUser($this->testUser);

        $requestData = json_encode([
            'eventID' => $this->testEvent->getId(),
            'userID' => $this->testUser->getUsername()
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $this->requestStack->push($request);

        $response = $this->eventController->toggleAttendance($request, $this->entityManager);

        $this->assertEquals(200, $response->getStatusCode());

        $interaction = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findOneBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);

        $this->assertNotNull($interaction);
        $this->assertEquals(EventInteractionStatus::ATTENDING, $interaction->getStatus());

        $this->entityManager->remove($interaction);
        $this->entityManager->flush();
    }

    public function testToggleAttendanceFromAttendingToNone(): void
    {
        // Create an interaction with attendance
        $interaction = new EventInteraction();
        $interaction->setEvent($this->testEvent);
        $interaction->setUser($this->testUser);
        $interaction->setStatus(EventInteractionStatus::ATTENDING);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        // Login the test user
        $this->loginUser($this->testUser);

        // Create a JSON request with DELETE method
        $requestData = json_encode([
            'eventID' => $this->testEvent->getId(),
            'userID' => $this->testUser->getUsername()
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->setMethod('DELETE');
        $request->headers->set('Content-Type', 'application/json');
        $this->requestStack->push($request);

        // Call the controller method directly
        $response = $this->eventController->toggleAttendance($request, $this->entityManager);

        // Verify interaction was removed from database
        $interaction = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findOneBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);

        $this->assertNull($interaction, 'Interaction should be removed when status is set to NO_INTERACTION');
    }

    public function testToggleAttendanceFromInterestedToAttending(): void
    {
        // Create an interaction with interested status
        $interaction = new EventInteraction();
        $interaction->setEvent($this->testEvent);
        $interaction->setUser($this->testUser);
        $interaction->setStatus(EventInteractionStatus::INTERESTED);
        $this->entityManager->persist($interaction);
        $this->entityManager->flush();

        // Login the test user
        $this->loginUser($this->testUser);

        // Create a JSON request
        $requestData = json_encode([
            'eventID' => $this->testEvent->getId(),
            'userID' => $this->testUser->getUsername()
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->headers->set('Content-Type', 'application/json');
        $request->setMethod('POST');
        $this->requestStack->push($request);

        // Call the controller method directly
        $response = $this->eventController->toggleAttendance($request, $this->entityManager);

        // Verify database state
        $interaction = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findOneBy([
                'event' => $this->testEvent,
                'user' => $this->testUser
            ]);

        $this->assertNotNull($interaction);
        $this->assertEquals(EventInteractionStatus::ATTENDING, $interaction->getStatus());

        // Cleanup
        $this->entityManager->remove($interaction);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up any remaining interactions
        $interactions = $this->entityManager
            ->getRepository(EventInteraction::class)
            ->findBy([
                'event' => $this->testEvent
            ]);

        foreach ($interactions as $interaction) {
            $this->entityManager->remove($interaction);
        }

        $this->entityManager->flush();

        // Reset the request stack and token storage
        if ($this->requestStack->getCurrentRequest()) {
            $this->requestStack->pop();
        }
        $this->tokenStorage->setToken(null);

        parent::tearDown();
    }
}