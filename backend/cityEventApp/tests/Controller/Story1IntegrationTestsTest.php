<?php

namespace App\Tests\Controller;

use App\Controller\EventController;
use App\Entity\Event;
use App\Tests\tests\DataFixtures\EventFixtureJustOneExpired;
use App\Tests\tests\DataFixtures\EventFixtureOneEvent;
use App\Tests\tests\DataFixtures\EventFixtureExpired;
use App\Tests\tests\DataFixtures\EventFixtureTenEvents;
use App\Tests\tests\DataFixtures\EventFixtureTwoEvent;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class Story1IntegrationTestsTest extends KernelTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected EntityManagerInterface $entityManager;
    private $jwtManager;
    public function setUp(): void
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');

        $kernel = self::bootKernel();

        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');

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
        parent::tearDown();
        $this->entityManager->close();
    }


    private function loadFixtures(array $fixtureInstances): void
    {
        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);

        // Execute the fixture instances
        $executor->execute($fixtureInstances);
    }

    public function testOneEvent()
    {
        $this->loadFixtures([new EventFixtureOneEvent()]);
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $this->jwtManager, $mailerMock, $this->entityManager);
        $response = $controller->getEventsWithFilterAndSorter(new \Symfony\Component\HttpFoundation\Request(), $eventRepository);

        // check that it is a json
        $this->assertInstanceOf(JsonResponse::class, $response);

        // need to decode
        $data = json_decode($response->getContent(), true);

        // Check that there is only one
        $this->assertCount(1, $data);

        //verify the title
        $this->assertEquals('Test 1', $data[0]['title']);
        $this->assertEquals('Description 1', $data[0]['description']);
        $this->assertEquals('Prince Albert', $data[0]['location']);
    }

    public function testTwoEvents()
    {
        $this->loadFixtures([new EventFixtureTwoEvent()]);
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $this->jwtManager, $mailerMock, $this->entityManager);
        $response = $controller->getEventsWithFilterAndSorter(new \Symfony\Component\HttpFoundation\Request(), $eventRepository);

        // check that it is a json
        $this->assertInstanceOf(JsonResponse::class, $response);

        // need to decode
        $data = json_decode($response->getContent(), true);

        // Check that there is 2
        $this->assertCount(2, $data);

        //verify the titles
        $this->assertEquals('Test 1', $data[0]['title']);
        $this->assertEquals('Description 1', $data[0]['description']);
        $this->assertEquals('Estevan', $data[0]['location']);

        $this->assertEquals('Test 2', $data[1]['title']);
        $this->assertEquals('Description 2', $data[1]['description']);
        $this->assertEquals('Yorkton', $data[1]['location']);
    }

    public function testTenEvents()
    {
        $this->loadFixtures([new EventFixtureTenEvents()]);
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $this->jwtManager, $mailerMock, $this->entityManager);
        $response = $controller->getEventsWithFilterAndSorter(new \Symfony\Component\HttpFoundation\Request(), $eventRepository);

        // check that it is a json
        $this->assertInstanceOf(JsonResponse::class, $response);

        // need to decode
        $data = json_decode($response->getContent(), true);

        // Check that there is 10
        $this->assertCount(10, $data);

        //verify the titles
        $this->assertEquals('Test 1', $data[0]['title']);
        $this->assertEquals('Description 1', $data[0]['description']);
        $this->assertEquals('Regina', $data[0]['location']);

        $this->assertEquals('Test 2', $data[1]['title']);
        $this->assertEquals('Test 3', $data[2]['title']);
        $this->assertEquals('Test 4', $data[3]['title']);
        $this->assertEquals('Test 5', $data[4]['title']);
        $this->assertEquals('Test 6', $data[5]['title']);
        $this->assertEquals('Test 7', $data[6]['title']);
        $this->assertEquals('Test 8', $data[7]['title']);
        $this->assertEquals('Test 9', $data[8]['title']);
        $this->assertEquals('Test 10', $data[9]['title']);
    }

    public function testExpiredEvents()
    {
        $this->loadFixtures([new EventFixtureExpired()]);
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $this->jwtManager, $mailerMock, $this->entityManager);
        $response = $controller->getEventsWithFilterAndSorter(new \Symfony\Component\HttpFoundation\Request(), $eventRepository);

        // check that it is a json
        $this->assertInstanceOf(JsonResponse::class, $response);

        // need to decode
        $data = json_decode($response->getContent(), true);

        // Check that there is only 9 (there are 10 in the database but one is expired)
        $this->assertCount(9, $data);

        //verify the titles
        $this->assertEquals('Test 1', $data[0]['title']);
        $this->assertEquals('Description 1', $data[0]['description']);
        $this->assertEquals('Saskatoon', $data[0]['location']);
        $this->assertEquals('Test 2', $data[1]['title']);
        $this->assertEquals('Test 3', $data[2]['title']);
        $this->assertEquals('Test 4', $data[3]['title']);
        $this->assertEquals('Test 5', $data[4]['title']);
        $this->assertEquals('Test 6', $data[5]['title']);
        $this->assertEquals('Test 7', $data[6]['title']);
        $this->assertEquals('Test 8', $data[7]['title']);
        $this->assertEquals('Test 9', $data[8]['title']);

//        $this->assertNotEquals('Expired Event', $data[9]['title']);
    }

    public function testOneExpiredEvent()
    {
        $this->loadFixtures([new EventFixtureJustOneExpired()]);
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $controller = new EventController($eventRepository, $this->jwtManager, $mailerMock, $this->entityManager);
        $response = $controller->getEventsWithFilterAndSorter(new \Symfony\Component\HttpFoundation\Request(), $eventRepository);

        // check that it is a json
        $this->assertInstanceOf(JsonResponse::class, $response);

        // need to decode
        $data = json_decode($response->getContent(), true);

        // Check that the expired event is not included
        $this->assertCount(0, $data);

        //verify the title
        /*        $this->assertNotEquals('Expired', $data[0]['title']);
                $this->assertNotEquals('Description 1', $data[0]['description']);
                $this->assertNotEquals('Location 1', $data[0]['location']);
                $this->assertNotEquals('Audience 1', $data[0]['audience']);*/
    }

}
