<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Event;
use Carbon\Carbon;
use App\Repository\EventRepository;

class EventControllerSeriesOfEvents extends WebTestCase
{
    private $token = null; // For authentication and authorization
    private $userID = null;
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $container = static::getContainer();
        $this->eventRepository = $container->get(EventRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
    }

    /**
     * Registered user creates an event that is not a series
     * @return void
     */
    public function testCreateNonSeriesEvent(): void
    {
        // Get the authentication token
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay One Time Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => false,
            'instanceNumber' => null,
            'eventRecurringType' => null,
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify that the event was created
        $createdEvent = $this->entityManager->getRepository(Event::class)->findBy(['eventTitle' => 'Zombie Cosplay One Time Competition',]);
        $this->assertCount(1, $createdEvent);
    }

    // Test series creation. 12 instances
    /**
     * Registered user assigns an event as a weekly recurring events with 12 instances
     * @return void
     */
    public function testCreateWeeklySeries12Instances(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Weekly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 12,
            'eventRecurringType' => 'WEEKLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify the events were created
        // Get the parent eventID
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Zombie Cosplay Weekly Competition',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(12, $eventSeries);

        // Verify the dates are correct using Carbon
        $currentDate = $startDate->copy();
        foreach ($eventSeries as $event) {
            $eventDate = Carbon::instance($event->getEventStartDate());
            $this->assertTrue($currentDate->format('Y-m-d H:i') === $eventDate->format('Y-m-d H:i'));
            $currentDate->addWeek();
        }
    }

    /**
     * Registered user assigns an event as a bi-weekly recurring events with 12 instances
     * @return void
     */
    public function testCreateBiweeklySeries12Instances(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Bi Weekly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 12,
            'eventRecurringType' => 'BI-WEEKLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify the events were created
        // Get the parent eventID
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Zombie Cosplay Bi Weekly Competition',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(12, $eventSeries);

        // Verify the dates are correct using Carbon
        $currentDate = $startDate->copy();
        foreach ($eventSeries as $event) {
            $eventDate = Carbon::instance($event->getEventStartDate());
            $this->assertTrue($currentDate->format('Y-m-d H:i') === $eventDate->format('Y-m-d H:i'));
            $currentDate->addWeeks(2);
        }
    }

    /**
     * Registered user assigns an event as a monthly recurring events with 12 instances
     * @return void
     */
    public function testCreateMonthlySeries12Instances(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Monthly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 12,
            'eventRecurringType' => 'MONTHLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify the events were created
        // Get the parent eventID
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Zombie Cosplay Monthly Competition',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(12, $eventSeries);

        // Verify the dates are correct using Carbon
        $currentDate = $startDate->copy();
        foreach ($eventSeries as $event) {
            $eventDate = Carbon::instance($event->getEventStartDate());
            $this->assertTrue($currentDate->format('Y-m-d H:i') === $eventDate->format('Y-m-d H:i'));
            $currentDate->addMonth();
        }
    }

    // Test series creation. 2 instances
    /**
     * Registered user assigns an event as a weekly recurring events with 2 instances
     * @return void
     */
    public function testCreateWeeklySeries2Instances(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Weekly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 2,
            'eventRecurringType' => 'WEEKLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify the events were created
        // Get the parent eventID
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Zombie Cosplay Weekly Competition',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(2, $eventSeries);

        // Verify the dates are correct using Carbon
        $currentDate = $startDate->copy();
        foreach ($eventSeries as $event) {
            $eventDate = Carbon::instance($event->getEventStartDate());
            $this->assertTrue($currentDate->format('Y-m-d H:i') === $eventDate->format('Y-m-d H:i'));
            $currentDate->addWeek();
        }
    }

