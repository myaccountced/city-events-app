<?php


namespace App\Tests\Controller;

use App\Controller\EventController;
use App\Entity\Banned;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Tests\tests\DataFixtures\EventFixtures;
use App\Tests\tests\DataFixtures\AppUserFixture;
use App\Tests\tests\DataFixtures\EventFixtureSixEvents;
use App\Tests\tests\DataFixtures\lotsOfEvents;
use DateTime;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventControllerTest extends KernelTestCase
{
    protected DatabaseToolCollection $databaseTool;
    protected EntityManagerInterface $entityManager;
//    private EventController $controller;
    public function setUp(): void
    {
        parent::setUp();

        // Load environment variables
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');

        $kernel = self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->controller = $container->get(EventController::class);
        $this->jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
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



    /**
     * story_3_guest_sorts_the_list_of_events
     */

    /**
     * Test getEvents with custom pagination
     */
    public function testGetEventsWithPagination(): void
    {
        $this->loadFixtures([new EventFixtures()]);
        $eventRepository = $this->entityManager->getRepository(Event::class);
        // Mock JWT Manager
        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);

        $request = new Request(['limit' => 20, 'offset' => 0]);
        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);

        // Should only get 6 events due to limit
        $this->assertCount(6, $data);

        foreach ($data as $event) {
            // Media tests
            $this->assertArrayHasKey('media', $event);
            $this->assertIsArray($event['media']);
            $this->assertGreaterThan(0, count($event['media']));
            $this->assertContains('p1.jpg', $event['media']);
            $this->assertContains('chilidisaster.jpg', $event['media']);

            // Bookmark tests
            $this->assertArrayHasKey('bookmarks', $event);
            $this->assertIsArray($event['bookmarks']);


        }
    }

    /**
     * Test getEvents with default pagination and sorting,
     * which is date ascending
     */
//    public function testGetEventsDefaultParameters(): void
//    {
//        $this->loadFixtures([new EventFixtures()]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        // Mock JWT Manager
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        $request = new Request();
//        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        // Default limit is 20, so we should get all 6 events from fixtures
//        $this->assertCount(6, $data);
//
//        // Check default sorting (by startDate ascending)
//        $events = array_map(function ($event) {
//            return [
//                'date' => $event['startDate'],
//                'name' => $event['title'],
//                'location' => $event['location']
//            ];
//        }, $data);
//
//        $this->assertTrue($this->isSortedBy($events, 'date', 'asc'));
//    }


    /**
     * Test getEvents sorting by date in ascending order
     */
//    public function testGetEventsWithPaginationWithLocationDescending(){
//        $this->loadFixtures([new lotsOfEvents()]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        // Mock JWT Manager
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//        // Create a Request object with query parameters
//        $request = new Request(
//            ['limit' => 20, 'offset' => 0, 'sortField' => 'eventLocation', 'sortOrder' => 'DESC']  // Query parameters as array
//        );
//        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);
//        // check that it is a json
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        // need to decode
//        $data = json_decode($response->getContent(), true);
//        // Check that there are 20 events
//        $this->assertCount(20, $data);
//        // Map the response data into the same structure as $events array
//        $events = array_map(function ($event) {
//            return [
//                'date' => $event['startDate'],
//                'name' => $event['title'],
//                'location' => $event['location']
//            ];
//        }, $data);
//        // Check that the events are sorted by location in ascending order
//        $this->assertTrue($this->isSortedBy($events, 'eventLocation', 'desc'));
//        // For events with same location, check if they are sorted by date and name
//        for ($i = 0; $i < count($events) - 1; $i++) {
//            if ($events[$i]['location'] === $events[$i + 1]['location']) {
//                // Check date ordering (ascending)
//                $this->assertLessThanOrEqual(0, strcmp($events[$i]['date'], $events[$i + 1]['date']));
//                // If dates are equal, check name ordering (ascending)
//                if ($events[$i]['date'] === $events[$i + 1]['date']) {
//                    $this->assertLessThanOrEqual(0, strcmp($events[$i]['name'], $events[$i + 1]['name']));
//                }
//            }
//        }
//    }


    /**
     * Test getEvents sorting by date in descending order
     */
