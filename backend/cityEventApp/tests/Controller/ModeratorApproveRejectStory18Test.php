<?php

namespace App\Tests\Controller;

use App\Controller\SignInController;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;


class ModeratorApproveRejectStory18Test extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private $jwtManager;
    private JWTEncoderInterface $jwtEncoder;
    public function setUp(): void
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        //self::bootKernel();
        $this->client = static::createClient();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $this->jwtEncoder = self::getContainer()->get(JWTEncoderInterface::class);
    }

    protected static function getKernelClass(): string
    {
        if (!class_exists($class = 'App\Kernel')) {
            throw new \RuntimeException(sprintf('Class "%s" doesn\'t exist or cannot be autoloaded. Check that the KERNEL_CLASS value in phpunit.xml matches the fully-qualified class name of your Kernel or override the "%s::createKernel()" method.', $class, static::class));
        }

        return $class;
    }

    public function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();

    }


    /*public function testModeratorAuthentication()
    {
        // Creating a new user
        $payload = [ 'username' => 'Story18User1', 'password' => 'ABC123def', 'email' => 'story18@test.com'];
        $this->client->request(
            'POST',
            '/api/registration',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Making sure the user is actually NOT a mod
        $newUser = new User();
        $newUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'Story18User1']);

        $newUser->setModerator(false);
        $this->em->persist($newUser);
        $this->em->flush();

        // Getting the new user's token
        $loginPayload = [
            'identifier' => 'Story18User1',
            'password' => 'ABC123def'
        ];
        $this->client->request(
            'POST',
            '/auth/signin',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($loginPayload)
        );

        $res = $this->client->getResponse();
        $data = json_decode($res->getContent(), true);
        // Debugging: Print the response if no token is found
        if (!isset($data['token'])) {
            var_dump($data);
            $this->fail("Authentication response does not contain a token.");
        }

        $authToken = $data['token'];
        //$authToken = $data->token;

        $decodedToken = $this->jwtEncoder->decode($authToken);
        // New user is not a mod
        $this->assertNotContains('MODERATOR', $decodedToken['roles'], "User should not have MODERATOR role initially");


        // Making the user a moderator
        //$newUser = $this->em->getRepository(User::class)->findOneBy(['username' => $payload['identifier']]);
        $newUser->setModerator(true);
        $this->em->persist($newUser);
        $this->em->flush();

        $this->client->request(
            'POST',
            '/auth/signin',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($loginPayload)
        );

        $res = $this->client->getResponse();
        $data = json_decode($res->getContent(), true);
        $newAuthToken = $data['token'] ?? null;

        $this->assertNotNull($newAuthToken, "New authentication token should not be null");

        // Decode the new JWT
        $newDecodedToken = $this->jwtEncoder->decode($newAuthToken);
        $this->assertContains('MODERATOR', $newDecodedToken['roles'], "User should have MODERATOR role after update");
    }*/


    public function testModeratorApproval()
    {
        $userRepository = $this->em->getRepository(User::class);
        $eventRepository = $this->em->getRepository(Event::class);

        // Creating a new user
        $payload = [ 'username' => 'Story18User2', 'password' => 'ABC123def', 'email' => '2story18@test.com'];
        $this->client->request(
            'POST',
            '/api/registration',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Making sure the user is NOT a mod
        $newUser = new User();
        $newUser = $userRepository->findOneBy(['username' => $payload['username']]);
        $newUser->setModerator(false);
        $this->em->persist($newUser);
        $this->em->flush();

        // Getting the new user's token
        $payload = [ 'identifier' => 'Story18User2', 'password' => 'ABC123def'];
        $this->client->request(
            'POST',
            '/auth/signin',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Getting the user's token
        $res = $this->client->getResponse();
        $data = json_decode($res->getContent());
        $authToken = $data->token;

        // Creating an event to be approved
        $newEvent = new Event();
        $newEvent->setEventTitle('Event Title');
        $newEvent->setEventDescription('Description for event');
        $newEvent->setEventLocation('Location');
        $newEvent->setEventStartDate(new \DateTime('2026-01-01'));
        $newEvent->setEventEndDate(new \DateTime('2026-01-01'));
        $newEvent->setEventAudience('Audience');

        $eventCat = new Category();
        $eventCat->setCategoryName('Others');
        $newEvent->addCategory($eventCat);

        $newEvent->setEventImages('images/event.jpg');
        $newEvent->setEventLink('http://example.com/event');
        $newEvent->setEventCreator('creator');
        $newEvent->setModeratorApproval(null);
        $creator = $userRepository->findOneBy(['username' => 'creator']);
        $newEvent->setUserId($creator);

        $this->em->persist($newEvent);
        $this->em->persist($eventCat);
        $this->em->flush();

        // new event SHOULD have null status
        $this->assertNull($newEvent->isModeratorApproval());

        // Requesting to change the event
        $this->client->request(
            'PUT',
            '/events/mod',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken],
            json_encode(['id' => $newEvent->getId(), 'status' => true])
        );

        // Should get an unauthorized response
        $updateRes = $this->client->getResponse();
        $this->assertEquals(403, $updateRes->getStatusCode());

        // The event should NOT be changed
        try
        {
            // ENSURING that the database is up-to-date
            $this->em->refresh($newEvent);
        } catch (ORMException $e)
        {
            print_r($e->getMessage());
        }

        $updatedEvent = $eventRepository->find($newEvent->getId());
        $this->assertNull($updatedEvent->isModeratorApproval());


        // Now updating the user to be a mod
        $newUser = $userRepository->findOneBy(['username' => $payload['identifier']]);
        $newUser->setModerator(true);
        $this->em->persist($newUser);
        $this->em->flush();

        // Sending the moderator's request
        $this->client->request(
            'PUT',
            '/events/mod',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken],
            json_encode(['id' => $newEvent->getId(), 'status' => true])
        );

        // Good response
        $updateRes = $this->client->getResponse();
        $this->assertEquals(200, $updateRes->getStatusCode());
        $returnedEvent = json_decode($updateRes->getContent());

        try
        {
            $this->em->refresh($newEvent);
        } catch (ORMException $e)
        {
            print_r($e->getMessage());
        }

        // Database should have changed
        $updatedEvent = $eventRepository->find($returnedEvent->id);
        $this->assertTrue($updatedEvent->isModeratorApproval());

        // Sending another request for the same event but a different value
        $this->client->request(
            'PUT',
            '/events/mod',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken],
            json_encode(['id' => $newEvent->getId(), 'status' => false])
        );

        // Good response
        $updateRes = $this->client->getResponse();
        $this->assertEquals(200, $updateRes->getStatusCode());
    }


    public function testModeratorReject()
    {
        $userRepository = $this->em->getRepository(User::class);
        $eventRepository = $this->em->getRepository(Event::class);

        // Creating a new user
        $payload = [ 'username' => 'Story18User2', 'password' => 'ABC123def', 'email' => '2story18@test.com'];
        $this->client->request(
            'POST',
            '/api/registration',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Making sure the user is NOT a mod
        $newUser = new User();
        $newUser = $userRepository->findOneBy(['username' => $payload['username']]);
        $newUser->setModerator(false);
        $this->em->persist($newUser);
        $this->em->flush();

        // Getting the new user's token
        $payload = [ 'identifier' => 'Story18User2', 'password' => 'ABC123def'];
        $this->client->request(
            'POST',
            '/auth/signin',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Getting the user's token
        $res = $this->client->getResponse();
        $data = json_decode($res->getContent());
        $authToken = $data->token;

        // Creating an event to be approved
        $newEvent = new Event();
        $newEvent->setEventTitle('Event Title');
        $newEvent->setEventDescription('Description for event');
        $newEvent->setEventLocation('Location');
        $newEvent->setEventStartDate(new \DateTime('2026-01-01'));
        $newEvent->setEventEndDate(new \DateTime('2026-01-01'));
        $newEvent->setEventAudience('Audience');

        $eventCat = new Category();
        $eventCat->setCategoryName("Others");
        $newEvent->addCategory($eventCat);

        $newEvent->setEventImages('images/event.jpg');
        $newEvent->setEventLink('http://example.com/event');
        $newEvent->setEventCreator('Creator ');
        $newEvent->setModeratorApproval(null);

        $this->em->persist($newEvent);
        $this->em->persist($eventCat);
        $this->em->flush();

        // new event SHOULD have null status
        $this->assertNull($newEvent->isModeratorApproval());

        // Requesting to change the event
        $this->client->request(
            'PUT',
            '/events/mod',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken],
            json_encode(['id' => $newEvent->getId(), 'status' => false])
        );

        // Should get an unauthorized response
        $updateRes = $this->client->getResponse();
        $this->assertEquals(403, $updateRes->getStatusCode());

        // The event should NOT be changed
        try
        {
            // ENSURING that the database is up-to-date
            $this->em->refresh($newEvent);
        } catch (ORMException $e)
        {
            print_r($e->getMessage());
        }

        $updatedEvent = $eventRepository->find($newEvent->getId());
        $this->assertNull($updatedEvent->isModeratorApproval());


        // Now updating the user to be a mod
        $newUser = $userRepository->findOneBy(['username' => $payload['identifier']]);
        $newUser->setModerator(true);
        $this->em->persist($newUser);
        $this->em->flush();

        // Sending the moderator's request
        $this->client->request(
            'PUT',
            '/events/mod',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken],
            json_encode(['id' => $newEvent->getId(), 'status' => false])
        );

        // Good response
        $updateRes = $this->client->getResponse();
        $this->assertEquals(200, $updateRes->getStatusCode());
        $returnedEvent = json_decode($updateRes->getContent());

        try
        {
            $this->em->refresh($newEvent);
        } catch (ORMException $e)
        {
            print_r($e->getMessage());
        }
    }

    public function testEventNotExist()
    {
        $userRepository = $this->em->getRepository(User::class);

        // Creating a new user
        $payload = [ 'username' => 'Story18User2', 'password' => 'ABC123def', 'email' => '2story18@test.com'];
        $this->client->request(
            'POST',
            '/api/registration',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Making sure the user is a mod
        $newUser = new User();
        $newUser = $userRepository->findOneBy(['username' => $payload['username']]);
        $newUser->setModerator(true);
        $this->em->persist($newUser);
        $this->em->flush();

        // Getting the new user's token
        $payload = [ 'identifier' => 'Story18User2', 'password' => 'ABC123def'];
        $this->client->request(
            'POST',
            '/auth/signin',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Getting the user's token
        $res = $this->client->getResponse();
        $data = json_decode($res->getContent());
        $authToken = $data->token;

        // Now sending a request to change an event that does not exist (-10 shouldn't exist!)
        $this->client->request(
            'PUT',
            '/events/mod',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken],
            json_encode(['id' => '-10', 'status' => true])
        );

        // Should get an unauthorized response
        $updateRes = $this->client->getResponse();
        $this->assertEquals(404, $updateRes->getStatusCode());
    }
}