    /**
     * Registered user assigns an event as a bi-weekly recurring events with 2 instances
     * @return void
     */
    public function testCreateBiweeklySeries2Instances(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Bi Weekly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 2,
            'eventRecurringType' => 'BI-WEEKLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify the events were created
        // Get the parent eventID
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Zombie Cosplay Bi Weekly Competition',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(2, $eventSeries);

        // Verify the dates are correct using Carbon
        $currentDate = $startDate->copy();
        foreach ($eventSeries as $event) {
            $eventDate = Carbon::instance($event->getEventStartDate());
            $this->assertTrue($currentDate->format('Y-m-d H:i') === $eventDate->format('Y-m-d H:i'));
            $currentDate->addWeeks(2);
        }
    }

    /**
     * Registered user assigns an event as a monthly recurring events with 2 instances
     * @return void
     */
    public function testCreateMonthlySeries2Instances(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Monthly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 2,
            'eventRecurringType' => 'MONTHLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify the events were created
        // Get the parent eventID
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Zombie Cosplay Monthly Competition',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(2, $eventSeries);

        // Verify the dates are correct using Carbon
        $currentDate = $startDate->copy();
        foreach ($eventSeries as $event) {
            $eventDate = Carbon::instance($event->getEventStartDate());
            $this->assertTrue($currentDate->format('Y-m-d H:i') === $eventDate->format('Y-m-d H:i'));
            $currentDate->addMonthsWithNoOverflow();
        }
    }

    /**
     * Registered user assigns an event as a monthly recurring events with 2 instances.
     * The start date is January 31st. The next instance's start date should be February 28th.
     * @return void
     */
    public function testCreateMonthlyEndOfMonthSeries2Instances(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 31, 9, 0, 0);
        $endDate = Carbon::create(2026, 2, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay End of Month Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 2,
            'eventRecurringType' => 'MONTHLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());

        // Verify the events were created
        // Get the parent eventID
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Zombie Cosplay End of Month Competition',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(2, $eventSeries);

        // Verify the dates are correct using Carbon
        $expectedFirstStartDate = Carbon::create(2026, 1, 31, 9, 0, 0);
        $this->assertEquals($expectedFirstStartDate->format('Y-m-d H:i:s'),
            $eventSeries[0]->getEventStartDate()->format('Y-m-d H:i:s'));

        $expectedFirstEndDate = Carbon::create(2026, 2, 1, 10, 0, 0);
        $this->assertEquals($expectedFirstEndDate->format('Y-m-d H:i:s'),
            $eventSeries[0]->getEventEndDate()->format('Y-m-d H:i:s'));

        $expectedSecondStartDate = Carbon::create(2026, 2, 28, 9, 0, 0);
        $this->assertEquals($expectedSecondStartDate->format('Y-m-d H:i:s'),
            $eventSeries[1]->getEventStartDate()->format('Y-m-d H:i:s'));

        $expectedSecondEndDate = Carbon::create(2026, 3, 1, 10, 0, 0);
        $this->assertEquals($expectedSecondEndDate->format('Y-m-d H:i:s'),
            $eventSeries[1]->getEventEndDate()->format('Y-m-d H:i:s'));
    }