//    public function testGetEventsSortByDateDescending(): void
//    {
//        $this->loadFixtures([new EventFixtures()]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        // Mock JWT Manager
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        $request = new Request(['category' => 'startDate', 'order' => 'desc']);
//        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        $events = array_map(function ($event) {
//            return [
//                'date' => $event['startDate'],
//                'name' => $event['title'],
//                'location' => $event['location']
//            ];
//        }, $data);
//
//        $this->assertTrue($this->isSortedBy($events, 'eventStartDate', 'desc'));
//    }

    /**
     * Test getEvents sorting by title in ascending order
     */
//    public function testGetEventsSortByTitleAscending(): void
//    {
//        $this->loadFixtures([new EventFixtures()]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        // Mock JWT Manager
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        $request = new Request(['category' => 'title', 'order' => 'asc']);
//        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        $events = array_map(function ($event) {
//            return [
//                'date' => $event['startDate'],
//                'name' => $event['title'],
//                'location' => $event['location']
//            ];
//        }, $data);
//
//        $this->assertTrue($this->isSortedBy($events, 'name', 'asc'));
//    }

    /**
     * Test getEvents sorting by title in descending order
     */
//    public function testGetEventsSortByTitleDescending(): void
//    {
//        $this->loadFixtures([new EventFixtures()]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        // Mock JWT Manager
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        $request = new Request(['category' => 'title', 'order' => 'desc']);
//        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        $events = array_map(function ($event) {
//            return [
//                'date' => $event['startDate'],
//                'name' => $event['title'],
//                'location' => $event['location']
//            ];
//        }, $data);
//
//        $this->assertTrue($this->isSortedBy($events, 'eventTitle', 'desc'));
//    }

    /**
     * Test getEvents sorting by location in ascending order
     */
//    public function testGetEventsSortByLocationAscending(): void
//    {
//        $this->loadFixtures([new EventFixtures()]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        // Mock JWT Manager
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        $request = new Request(['category' => 'location', 'order' => 'asc']);
//        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        $events = array_map(function ($event) {
//            return [
//                'date' => $event['startDate'],
//                'name' => $event['title'],
//                'location' => $event['location']
//            ];
//        }, $data);
//
//        $this->assertTrue($this->isSortedBy($events, 'eventLocation', 'asc'));
//    }


    /**
     * Test getEvents sorting by location in descending order
     */
