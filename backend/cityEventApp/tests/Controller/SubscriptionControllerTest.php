<?php

namespace App\Tests\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubscriptionControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Load environment variables
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');

        $this->client = static::createClient();
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testCreateOneMonthSubscriptionSuccess(): void
    {
        // Fetch an existing user from the database by username
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'nUserS15']);

        $this->assertNotNull($user, 'The test user must exist in the database.');

        // Verify current of user do not have subscription
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findRecentActiveSubscription($user->getId());
        $this->assertNull($subscriptions, 'This user should not have valid subscription');

        // Test creating a subscription
        $this->client->request('POST', '/api/subscription', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $user->getUsername(),
            'selectedPlan' => 1, // 1-Month plan
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Subscription created successfully', $response['message']);
        $this->assertTrue($response['isPremium']);
        $this->assertArrayHasKey('expireDate', $response);

        // Verify subscription in database
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findBy(['userId' => $user->getId()]);
        $this->assertCount(1, $subscriptions);
        //$this->assertEquals(new \DateTimeImmutable('+30 days'), $subscriptions[0]->getExpireDate());

        $expireDate = $subscriptions[0]->getExpireDate();
        $expectedExpireDate = new \DateTimeImmutable('+30 days');

        // Calculate the difference in days
        $differenceInDays = $expectedExpireDate->diff($expireDate)->days;

        // Assert that the difference is 0 (i.e., they are 30 days apart as expected)
        $this->assertEquals(0, $differenceInDays, 'The subscription expiration date should be 30 days from now.');
    }

    public function testCreateOneYearSubscriptionSuccess(): void
    {
        // Fetch an existing user from the database by username
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'nUserS15']);

        $this->assertNotNull($user, 'The test user must exist in the database.');

        // Verify current of user do not have subscription
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findRecentActiveSubscription($user->getId());
        $this->assertNull($subscriptions, 'This user should not have valid subscription');

        // Test creating a subscription with 1-Year plan
        $this->client->request('POST', '/api/subscription', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $user->getUsername(),
            'selectedPlan' => 2, // 1-Year plan
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Subscription created successfully', $response['message']);
        $this->assertTrue($response['isPremium']);
        $this->assertArrayHasKey('expireDate', $response);

        // Verify subscription in database
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findBy(['userId' => $user->getId()]);
        $this->assertCount(1, $subscriptions);

        $expireDate = $subscriptions[0]->getExpireDate();
        $expectedExpireDate = new \DateTimeImmutable('+365 days');

        // Calculate the difference in days
        $differenceInDays = $expectedExpireDate->diff($expireDate)->days;

        // Assert that the difference is 0 (i.e., they are 365 days apart as expected)
        $this->assertEquals(0, $differenceInDays, 'The subscription expiration date should be 365 days from now.');
    }

    public function testUpgradeSubscription1MonthSuccess(): void
    {
        // Fetch an existing user from the database by username
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'pUserS15']);

        // Assert user exist
        $this->assertNotNull($user, 'The test user must exist in the database.');

        // Verify current subscription of user in database
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findRecentActiveSubscription($user->getId());
        $this->assertNotNull($subscriptions, 'This user should have valid subscription');


        $oldExpireDate = $subscriptions->getExpireDate(); // keep track of the subscription data

        // Test upgrading the subscription to 1-Year plan
        $this->client->request('POST', '/api/subscription', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $user->getUsername(),
            'selectedPlan' => 1, // 1-Month plan
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Subscription created successfully', $response['message']);
        $this->assertTrue($response['isPremium']);
        $this->assertArrayHasKey('expireDate', $response);

        // Verify subscription in database
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findBy(['userId' => $user->getId()], ['expireDate' => 'DESC']);
        $this->assertCount(2, $subscriptions);

        $newExpireDate = $subscriptions[0]->getExpireDate();
        $expectedExpireDate = ($oldExpireDate->add(new \DateInterval('P30D')));

        // Calculate the difference in days
        $differenceInDays = $expectedExpireDate->diff($newExpireDate)->days;

        // Assert that the difference is 0 (i.e., they are 395 days apart as expected)
        $this->assertEquals(0, $differenceInDays, 'The subscription expiration date should be 395 days from now.');
    }

    public function testUpgradeSubscription1YearSuccess(): void
    {
        // Fetch an existing user from the database by username
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'pUserS15']);

        // Assert user exist
        $this->assertNotNull($user, 'The test user must exist in the database.');

        // Verify current subscription of user in database
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findRecentActiveSubscription($user->getId());
        $this->assertNotNull($subscriptions, 'This user should have valid subscription');

        $oldExpireDate = $subscriptions->getExpireDate(); // keep track of the subscription data

        // Test upgrading the subscription to 1-Year plan
        $this->client->request('POST', '/api/subscription', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => $user->getUsername(),
            'selectedPlan' => 2, // 1-Year plan
        ]));

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Subscription created successfully', $response['message']);
        $this->assertTrue($response['isPremium']);
        $this->assertArrayHasKey('expireDate', $response);

        // Verify subscription in database
        $subscriptions = $this->entityManager->getRepository(Subscription::class)->findBy(['userId' => $user->getId()], ['expireDate' => 'DESC']);
        $this->assertCount(2, $subscriptions);

        $newExpireDate = $subscriptions[0]->getExpireDate();
        $expectedExpireDate = ($oldExpireDate->add(new \DateInterval('P365D')));

        // Calculate the difference in days
        $differenceInDays = $expectedExpireDate->diff($newExpireDate)->days;

        // Assert that the difference is 0 (i.e., they are 395 days apart as expected)
        $this->assertEquals(0, $differenceInDays, 'The subscription expiration date should be 395 days from now.');
    }

    public function testGetSubscriptionStatusSuccess(): void
    {

        // Test fetching subscription status
        $this->client->request('GET', '/api/subscription/pUserS15');
        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Subscription found', $response['message']);
        $this->assertTrue($response['isPremium']);
    }

    public function testGetSubscriptionStatusNotFound(): void
    {
        //$client = static::createClient();

        // Test fetching subscription status for non-existent user
        $this->client->request('GET', '/api/subscription/unknownuser');
        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('User not found', $response['error']);
    }

    public function testCreateSubscriptionMissingUsername(): void
    {
        // Test with missing username
        $this->client->request('POST', '/api/subscription', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'selectedPlan' => 1,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid data', $response['error']);
    }

    public function testCreateSubscriptionWithUnknownUser(): void
    {
        $this->client->request('POST', '/api/subscription', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'usernotexist',
            'selectedPlan' => 1,
        ]));

        $this->assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('User not found', $response['error']);
    }

    public function testCreateSubscriptionWithInvalidPlan(): void
    {
        // Create a test user
        $user = new User();
        $user->setUsername('testuser3');
        $user->setEmail('testuser3@example.com');
        $user->setPassword('@Password3'); // Hash password if required in your setup
        $user->setModerator(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/subscription', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'testuser3',
            'selectedPlan' => 12,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid plan selected', $response['error']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
