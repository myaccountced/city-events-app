<?php

namespace App\Tests\Controller;

use App\Tests\tests\DataFixtures\EventFixtures;
use App\Tests\tests\DataFixtures\LinkFixtures;
use App\Entity\Event;
use App\Repository\EventRepository;
use App\Controller\EventController;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;


class EventControllerStory6Test extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected EntityRepository $eventRepository;

    protected EventController $controller;
    public function setUp(): void
    {
        $kernel = self::bootKernel();
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function loadFixtures(array $fixtureInstances): void
    {
        // this will delete data from the table, just like a DELETE statement would
        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        // Execute the fixture instances
        $executor->execute($fixtureInstances);
    }

    /**
     * @return void
     * Test that the link associated with an event is the same as the one associated with that event
     * in the database
     */
    public function testExternalLinkDisplayedIsCorrect(): void
    {
        $this->loadFixtures([new EventFixtures()]);

        $eventRepository = $this->entityManager->getRepository(Event::class);
        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
        $entityManager = $this->createMock(EntityManager::class);
        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $entityManager);

        $responseObject = $controller->getEventsWithFilterAndSorter(new \Symfony\Component\HttpFoundation\Request(), $eventRepository);

        // get the data from the response
        $responseContent = $responseObject->getContent();

        // assert that we are getting a JSON response
        $this->assertJson($responseContent);

        $jsonContent = json_decode($responseContent, true);

        // verify that there is a link present
        $this->assertNotEmpty($jsonContent[0]['links']);
        //verify that the correct link is there
        $this->assertEquals('https://google.com', $jsonContent[0]['links']);
    }

    /**
     * @return void
     * Test that when there is a list of events with links,
     * the correct link is associated with the correct event. This includes events with no links.
     */
    public function testExternalLinksMatchEventsInEventList(): void
    {
        $this->loadFixtures([new LinkFixtures()]);

        $eventRepository = $this->entityManager->getRepository(Event::class);
        $jwtManagerMock = $this->createMock(JWTTokenManagerInterface::class);
        $entityManager = $this->createMock(EntityManager::class);
        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $jwtManagerMock, $mailerMock, $entityManager);

        $response = $controller->getEventsWithFilterAndSorter(new \Symfony\Component\HttpFoundation\Request(), $eventRepository);

        // assert that we are getting a JSON response
        $this->assertInstanceOf(JsonResponse::class, $response);

        // get the data out of it
        $data = json_decode($response->getContent(), true);

        // assert that there are 3 events from the fixture
        $this->assertCount(3, $data);

        // verify that the link is correct for each event
        $this->assertEquals('https://google.com', $data[0]['links']);
        $this->assertEquals('https://symfony.com', $data[1]['links']);
        // assert that an event with no link does not have a link
        $this->assertNull($data[2]['links']);

    }
}