//    public function testGetEventsSortByLocationDescending(): void
//    {
//        $this->loadFixtures([new EventFixtures()]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        // Mock JWT Manager
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        $request = new Request(['category' => 'location', 'order' => 'desc']);
//        $response = $controller->getEventsWithFilterAndSorter($request, $eventRepository);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        $events = array_map(function ($event) {
//            return [
//                'date' => $event['startDate'],
//                'name' => $event['title'],
//                'location' => $event['location']
//            ];
//        }, $data);
//
//        $this->assertTrue($this->isSortedBy($events, 'eventLocation', 'desc'));
//    }


    /**
     * end
     */


    /**
     * story22_registered_user_includes_a_photo_in_an_event_post
     */

    private function createMockEvent(): array
    {
        // Instead of creating and persisting an event, return event data
        return [
            'eventTitle' => 'Test Event',
            'eventDescription' => 'Test Description',
            'eventStartDate' => '2024-01-01',
            'eventEndDate' => '2024-01-02',
            'eventLocation' => 'Test Location',
            'eventAudience' => 'Public',
            'eventCategory' => 'Test Category',
            'eventLink' => 'http://example.com',
            'creator' => 'Creator'
        ];
    }
    private function createMockEvent2(): Event
    {
        $event = new Event();
        $event->setEventTitle('Test Event');
        $event->setEventDescription('Test Description');
        $event->setEventStartDate(new \DateTime('2024-01-01'));
        $event->setEventEndDate(new \DateTime('2024-01-02'));
        $event->setEventLocation('Test Location');
        $event->setEventAudience('Public');
        $eventCat = new Category();
        $eventCat->setCategoryName("Others");
        $event->addCategory($eventCat);

        $event->setEventLink('http://example.com');
        $event->setEventCreator('Creator');

        $this->entityManager->persist($event);
        $this->entityManager->persist($eventCat);
        $this->entityManager->flush();

        return $event;
    }

    public function testUploadImageWithJpgFile(): void
    {
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');
        $uploadedFile = new UploadedFile(
            $projectDir . '\tests\tests\DataFixtures\p1.jpg',
            'p1.jpg',
            'image/jpeg',
            null,
            true
        );

        // Create event data
        $eventData = $this->createMockEvent();

        // Create request with both file and event data
        $request = new Request(
            [], // POST data
            ['eventData' => json_encode($eventData)], // Query parameters
            [], // Cookies
            [], // Files
            ['photoOne' => $uploadedFile] // Uploaded file
        );
        $event = $this->createMockEvent2();
        $response = $this->controller->uploadImages($request, $event, $this->entityManager);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('imageCount', $response);
        $this->assertEquals(1, $response['imageCount']);

        // Fetch the Media entity created during upload to get the filename
        $mediaRepo = $this->entityManager->getRepository(Media::class);
        $media = $mediaRepo->findOneBy(['event' => $event]);
        $uploadedFileName = $media->getPath();
        $uploadDir = $projectDir . '/public/uploads';
        $uploadedFilePath = $uploadDir . '/' . $uploadedFileName;

        // Move the file to the tests/tests/uploads directory
        $testUploadDir = $projectDir . '/tests/tests/uploads';
        $movedFilePath = $testUploadDir . '/' . $uploadedFileName;
        rename($uploadedFilePath, $movedFilePath);

        // Assert the file exists in the new destination
        $this->assertFileExists($movedFilePath, 'Uploaded file was not moved to the tests/tests/uploads directory.');
    }

    public function testUploadImageWithInvalidFile(): void
    {
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');
        $uploadedFile = new UploadedFile(
            $projectDir . '\tests\tests\DataFixtures\pdf1.pdf',
            'pdf1.pdf',
            'application/pdf',
            null,
            true
        );

        // Create event data
        $eventData = $this->createMockEvent();

        $request = new Request(
            [], // POST data
            ['eventData' => json_encode($eventData)], // Query parameters
            [], // Cookies
            [], // Files
            ['photoOne' => $uploadedFile] // Uploaded file
        );

        $event = $this->createMockEvent2();
        $response = $this->controller->uploadImages($request, $event, $this->entityManager);

        // Decode the JSON response content
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('imageCount', $response);
        $this->assertEquals(0, $response['imageCount']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertNotEmpty($response['errors']);
    }

    public function testUploadImageWithFileSizeExceeding5MB(): void
    {
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');
        $uploadedFile = new UploadedFile(
            $projectDir . '\tests\tests\DataFixtures\p1Large.jpg',
            'p1Large.jpg',
            'image/jpeg',
            null,
            true
        );

        // Create event data
        $eventData = $this->createMockEvent();

        $request = new Request(
            [], // POST data
            ['eventData' => json_encode($eventData)], // Query parameters
            [], // Other attributes
            [], // Cookies
            ['photoOne' => $uploadedFile] // Files
        );

        $event = $this->createMockEvent2();
        $response = $this->controller->uploadImages($request, $event, $this->entityManager);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('imageCount', $response);
        $this->assertEquals(0, $response['imageCount']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertNotEmpty($response['errors']);
    }


    public function testUploadImageWithMultipleFiles(): void
    {
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');

        // Create event data
        $eventData = $this->createMockEvent();

        $request = new Request(
            [], // POST data
            ['eventData' => json_encode($eventData)], // Query parameters
            [], // Other attributes
            [], // Cookies
            [
                'photoOne' => new UploadedFile(
                    $projectDir . '\tests\tests\DataFixtures\p1.jpg',
                    'p1.jpg',
                    'image/jpeg',
                    null,
                    true
                ),
                'photoTwo' => new UploadedFile(
                    $projectDir . '\tests\tests\DataFixtures\p2.jpg',
                    '2.jpg',
                    'image/jpeg',
                    null,
                    true
                ),
                'photoThree' => new UploadedFile(
                    $projectDir . '\tests\tests\DataFixtures\p3.jpg',
                    'p3.jpg',
                    'image/jpeg',
                    null,
                    true
                ),
            ]
        );

        $event = $this->createMockEvent2();
        $response = $this->controller->uploadImages($request, $event, $this->entityManager);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('imageCount', $response);
        $this->assertEquals(3, $response['imageCount']);

        // Fetch the Media entity created during upload to get the filename
        $mediaRepo = $this->entityManager->getRepository(Media::class);
//        $media = $mediaRepo->findOneBy(['event' => $event]);
        for ($i = 0; $i < 3; $i++) {
            $media = $mediaRepo->findAll()[$i]; // Fetch the media entity at index $i
            $uploadedFileName = $media->getPath();
            $uploadDir = $projectDir . '/public/uploads';
            $uploadedFilePath = $uploadDir . '/' . $uploadedFileName;

            // Move the file to the tests/tests/uploads directory
            $testUploadDir = $projectDir . '/tests/tests/uploads';
            $movedFilePath = $testUploadDir . '/' . $uploadedFileName;
//            rename($uploadedFilePath, $movedFilePath);
        }

    }

    public function testUploadImageWithInvalidMultipleFiles(): void
    {
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');

        // Create event data
        $eventData = $this->createMockEvent();

        $request = new Request(
            [], // POST data
            ['eventData' => json_encode($eventData)], // Query parameters
            [], // Other attributes
            [], // Cookies
            [
                'photoOne' => new UploadedFile(
                    $projectDir . '\tests\tests\DataFixtures\p1.jpg',
                    'p1.jpg',
                    'image/jpeg',
                    null,
                    true
                ),
                'photoTwo' => new UploadedFile(
                    $projectDir . '\tests\tests\DataFixtures\p2.jpg',
                    '2.jpg',
                    'image/jpeg',
                    null,
                    true
                ),
                'photoThree' => new UploadedFile(
                    $projectDir . '\tests\tests\DataFixtures\p3.jpg',
                    'p3.jpg',
                    'image/jpeg',
                    null,
                    true
                ),
                'photoFour' => new UploadedFile(
                    $projectDir . '\tests\tests\DataFixtures\p4.jpg',
                    'p4.jpg',
                    'image/jpeg',
                    null,
                    true
                ),
            ]
        );

        $event = $this->createMockEvent2();
        $response = $this->controller->uploadImages($request, $event, $this->entityManager);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertNotEmpty($response['errors']);
    }

    /**
     * end
     */

    /**
     * story36_registered_user_sees_a_list_of_events_that_they_have_posted
     */

    /**
     * Test when user has no events
     */
    public function testNoFutureEvents(): void
    {
        /*$this->loadFixtures([
            new AppUserFixture(),
            new eventFixtureSixEvents()
        ]);*/

        // Mocks
        $securityMock = $this->createMock(Security::class);
        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);

        // Fetch a test user and generate the JWT token
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'nUserS15']);
        $token = $this->jwtManager->create($user);
        $userId = $user->getId();
        $username = $user->getUsername();

        error_log("Authenticated user ID: " . $userId);
        error_log("JWT Token: " . $token);

        // Ensure Security service returns the user
        $securityMock->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        // Create request with Authorization header
        $request = new Request(
            ['currentUser' => $username]
        );
        $request->headers->set('Authorization', 'Bearer ' . $token);

        error_log("Authorization Header: " . $request->headers->get('Authorization'));

        // Ensure JWT Manager correctly creates tokens (even though it's not used for parsing)
        $jwtManagerMock->expects($this->any())
            ->method('create')
            ->willReturn($token);

        // Create the controller instance
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);

        // Pass the request and get the response
        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);

        // Assert the response is a JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Decode the JSON response and log the result for debugging
        $data = json_decode($response->getContent(), true);
        error_log("Events fetched: " . count($data));
        error_log("Event Data: " . print_r($data, true));

        // Assert the returned data is empty (no future events for this user)
        $this->assertEmpty($data[0]);
    }


    public function testNoTokenProvided(): void
    {
        /*$this->loadFixtures([
            new AppUserFixture(),
            new eventFixtureSixEvents()
        ]);*/


        // Create a mock Security service to simulate the logged-in user
        $securityMock = $this->createMock(Security::class);
        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'nUserS15']);
        $username = $user->getUsername();

        // Create a request **without** an Authorization header
        $request = new Request(
            ['currentUser' => $username]
        );
        $securityMock->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        // Pass the request and get the response, using the mock Security service
        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode()); // Expect unauthorized error
    }

    public function testInvalidToken(): void
    {
        /*$this->loadFixtures([
            new AppUserFixture(),
            new eventFixtureSixEvents()
        ]);*/

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);
        $userId = $user->getId();

        $securityMock = $this->createMock(Security::class);
        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);

        // Ensure Security service returns null (since the user should not be authenticated)
        $securityMock->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        // Mock JWT manager to throw an exception on an invalid token
        $jwtManagerMock->expects($this->any())
            ->method('decode')
            ->willThrowException(new \Exception("Invalid JWT token")); // Simulating token failure

        // Create a request with an **invalid** token
        $request = new Request(
            ['condition' => 'future', 'order' => 'ASC']
        );
        $request->headers->set('Authorization', 'Bearer invalidtoken');

        // Call the controller method
        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);

        // Assert 401 Unauthorized response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode()); // Expect unauthorized error

        // Assert correct error message
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['error' => 'Unauthorized'], $data);
    }



    /**
     * Test future events sorting for multiple events
     */
