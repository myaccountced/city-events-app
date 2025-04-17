<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\GoogleAuthService;
use Firebase\JWT\JWT;
use phpDocumentor\Reflection\Types\Static_;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    private $entityManager;
    private $googleAuthServiceMock;
    public string $tokenFromGG;
    private  $ggId = "117674856037709056408";

    protected function setUp(): void
    {
        parent::setUp();
        // Create the client (boots the kernel)
        $this->client = static::createClient();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        // Ensure the user does not exist before registration
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }

        // Set the environment variable for testing if did not recognize
        putenv('JWT_SECRET_KEY=' . __DIR__ . '/../../private.key');
        putenv('JWT_PUBLIC_KEY=' . __DIR__ . '/../../public.key');
        putenv('JWT_PASSPHRASE=4230b646dcd9879fd35b6596dbc479b8cc8c1f07ceb66b6a2496f8e46ff98a09');

        // from Google response
        // this google id expires every one hour.
        // you have to update it everytime --> sign in with Google with valid info --> F12 --> copy everything under ggId --> replace string below.
        $this->tokenFromGG = "eyJhbGciOiJSUzI1NiIsImtpZCI6ImMzN2RhNzVjOWZiZTE4YzJjZTkxMjViOWFhMWYzMDBkY2IzMWU4ZDkiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLCJhenAiOiI2NjEwNzMwMDgwNi11dDY0cTN2ZHFxaDBrcmIyamtnZHZkdjNuZzY5NGQ0ci5hcHBzLmdvb2dsZXVzZXJjb250ZW50LmNvbSIsImF1ZCI6IjY2MTA3MzAwODA2LXV0NjRxM3ZkcXFoMGtyYjJqa2dkdmR2M25nNjk0ZDRyLmFwcHMuZ29vZ2xldXNlcmNvbnRlbnQuY29tIiwic3ViIjoiMTE3Njc0ODU2MDM3NzA5MDU2NDA4IiwiZW1haWwiOiJ6dWV2ZW50c3Byb2plY3RAZ21haWwuY29tIiwiZW1haWxfdmVyaWZpZWQiOnRydWUsIm5iZiI6MTc0NDc2OTgwNSwibmFtZSI6Inp1ZXZlbnRzIHByb2plY3QiLCJwaWN0dXJlIjoiaHR0cHM6Ly9saDMuZ29vZ2xldXNlcmNvbnRlbnQuY29tL2EvQUNnOG9jSUwwNHV2NWlZMGZOdEp1bXlab1JQZFNVVVZIMHZMR3laNGxrVjdLZlEwTm5xRGdRPXM5Ni1jIiwiZ2l2ZW5fbmFtZSI6Inp1ZXZlbnRzIiwiZmFtaWx5X25hbWUiOiJwcm9qZWN0IiwiaWF0IjoxNzQ0NzcwMTA1LCJleHAiOjE3NDQ3NzM3MDUsImp0aSI6ImZmNzA4NDQ4OTY0YzMwNWY0MjQyMTFmNzBkMTM4Yjk1ZDYyMzgzODUifQ.iuKW-6Ri3KRpuPqZ1YBRq3LE1BcB2yreSNLoKkP4AHUssiA9fPynmRwTJAseoYHPypgIIyON95K3j8qundwK51CaWLRH9RJNuwcYWF2q6sxm8b-7A6QjZRF3NDH7ZQaGgwwyv6u_IT911aWyn0y5x8jhe4RvgZglrmkM44PKPbDC8k6zkdvlr7C3gwh-fwv8ZesNGi_wm7QYP6An2GvaJCgrcMiEo5Ycr64EiqvGOSJeZEI3es72s9qFeH2Dt7ee4QaW7lrEb_jgWBEsXk3kEwl60aAGOJN30gi7IjrmrhO8gaUijEwLutuOqppxQOGOn5xItOImoDS3P-AdaZkY5w";
    }

    public function testGoogleRegisterNewAccount()
    {
        // Assert user does not exist before registration
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->assertNull($user);

        $this->client->request('POST', '/auth/google-login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'idToken' => $this->tokenFromGG
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->assertNotNull($user);
        $this->assertEquals('zuevents project', $user->getUserName());
        $this->assertEquals('zueventsproject@gmail.com', $user->getEmail());
        $this->assertEquals($this->ggId, $user->getGoogleId());
    }

    public function testGoogleLinkToExistingAccount()
    {
        // Create a user in the database
        $user = new User();
        $user->setEmail('zueventsproject@gmail.com');
        $user->setUsername('zuevents project');
        $user->setPassword('********');
//        $user->setGoogleId(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Assert that the user has existed the database before registering
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->assertNotNull($user);
        $this->assertNull($user->getGoogleId());

        $this->client->request('POST', '/auth/google-login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'idToken' => $this->tokenFromGG
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $user2 = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->entityManager->refresh($user2);
        $this->assertNotNull($user2);
        $this->assertEquals('zuevents project', $user2->getUserName());
        $this->assertEquals('zueventsproject@gmail.com', $user2->getEmail());
        $this->assertEquals($this->ggId, $user2->getGoogleId());
    }

    public function testGoogleLoginExistingAccount()
    {
        // Create a user in the database
        $user = new User();
        $user->setEmail('zueventsproject@gmail.com');
        $user->setUsername('zuevents project');
        $user->setPassword('********');
        $user->setGoogleId($this->ggId);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Assert that the user has existed the database before registering
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->assertNotNull($user);
        $this->assertEquals($this->ggId, $user->getGoogleId());

        $this->client->request('POST', '/auth/google-login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'idToken' => $this->tokenFromGG
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->assertNotNull($user);
        $this->assertEquals('zuevents project', $user->getUserName());
        $this->assertEquals('zueventsproject@gmail.com', $user->getEmail());
        $this->assertEquals($this->ggId, $user->getGoogleId());
    }

    public function testGoogleInvalidToken()
    {
        // Assert user does not exist before registration
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->assertNull($user);

        $this->client->request('POST', '/auth/google-login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
//            'idToken' => $this->tokenFromGG
            'idToken' => 'valid-token-id'
        ]));

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'zueventsproject@gmail.com']);
        $this->assertNull($user);
    }
}
