<?php

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Controller\EventController;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\Transports;

class EventControllerStory2Test extends KernelTestCase
{
    private EventController $eventController;
    private EventRepository $eventRepo;
    private $jwtManager;
    protected function setUp(): void
    {
        // This mocks an event that has all data set
        $event = new Event();
        $event->setEventID(0);
        $event->setEventTitle("Event Title");
        $event->setEventDescription("Event Description");
        $event->setEventCreator(0);
        $event->setEventAudience(0);
        $event->setEventLocation("Event Location");

        $startDate = new \DateTime("2022-12-01 09:00:00");
        $event->setEventStartDate($startDate);

        $endDate = new \DateTime("2022-12-01 21:00:00");
        $event->setEventEndDate($endDate);

        $event->setEventLink("Event Link");
        $event->setEventImages("Event Images");
        $event->setModeratorApproval(true);

        $this->eventRepo = $this->createMock(EventRepository::class);
        $this->eventRepo->expects($this->any())->method('getEventByID')->willReturn($event);

        //$this->eventRepo->

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())->method('getRepository')->willReturn($this->eventRepo);

        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);

        $mail = $this->createMock(MailerInterface::class);

        $this->eventController = new EventController($this->eventRepo, $this->jwtManager, $mail, $entityManager);
    }

    /**
     * This test makes sure that the response is a json object
     * @return void
     */
    public function testExpandEventResponseReturnsJSON()
    {
        // expandEventResponse() should return a Response object with json content
        $responseObject = $this->eventController->getEvent();
        $responseContent = $responseObject->getContent();
        $this->assertJson($responseContent);

        // expandEventResponse(id) should return a Response object with json content
        $responseObject = $this->eventController->getEvent(1);
        $responseContent = $responseObject->getContent();
        $this->assertJson($responseContent);
    }

    /**
     * This test verifies that the JSON object from the response contains all data
     * @return void
     */
    public function testExpandEventResponseGivenEvent()
    {
        // expandEventResponse() should return the mock event json with eventID of 0
        $responseObject = $this->eventController->getEvent();
        $responseContent = $responseObject->getContent();
        $jsonContent = json_decode($responseContent, true);

        $this->assertEquals(0, $jsonContent['id']);
        $this->assertEquals("Event Title", $jsonContent['title']);
        $this->assertEquals("Event Description", $jsonContent['description']);
        $this->assertEquals(0, $jsonContent['creator']);
        $this->assertEquals(0, $jsonContent['audience']);
        $this->assertEquals("Event Location", $jsonContent['location']);
        $this->assertEquals("Event Link", $jsonContent['links']);

        $this->assertTrue($jsonContent['moderatorApproval']);

        // if an id is given that does not have an event, Response object should be empty JSON
        $this->eventRepo->expects($this->any())->method('getEventByID')->willThrowException(new \Exception());
        $responseObject = $this->eventController->getEvent(99999);
        $responseContent = $responseObject->getContent();
        $this->assertEquals("", $responseContent);
    }

    /**
     * This test makes sure that the repository returns an event with all data set.
     * @return void
     */
    public function testEventRepositoryGetEventByID()
    {
        $this->eventRepo->getEventByID(0);
        $event0 = $this->eventRepo->getEventByID(0);

        $this->assertEquals(0, $event0->getId());
        $this->assertEquals("Event Title", $event0->getEventTitle());
        $this->assertEquals("Event Description", $event0->getEventDescription());
        $this->assertEquals(0, $event0->getEventCreator());
        $this->assertEquals(0, $event0->getEventAudience());
        $this->assertEquals("Event Location", $event0->getEventLocation());
        $this->assertEquals("Event Link", $event0->getEventLink());
        $this->assertEquals("Event Images", $event0->getEventImages());
    }

    /**
     * This test makes sure that the JSON response only contains the require values,
     * because the event's non-required values were not set
     * @return void
     * @throws \Exception
     */
    public function testExpandEventRequiredOnly()
    {
        $event = new Event();
        $event->setEventID(1);
        $event->setEventTitle("Event Title 2");
        $event->setEventDescription("Event Description 2");
        $event->setEventCreator(0);
        $event->setEventAudience(0);
        $event->setEventLocation("Event Location 2");
        $startDate = new \DateTime("2022-12-01 09:00:00");
        $event->setEventStartDate($startDate);
        $event->setModeratorApproval(false);

        $eventRepo = $this->createMock(EventRepository::class);
        $eventRepo->expects($this->any())->method('getEventByID')->willReturn($event);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())->method('getRepository')->willReturn($eventRepo);
        $mail = $this->createMock(MailerInterface::class);

        $eventController = new EventController($eventRepo, $this->jwtManager, $mail, $entityManager);

        // expandEventResponse() should return the mock event json with eventID of 0
        $responseObject = $eventController->getEvent(1);
        $responseContent = $responseObject->getContent();
        $jsonContent = json_decode($responseContent, true);

        // These are all the required stuff
        $this->assertEquals(1, $jsonContent['id']);
        $this->assertEquals("Event Title 2", $jsonContent['title']);
        $this->assertEquals("Event Description 2", $jsonContent['description']);
        $this->assertEquals(0, $jsonContent['creator']);
        $this->assertEquals(0, $jsonContent['audience']);
        $this->assertEquals("Event Location 2", $jsonContent['location']);
        $this->assertFalse($jsonContent['moderatorApproval']);

        // These are all the optional stuff that's been unset

        // TODO: MORGAN!!!
//        $this->assertStringNotContainsString("\"endDate\"", $responseContent);
//        $this->assertStringNotContainsString("\"endTime\"", $responseContent);
//        $this->assertStringNotContainsString("\"links\"", $responseContent);
//        $this->assertStringNotContainsString("\"images\"", $responseContent);
    }
}