//    public function testMultipleFutureEvents(): void
//    {
//        $this->loadFixtures([
//            new UserFixtures(),
//            new eventFixtureSixEvents()
//        ]);
//
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        $securityMock = $this->createMock(Security::class);
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        // Get the user ID dynamically
//        $user = $this->entityManager->getRepository(User::class)
//            ->findOneBy(['username' => 'username2']);
//        $id = $user->getId();
//        $username = $user->getUsername();
//        //Generate a token for the user
//        $token = $this->jwtManager->create($user);
//
//        $request = new Request(['user' => $username]);
//        //Attach the token to the header of the request.
//        $request->headers->set('Authorization', 'Bearer ' . $token);
//        $jwtManagerMock->expects($this->any())
//            ->method('create')
//            ->willReturn($token);
//        $securityMock->expects($this->any())
//            ->method('getUser')
//            ->willReturn($user);
//        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//        error_log("Events fetched: " . count($data[0][0]));
//        error_log("Event Data: " . print_r($data[0][0], true));
////        print_r(json_decode($response->getContent(), true));
//        // Should have exactly three future events
//        $this->assertEquals(3, count($data[0]));
//
//        // Verify chronological order for date
//        $this->assertEquals('2025-10-01', $data[0][0]['startDate']); // Oct 1
//        $this->assertEquals('2025-11-01', $data[0][1]['startDate']); // Nov 1
//        $this->assertEquals('2025-11-01', $data[0][2]['startDate']); // Nov 1
//
//        // Verify chronological order for title
//        $this->assertEquals('Event Title 1', $data[0][0]['title']); // Oct 1
//        $this->assertEquals('Event Title 2', $data[0][1]['title']); // Nov 1
//        $this->assertEquals('Event Title 3', $data[0][2]['title']); // Nov 1
//
//        // verify events are sorted by date in order of descending
//        $this->assertTrue($this->isSortedBy($data, 'date', 'asc'));
//
//        // Verify same-date events are sorted by title
//        $this->assertTrue(
//            $data[0][1]['startDate'] === $data[0][2]['startDate'] &&
//            strcmp($data[0][1]['title'], $data[0][2]['title']) < 0
//        );
//
//        $event = $data[0][0]; // Get the first event
//
//        // Assert that the 'images' field exists and is an array
//        $this->assertArrayHasKey('images', $event);
//        $this->assertIsArray($event['images']);
//
//        // Assert that 'p1.jpg' is in the 'images' array
//        $this->assertContains('p1.jpg', $event['images']);
//    }

    /**
     * Test past events
     */

    /**
     * Test when user has no past events
     */
