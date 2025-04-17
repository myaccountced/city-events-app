<?php

namespace App\Tests\tests\DataFixtures;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class EventFixtureSixEvents extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        date_default_timezone_set('Canada/Central');

        $user = $manager->getRepository(User::class)->findOneBy(['username' => 'Moderator2']);
        if (!$user) {
            throw new \RuntimeException('Required user "Moderator2" not found');
        }

// Event data with six events
        $eventData = [
            [
                'title' => "Event Title 1",
                'description' => 'Description A',
                'location' => 'Moose Jaw',
                'startDate' => new \DateTime('2025-10-01'),
                'endDate' => new \DateTime('2025-10-01'),
                'audience' => 'General',
                'category' => 'Art',
                'images' => ['p1.jpg','2.jpg','1.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('14:00:00'),
                'links' => 'https://google.com',
                'creator' => 'Moderator2',
                'moderatorApproval' => false
            ],
            [
                'title' => "Event Title 2",
                'description' => 'Description B',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2025-11-01'),
                'endDate' => new \DateTime('2025-11-01'),
                'audience' => 'General',
                'category' => 'Art',
                'images' => ['p1.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('14:00:00'),
                'links' => 'https://google.com',
                'creator' => 'Moderator2',
                'moderatorApproval' => true

            ],
            [
                'title' => "Event Title 3",
                'description' => 'Description C',
                'location' => 'Regina',
                'startDate' => new \DateTime('2025-11-01'),
                'endDate' => new \DateTime('2025-11-01'),
                'audience' => 'General',
                'category' => 'Art',
                'images' => ['p1.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('18:00:00'),
                'links' => 'https://google.com',
                'creator' => 'username2'
//                'moderatorApproval' => false

            ],
            [
                'title' => "Event Title 4",
                'description' => 'Description D',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2025-01-01'),
                'endDate' => new \DateTime('2025-01-01'),
                'audience' => 'General',
                'category' => 'Art',
                'images' => ['p1.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('18:00:00'),
                'links' => 'https://google.com',
                'creator' => 'username2'
//                'moderatorApproval' => false
            ],
            [
                'title' => "Event Title 5",
                'description' => 'Description E',
                'location' => 'Regina',
                'startDate' => new \DateTime('2025-01-02'),
                'endDate' => new \DateTime('2025-01-02'),
                'audience' => 'General',
                'category' => 'Art',
                'images' => ['p1.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('16:00:00'),
                'links' => 'https://google.com',
                'creator' => 'username2'
//                'moderatorApproval' => false

            ],
            [
                'title' => "Event Title 6",
                'description' => 'Description E',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2025-01-02'),
                'endDate' => new \DateTime('2025-01-02'),
                'audience' => 'General',
                'category' => 'Art',
                'images' => ['p1.jpg'],
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('16:00:00'),
                'links' => 'https://google.com',
                'creator' => 'username2'
//                'moderatorApproval' => false
            ],
        ];

        // Loop through the data and create each event
        foreach ($eventData as $data) {
            $event = new Event();
            $event->setEventTitle($data['title']);
            $event->setEventDescription($data['description']);
            $event->setEventLocation($data['location']);
            $event->setEventStartDate($data['startDate']);
            $event->setEventEndDate($data['endDate']);
            $event->setEventAudience($data['audience']);

            $eventCat = new Category();
            $eventCat->setCategoryName($data['category']);
            $event->addCategory($eventCat);
            //$event->setEventCategory($data['category']);

            $event->setEventImages(count($data['images']));
            $event->setEventLink($data['links']);
//            $event->setEventCreator($user->getUsername());
            $event->setEventCreator($data['creator']);
            $event->setUserId($data['userId'] ?? null);
            $event->setModeratorApproval($data['moderatorApproval'] ?? true);

            // Add media (images) to the event
            foreach ($data['images'] as $imagePath) {
                $media = new Media();
                $media->setPath($imagePath);
                $media->setEvent($event); // Link the media to the event
                $manager->persist($media);
                $event->addMedia($media); // Add the media to the event's media collection
            }

            $event->setUserId($user);
            $manager->persist($event);
            $manager->persist($eventCat);
            $manager->flush();

        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppUserFixture::class,
        ];
    }
}
