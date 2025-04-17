<?php

namespace App\Tests\Controller;

use App\Controller\EventController;
use App\Entity\Event;
use App\Entity\Report;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class story10controllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $client;

    protected function setUp(): void
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');

        // Set up the client and get the entity manager
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $eventRepository = $this->entityManager->getRepository(Event::class);
    }

    /*
     * Helper method to generate a token
     */

    private function getAuthenticatedToken(): string
    {
        // Create or retrieve a test user
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        if (!$user) {
            $user = new User();
            $user->setUsername('testuser');
            $user->setEmail('testuser@example.com');
            $user->setPassword(password_hash('password123', PASSWORD_BCRYPT)); // Hash password
            $user->setModerator(false); // Set appropriate role

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Generate a JWT token
        return $this->jwtManager->create($user);
    }

    public function testInvalidBadEventTitleNotAdded(): void
    {
        // Create a client to simulate requests
        //$client = static::createClient();
        $token = $this->getAuthenticatedToken();

        // Invalid event data (missing 'eventTitle')
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => '',  // Invalid
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('You must enter in a title for the event', $this->client->getResponse()->getContent());

        // Invalid event data (title contains invalid characters)
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'T3st 3vent',  // Invalid
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];
        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        // Simulate sending a POST request with authentication
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('The event title cannot contain numbers or special characters', $this->client->getResponse()->getContent());
    }

    public function testInvalidBadEventDescriptionNotAdded(): void
    {
        // Create a client to simulate requests
        //$client = static::createClient();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        $token = $this->getAuthenticatedToken();

        // Invalid event data (Not enough characters in the description)
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',
            'eventDescription' => '', // Invalid
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('The description must be between 10 and 250 characters long.', $this->client->getResponse()->getContent());

        //Too long of a description
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',
            'eventDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum vitae enim tempus turpis ultricies condimentum nec ac felis. Integer facilisis sodales nibh, vitae gravida libero consectetur sed. Mauris fermentum vel sem quis maximus. Duis liberoa.', // Invalid
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('The description must be between 10 and 250 characters long.', $this->client->getResponse()->getContent());

    }

    public function testInvalidBadEventLocationNotAdded(): void
    {
        // Create a client to simulate requests
        //$client = static::createClient();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        $token = $this->getAuthenticatedToken();

        // Invalid event data (missing 'eventLocation')
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Description',  // Invalid
            'eventDescription' => 'Test Description',
            'eventLocation' => '',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];
        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('You must enter in a city for the event.', $this->client->getResponse()->getContent());

        // Invalid event data (Location contains invalid characters)
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',  // Invalid
            'eventDescription' => 'Test Description',
            'eventLocation' => 'S4skatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('You must enter in a valid city name for the event.', $this->client->getResponse()->getContent());
    }

    public function testInvalidBadEventStartNotAdded(): void
    {
        // Create a client to simulate requests
        //$client = static::createClient();
        $token = $this->getAuthenticatedToken();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        // Invalid event data (missing 'eventStartDate')
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Description',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '',// Invalid
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('{"errors":{"eventStartDate":"You must enter a start date and time."}}', $this->client->getResponse()->getContent());
        // Invalid event data (Date is before today)
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2020-01-01', //invalid
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('You must enter a start date that is after today.', $this->client->getResponse()->getContent());

        //invalid date format
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => 'bad date', //invalid
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        // Simulate sending a POST request to the /events endpoint

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('Use the calendar icon to enter a proper date.', $this->client->getResponse()->getContent());
    }

    public function testInvalidBadEventEndNotAdded(): void
    {
        // Create a client to simulate requests
        //$client = static::createClient();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        $token = $this->getAuthenticatedToken();

        // Invalid event data (eventEndDate before start date)
        $invalidEventData = array(
            'userId' => $user->getId(),
            'eventTitle' => 'Test Description',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-02',
            'eventEndDate' => '2025-12-31',// Invalid
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        );
        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('The end date must come after the start date.', $this->client->getResponse()->getContent());

        // Invalid event data (Date is before today)
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2020-01-02',//invalid
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('You must enter an end date that is after today.', $this->client->getResponse()->getContent());

        //invalid date format
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => 'Bad date',//invalid
            'eventStartTime' => '17:00:00',
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('Use the calendar icon to enter a proper date.', $this->client->getResponse()->getContent());

    }

    public function testInvalidBadEventStartTimeNotAdded(): void
    {
        // Create a client to simulate requests
        //$client = static::createClient();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        $token = $this->getAuthenticatedToken();

        // Invalid event data (missing 'eventStartTime')
        $invalidEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Description',
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '',// Invalid
            'eventEndTime' => '17:00:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $eventDataJson = json_encode($invalidEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert that the response status code is 400 (Bad Request)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Assert that the response contains the error message
        // Assert that the response body contains the expected error message
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('{"errors":{"eventStartDate":"You must enter a start date and time."}}', $this->client->getResponse()->getContent());
    }

    public function testValidEventAdded(): void
    {


        $eventRepository = $this->entityManager->getRepository(Event::class);
        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $this->entityManager);
        // Create a client to simulate requests
        //$client = static::createClient();

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username'=>'username1']);
        $token = $this->jwtManager->create($user);


        // Valid event data
        $validEventData = [
            'userId' => $user->getId(),
            'eventTitle' => 'Test Event Successful',  // Valid data
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00',
            'eventEndTime' => '17:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        // Simulate sending a POST request to the /events endpoint

        $eventDataJson = json_encode($validEventData);

        // Simulate sending a POST request to the /events endpoint
        $this->client->request(
            'POST',
            '/events',
            ['eventData' => $eventDataJson], // Send the JSON in 'eventData' field of FormData
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );
        //$response = $controller->getUserEvents($request, $this->entityManager);
        // Assert that the response status code is 201 (Created)
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Check if the event was added to the database
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $eventRepository = $entityManager->getRepository(Event::class);
        $event = $eventRepository->findOneBy(['eventTitle' => 'Test Event Successful']);

        // Assert that the event exists and has the correct properties
        $this->assertNotNull($event);
        $this->assertEquals('Test Event Successful', $event->getEventTitle());
        $this->assertEquals('Saskatoon', $event->getEventLocation());
    }
}