//    public function testNoPastEvents(): void
//    {
//        $this->loadFixtures([
//            new UserFixtures(),
//            new eventFixtureSixEvents()
//        ]);
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        $securityMock = $this->createMock(Security::class);
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username'=>'username1']);
//        $userId = $user->getId();
//        //generate token
//        $token = $this->jwtManager->create($user);
//        // Test for a user with no events (userId = 2)
//
//        $request = new Request(['userID' => $userId, 'condition' => 'past', 'order' => 'DESC']);
//        //Attach token to request
//        $request->headers->set('Authorization', 'Bearer ' . $token);
//
//        $securityMock->expects($this->any())
//            ->method('getUser')
//            ->willReturn($user);
//        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        // Should be empty array when no events
//        $this->assertEmpty($data[1]);
//    }

    /**
     * Test when user has multiple past events
     */
//    public function testMultiplePastEvents(): void
//    {
//        $this->loadFixtures([
//            new UserFixtures(),
//            new eventFixtureSixEvents()
//        ]);
//
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        $securityMock = $this->createMock(Security::class);
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        $user = $this->entityManager->getRepository(User::class)
//            ->findOneBy(['username' => 'username2']);
//        //Generate a token for the user
//        $token = $this->jwtManager->create($user);
//
//        $id = $user->getId();
//        $username = $user->getUsername();
//
//        $request = new Request(['user' => $username]);
//        //attach the token to the request
//        $request->headers->set('Authorization', 'Bearer ' . $token);
//        $securityMock->expects($this->any())
//            ->method('getUser')
//            ->willReturn($user);
//
//        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);
//
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $data = json_decode($response->getContent(), true);
//
//        // Should have exactly three past events
//        $this->assertEquals(3, count($data[1]));
//
//        // Verify chronological order for date
//        $this->assertEquals('2025-01-02', $data[1][0]['startDate']); // Jan 2
//        $this->assertEquals('2025-01-02', $data[1][1]['startDate']); // Jan 2
//        $this->assertEquals('2025-01-01', $data[1][2]['startDate']); // Jan 1
//
//        // Verify reverse chronological order
//        $this->assertEquals('Event Title 5', $data[1][0]['title']); // Jan 2
//        $this->assertEquals('Event Title 6', $data[1][1]['title']); // Jan 2
//        $this->assertEquals('Event Title 4', $data[1][2]['title']); // Jan 1
//
//        // verify events are sorted by date in order of descending
//        $this->assertTrue($this->isSortedBy($data[1], 'date', 'desc'));
//
//        // Verify same-date events are sorted by title
//        $this->assertTrue(
//            $data[1][0]['startDate'] === $data[1][1]['startDate'] &&
//            strcmp($data[1][0]['title'], $data[1][1]['title']) < 0
//        );
//    }

    /**
     * Test when userId is invalid or does not exist
     */