    // Test deleting an event in series or the whole series
    /**
     * Registered user deletes an instance in the series with a past event
     * @return void
     */
    public function testDeleteInstanceInSeriesWithAPastEvent(): void
    {
        $this->signIn();
        $startDate = Carbon::create(2027, 2, 1, 9, 0, 0);

        // Fetch the event that will be deleted
        $eventToDelete = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Delete Series Test Event With a Past Event',
            'eventStartDate' => $startDate->toDateTime(),
        ]);

        $requestData = [
            'eventID' => $eventToDelete->getId(),
            'deleteSeries' => false,
        ];

        // Send the request to delete events
        $this->client->request(
            'DELETE',
            '/events/delete',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ],
            json_encode(['eventData' => $requestData])
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Verify the event was deleted
        // Get the parent event
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Delete Series Test Event With a Past Event',
            'parentEventID' => null
        ]);
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(2, $eventSeries);
    }

    /**
     * Registered user deletes series with a past event
     * @return void
     */
    public function testDeleteSeriesWithAPastEvent(): void
    {
        $this->signIn();

        $startDate = Carbon::create(2027, 3, 1, 9, 0, 0);

        // Fetch the event that will be deleted
        $eventToDelete = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Delete Series Test Event With a Past Event',
            'eventStartDate' => $startDate->toDateTime(),
        ]);

        $requestData = [
            'eventID' => $eventToDelete->getId(),
            'deleteSeries' => true,
        ];

        // Send the request to delete events
        $this->client->request(
            'DELETE',
            '/events/delete',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ],
            json_encode(['eventData' => $requestData])
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Verify the events were deleted
        // We are getting the eventID of the parent event. Which shouldn't be deleted
        $parentEvent = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Delete Series Test Event With a Past Event',
            'parentEventID' => null
        ]);
        // Past events that is in the series should remain
        $eventSeries = $this->eventRepository->findEventSeriesUpcomingOnly($parentEvent->getId());
        $this->assertCount(0, $eventSeries);
    }

    /**
     * Registered user deletes series without a past event
     * @return void
     */
    public function testDeleteSeriesWithoutAPastEvent(): void
    {
        $this->signIn();

        $startDate = Carbon::create(2027, 1, 1, 9, 0, 0);

        // Fetch the event that will be deleted
        $eventToDelete = $this->entityManager->getRepository(Event::class)->findOneBy([
            'eventTitle' => 'Delete Series Test Event Without a Past Event',
            'eventStartDate' => $startDate->toDateTime(),
        ]);

        $requestData = [
            'eventID' => $eventToDelete->getId(),
            'deleteSeries' => true,
        ];

        // Send the request to delete events
        $this->client->request(
            'DELETE',
            '/events/delete',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ],
            json_encode(['eventData' => $requestData])
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Verify the series is deleted
        $event = $this->entityManager->getRepository(Event::class)->findOneBy(['eventTitle' => 'Delete Series Test Event Without a Past Event']);
        $this->assertTrue($event === null);
    }

    // Validation of instance number
    /**
     * Registered user enters ‘1’ in the instance input box
     * @return void
     */
    public function testInstanceNumberOf1Validation(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Weekly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 1,
            'eventRecurringType' => 'WEEKLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        // Check the message
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid number. Enter numbers between 2-12', $data['errors']);
    }

    /**
     * Registered user enters ‘13’ in the instance input box
     * @return void
     */
    public function testInstanceNumberOf13Validation(): void
    {
        $this->signIn();

        // Create test data using Carbon
        $startDate = Carbon::create(2026, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2026, 1, 1, 10, 0, 0);

        $requestData = [
            'eventTitle' => 'Zombie Cosplay Weekly Competition',
            'eventDescription' => 'This is a zombie cosplay competition! Where you dress up as zombie, duh.',
            'eventLocation' => 'Cemetery',
            'eventAudience' => 'Youth',
            'eventStartDate' => $startDate->format('Y-m-d'),
            'eventStartTime' => $startDate->format('H:i:s'),
            'eventEndDate' => $endDate->format('Y-m-d'),
            'eventEndTime' => $endDate->format('H:i:s'),
            'recurring' => true,
            'instanceNumber' => 13,
            'eventRecurringType' => 'WEEKLY',
            'userId' => $this->userID,
            'eventCategory' => 'Arts and Culture',
        ];

        // Send the request to create events
        $this->client->request(
            'POST',
            '/events',
            [
                'eventData' => json_encode($requestData)
            ],
            [],
            [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
            ]
        );

        // Verify the response
        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        // Check the message
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid number. Enter numbers between 2-12', $data['errors']);
    }

    // Helper functions
    /**
     * Function helper that returns a token. Mimic the idea of being signed in.
     * @return void
     */
    public function signIn()
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $data = [
            'identifier' => 'username1',
            'password' => '@Password1',
            'rememberMe' => false
        ];

        // Mimic signing in
        $this->client->request(
            'POST',
            '/auth/signin',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        // Verify successful log in
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->token = $responseData['token'];

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'username1']);
        $this->userID = $user->getId();
    }
}
