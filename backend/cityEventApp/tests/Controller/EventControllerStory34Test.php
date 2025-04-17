<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EventControllerStory34Test extends WebTestCase
{
    public function testGetEventsWithEmptyFilter()
    {
        $client = static::createClient();
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $client->request('GET', '/eventsWithFilterAndSorter', ['limit' => 10000, 'offset' => 0]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);

        $this->assertCount(1070, $responseData);
    }

    public function testGetEventsWithOneFilterField()
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $client = static::createClient();
        $client->request('GET', '/eventsWithFilterAndSorter', [
            'limit' => 10000,
            'offset' => 0,
            'filter' => ['moderatorApproval' => [1]]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(1047, $responseData);
    }

    public function testGetEventsWithOneCategoryFilter()
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $client = static::createClient();
        $client->request('GET', '/eventsWithFilterAndSorter', [
            'limit' => 10000,
            'offset' => 0,
            'filter' => ['moderatorApproval' => [1],'eventCategory' => ['Sports']]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(125, $responseData);

        foreach ($responseData as $event) {
            $this->assertIsArray($event['category']);
            $this->assertContains('Sports', $event['category']);
        }
    }
    public function testGetEventsWithTwoCategoryFilter()
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $myCategory = ['Food and Drink', 'Sports'];
        $client = static::createClient();
        $client->request('GET', '/eventsWithFilterAndSorter', [
            'limit' => 10000,
            'offset' => 0,
            'filter' => [
                'moderatorApproval' => [1],
                'eventCategory' => $myCategory]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        print_r($responseData);
        // There is now only 1 event with both sports and food and drink as a category, and 2 with all events
        $this->assertCount(3, $responseData);

        foreach ($responseData as $event) {
            $this->assertContains($myCategory[0], $event['category']);
            $this->assertContains($myCategory[1], $event['category']);
        }
    }

    public function testGetEventsWithAllCategoryFilter()
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $myCategory = ['Arts and Culture','Education','Health and Wellness','Food and Drink',
                        'Music','Nature and Outdoors','Sports','Technology','Others'];
        $client = static::createClient();
        $client->request('GET', '/eventsWithFilterAndSorter', [
            'limit' => 10000,
            'offset' => 0,
            'filter' => [
                'moderatorApproval' => [1],
                'eventCategory' => $myCategory],
            'sortField' => 'eventStartDate',
            'sortOrder' => 'ASC'
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);

        // There are only 2 events that have ALL categories
        $this->assertCount(2, $responseData);

        foreach ($responseData as $event) {
            foreach ($myCategory as $index => $category) {
                $this->assertContains($category, $event['category']);
            }
        }
    }
    public function testGetEventsWithFiltersAndSorter()
    {
        (new \Symfony\Component\Dotenv\Dotenv())->loadEnv(dirname(__DIR__, 2) . '/.env.test');
        $myCategory = ['Music', 'Technology','Nature and Outdoors'];
        $client = static::createClient();
        $client->request('GET', '/eventsWithFilterAndSorter', [
            'limit' => 10000,
            'offset' => 0,
            'filter' => [
                'moderatorApproval' => 1,
                'eventCategory' => $myCategory],
            'sortField' => 'eventStartDate',
            'sortOrder' => 'ASC'
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);

        // Only two events have all these categories
        $this->assertCount(2, $responseData);
        foreach ($responseData as $event) {
            foreach ($myCategory as $category) {
                $this->assertContains($category, $event['category']);
            }
        }
    }
}