//    public function testInvalidUserId(): void
//    {
//        // Load fixture with some basic data
//        $this->loadFixtures([
//            new UserFixtures(),
//            new eventFixtureSixEvents()
//        ]);
//
//        $eventRepository = $this->entityManager->getRepository(Event::class);
//        $securityMock = $this->createMock(Security::class);
//        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
//        $mailerMock = $this->createMock(MailerInterface::class);
//        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
//
//        // Test with non-existent userId
//        $request = new Request(
//            ['userID' => 'invalid', 'condition' => 'future', 'order' => 'ASC'],
//        );
//        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);
//
//        // Assert response type
//        $this->assertInstanceOf(JsonResponse::class, $response);
//
//        // Assert response status code
//        $this->assertEquals(401, $response->getStatusCode());
//
//        // Assert response content
//        $data = json_decode($response->getContent(), true);
//        $this->assertArrayHasKey('error', $data);
//        $this->assertEquals('Unauthorized', $data['error']);
//
//        // Test with invalid userId format (non-numeric)
//        $request = new Request(
//            [],
//            [],
//            ['userID' => 999, 'condition' => 'future', 'order' => 'ASC'],
//            [],
//            []
//        );
//        $securityMock->expects($this->any())
//            ->method('getUser')
//            ->willReturn(null);
//        $response = $controller->getUserEvents($request, $this->entityManager, $securityMock);
//
//        // Assert response for invalid format
//        $this->assertInstanceOf(JsonResponse::class, $response);
//        $this->assertEquals(401, $response->getStatusCode());
//
//        $data = json_decode($response->getContent(), true);
//        $this->assertArrayHasKey('error', $data);
//    }
//
//    /**
//     * This is the helper method to sort arraylist by one attribute and order
//     * @param array $events
//     * @param $keys
//     * @param string $order
//     * @return bool
//     */
//    public function isSortedBy(array $events, $keys, string $order = 'asc'): bool
//    {
//        if (count($events) === 0) {
//            return true; // No events mean nothing to sort, so it's valid.
//        }
//
//        $keys = (array)$keys; // Ensure keys are an array
//
//        for ($i = 1; $i < count($events); $i++) {
//            foreach ($keys as $key) {
//                if (!isset($events[$i - 1][$key]) || !isset($events[$i][$key])) {
//                    continue; // Skip missing keys
//                }
//
//                if ($order === 'asc') {
//                    if ($events[$i - 1][$key] > $events[$i][$key]) {
//                        return false; // Not ascending
//                    }
//                } else {
//                    if ($events[$i - 1][$key] < $events[$i][$key]) {
//                        return false; // Not descending
//                    }
//                }
//            }
//        }
//
//        return true;
//    }

    /**
     * End
     */


    /**
     *  Story 41 Banned user cannot post an event
     */
    public function testBannedUserCannotPostEvent(): void
    {
        // get a user
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => 'username2']);

        // Create a banned record for the user
        $banned = new Banned();
        $banned->setUserId($user);
        $banned->setReason('Test ban');

        $this->entityManager->persist($banned);
        $this->entityManager->flush();

        $user->setBanned($banned);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        print_r($user->getId());
        $eventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Test Location',
            'eventAudience' => 'Everyone',
            'eventCategory' => 'General',
            'eventStartDate' => '2025-07-03',
            'eventStartTime' => '10:00:00',
            'eventEndDate' => '2025-07-03',
            'eventEndTime' => '12:00:00',
            'eventLink' => 'http://example.com',
            'creator' => 'TestCreator',
        ];

        $request = new Request(
            [],
            ['eventData' => json_encode($eventData)],
        );

        // Get the Validator from the container
        $validator = self::getContainer()->get(ValidatorInterface::class);

        // Pass all required arguments to postEvent()
        $response = $this->controller->postEvent($request, $this->entityManager, $validator);
        $this->assertInstanceOf(JsonResponse::class, $response);
        //HTTP_FORBIDDEN
        $this->assertEquals(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('You are banned and cannot post events.', $data['error']);
    }

    /**
     * End
     */

}