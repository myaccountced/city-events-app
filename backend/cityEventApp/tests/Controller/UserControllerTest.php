<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private $client;
    protected function setUp(): void
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $this->client = static::createClient();
    }

    public function testGetPreferencesWithValidToken(): void
    {
        // Getting the new user's token
        $payload = [ 'identifier' => 'pUserS15', 'password' => '@Password1'];
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


        $this->client->request('GET', '/api/user/notification-preferences', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('wants_notifications', $data);
        $this->assertArrayHasKey('notification_methods', $data);
        $this->assertArrayHasKey('notification_time', $data);
        print_r($data);
        $this->assertEquals(1,$data['wants_notifications']);
        $this->assertEquals(["email"],$data['notification_methods']);
        $this->assertEquals(["day0","day1","day7"],$data['notification_time']);
    }

    public function testGetPreferencesWithInvalidToken(): void
    {
        $this->client->request('GET', '/api/user/notification-preferences', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token'
        ]);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testUpdatePreferencesWithInvalidToken(): void
    {
        $this->client->request('POST', '/api/user/save-notification-preferences', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token',
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'wants_notifications' => true,
            'notification_methods' => ['email'],
            'notification_time' => ['day0','day1','day7']
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testUpdatePreferencesWithValidToken(): void
    {
        // Getting the new user's token
        $payload = [ 'identifier' => 'pUserS15', 'password' => '@Password1'];
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

        $this->client->request('POST', '/api/user/save-notification-preferences', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'wants_notifications' => true,
            'notification_methods' => ['email'],
            'notification_time' => ['day0','day1','day7']
        ]));


        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['wants_notifications']);
        $this->assertEquals(['email'], $data['notification_methods']);
        $this->assertEquals(['day0','day1','day7'], $data['notification_time']);
    }

    public function testUpdatePreferencesWithValidTokenMissingField1(): void
    {
        // Getting the new user's token
        $payload = [ 'identifier' => 'pUserS15', 'password' => '@Password1'];
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

        $this->client->request('POST', '/api/user/save-notification-preferences', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'notification_methods' => ['email'],
            'notification_time' => ['day0','day1','day7']
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Missing required field: wants_notifications', $data['error']);
    }

    public function testUpdatePreferencesWithValidTokenMissingField2(): void
    {
        // Getting the new user's token
        $payload = [ 'identifier' => 'pUserS15', 'password' => '@Password1'];
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

        $this->client->request('POST', '/api/user/save-notification-preferences', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'wants_notifications' => true,
            'notification_time' => ['day0','day1','day7']
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Missing required field: notification_methods', $data['error']);
    }

    public function testUpdatePreferencesWithValidTokenMissingField3(): void
    {
        // Getting the new user's token
        $payload = [ 'identifier' => 'pUserS15', 'password' => '@Password1'];
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

        $this->client->request('POST', '/api/user/save-notification-preferences', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $authToken,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'wants_notifications' => true,
            'notification_methods' => ['email'],
        ]));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Missing required field: notification_time', $data['error']);
    }
}