<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;

class ReportControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $client;

    protected function setUp(): void
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');

        // Set up the client and get the entity manager
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    protected static function getKernelClass(): string
    {
        if (!class_exists($class = 'App\Kernel')) {
            throw new \RuntimeException(sprintf('Class "%s" doesn\'t exist or cannot be autoloaded. Check that the KERNEL_CLASS value in phpunit.xml matches the fully-qualified class name of your Kernel or override the "%s::createKernel()" method.', $class, static::class));
        }

        return $class;
    }

    public function testSuccessfulCreateReport(): void
    {
        //$client = static::createClient();
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        $aUser = $allUsers[0];

        $validEventData = [
            'eventTitle' => 'Test Event Successful A',  // Valid data
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00',
            'eventEndTime' => '17:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'userId' => $aUser->getId(),
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $this->client->request(
            'POST',
            '/events',
            ['eventData' => json_encode($validEventData)],
            [],
            [],
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Check if the event was added to the database
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $event = $eventRepository->findOneBy(['eventTitle' => 'Test Event Successful A']);

        // Assert that the event exists and has the correct properties
        $this->assertNotNull($event);
        $this->assertEquals('Test Event Successful A', $event->getEventTitle());
        $this->assertEquals('Saskatoon', $event->getEventLocation());

        // Define sample data for the POST request
        $data = [
            'eventID' => $event->getId(),
            'reason' => 'Spam'
        ];

        // Send POST request to the API
        $this->client->request('POST','/api/reports',[], [],
            ['CONTENT_TYPE' => 'application/json'],json_encode($data));

        // Assert the response status code is 201 Created
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        // Assert the response contains the expected success message
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Report created successfully']),
            $this->client->getResponse()->getContent()
        );

        // Verify that the report was actually created in the database
        $report = $this->entityManager->getRepository(Report::class)->findOneBy([
            'eventID' => $data['eventID'],
            'reason' => $data['reason'],
        ]);

        $this->assertNotNull($report, 'Report should be created and saved in the database.');
        $this->assertEquals($event->getId(), $report->getEventID());
        $this->assertEquals('Spam', $report->getReason());
    }

    public function testSuccessfulCreateReportLowBoundary(): void
    {
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        $aUser = $allUsers[0];

        //$client = static::createClient();
        $validEventData = [
            'eventTitle' => 'Test Event Successful B',  // Valid data
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00',
            'eventEndTime' => '17:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'userId' => $aUser->getId(),
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $this->client->request(
            'POST',
            '/events',
            ['eventData' => json_encode($validEventData)],
            [],
            [],
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Check if the event was added to the database
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $event = $eventRepository->findOneBy(['eventTitle' => 'Test Event Successful B']);

        // Assert that the event exists and has the correct properties
        $this->assertNotNull($event);
        $this->assertEquals('Test Event Successful B', $event->getEventTitle());
        $this->assertEquals('Saskatoon', $event->getEventLocation());

        // Define sample data for the POST request
        $data = [
            'eventID' => $event->getId(),
            'reason' => 'a'
        ];

        // Send POST request to the API
        $this->client->request('POST','/api/reports',[], [],
            ['CONTENT_TYPE' => 'application/json'],json_encode($data));

        // Assert the response status code is 201 Created
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        // Assert the response contains the expected success message
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Report created successfully']),
            $this->client->getResponse()->getContent()
        );

        // Verify that the report was actually created in the database
        $report = $this->entityManager->getRepository(Report::class)->findOneBy([
            'eventID' => $data['eventID'],
            'reason' => $data['reason'],
        ]);

        $this->assertNotNull($report, 'Report should be created and saved in the database.');
        $this->assertEquals($data['eventID'], $report->getEventID());
        $this->assertEquals($data['reason'], $report->getReason());
    }

    public function testSuccessfulCreateReportHighBoundary(): void
    {
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        $aUser = $allUsers[0];

        //$client = static::createClient();
        $validEventData = [
            'eventTitle' => 'Test Event Successful C',  // Valid data
            'eventDescription' => 'Test Description',
            'eventLocation' => 'Saskatoon',
            'eventStartDate' => '2026-01-01',
            'eventEndDate' => '2026-01-02',
            'eventStartTime' => '17:00',
            'eventEndTime' => '17:00',
            'eventCategory' => 'Music',
            'eventAudience' => 'Family_Friendly',
            'eventLink' => 'https://www.google.ca',
            'userId' => $aUser->getId(),
            'instanceNumber' => 1,
            'recurring' => false,
        ];

        $this->client->request(
            'POST',
            '/events',
            ['eventData' => json_encode($validEventData)],
            [],
            [],
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Check if the event was added to the database
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $event = $eventRepository->findOneBy(['eventTitle' => 'Test Event Successful C']);

        // Assert that the event exists and has the correct properties
        $this->assertNotNull($event);
        $this->assertEquals('Test Event Successful C', $event->getEventTitle());
        $this->assertEquals('Saskatoon', $event->getEventLocation());

        // Define sample data for the POST request
        $data = [
            'eventID' => $event->getId(),
            'reason' => str_repeat('a', 255)
        ];

        // Send POST request to the API
        $this->client->request('POST','/api/reports',[], [],
            ['CONTENT_TYPE' => 'application/json'],json_encode($data));

        // Assert the response status code is 201 Created
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        // Assert the response contains the expected success message
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Report created successfully']),
            $this->client->getResponse()->getContent()
        );

        // Verify that the report was actually created in the database
        $report = $this->entityManager->getRepository(Report::class)->findOneBy([
            'eventID' => $data['eventID'],
            'reason' => $data['reason'],
        ]);

        $this->assertNotNull($report, 'Report should be created and saved in the database.');
        $this->assertEquals($data['eventID'], $report->getEventID());
        $this->assertEquals($data['reason'], $report->getReason());
    }

    public function testFailReportWithEmptyEventID()
    {
        // Send a POST request with an empty eventID
        $this->client->request('POST', '/api/reports', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'reason' => 'This is a test reason'
        ]));

        // Assert the response status code is 400 (Bad Request)
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Assert the response contains the expected error message
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'EventID is required and must be an integer']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testFailReportWithEventIDIsNotNumeric()
    {
        // Send a POST request with an empty eventID
        $this->client->request('POST', '/api/reports', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'eventID'=>'abc',
            'reason' => 'This is a test reason'
        ]));

        // Assert the response status code is 400 (Bad Request)
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Assert the response contains the expected error message
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'EventID is required and must be an integer']),
            $this->client->getResponse()->getContent()
        );
    }

    public function testFailReportWithEmptyReason()
    {
        // Send a POST request with an empty reason
        $this->client->request('POST', '/api/reports', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'eventID' => 12,
            'reason' => '        '
        ]));

        // Assert the response status code is 400 (Bad Request)
        $this->assertEquals(422, $this->client->getResponse()->getStatusCode());

        // Check that the response contains an error message
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message'=>'Invalid data',
                'errors'=>["reason"=>"Reason is required"]]),
            $this->client->getResponse()->getContent()
        );
    }

    public function testFailReportWithReasonLongerThan255Characters()
    {
        // Generate a string with 256 characters
        $longReason = str_repeat('a', 256);

        // Send a POST request with a reason longer than 255 characters
        $this->client->request('POST', '/api/reports', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'eventID' => 123,
            'reason' => $longReason
        ]));

        // Assert the response status code is 400 (Bad Request)
        $this->assertEquals(422, $this->client->getResponse()->getStatusCode());

        // Check that the response contains an error message
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message'=>'Invalid data',
                'errors'=>["reason"=>"Reason must not exceed 255 characters"]]),
            $this->client->getResponse()->getContent()
        );
    }


    protected function tearDown(): void
    {
        // Clean up database after the test
        $report = $this->entityManager->getRepository(Report::class)->findOneBy(['eventID' => 123]);
        if ($report) {
            $this->entityManager->remove($report);
            $this->entityManager->flush();
        }

        parent::tearDown();
    }
}
