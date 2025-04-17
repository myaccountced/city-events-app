<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Banned;
use App\Entity\BookmarkedEvent;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Media;
use App\Entity\User;
use App\Enum\NotificationMethods;
use App\Enum\NotificationTimings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class NotificationPreferenceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        date_default_timezone_set('Canada/Central');

        $nUserS15 = $manager->getRepository(User::class)->findOneBy(['username' => 'nUserS15']);
        $pUserS15 = $manager->getRepository(User::class)->findOneBy(['username' => 'pUserS15']);
        if (!$nUserS15 || !$pUserS15) {
            throw new \RuntimeException('Required user nUserS15 or pUserS15 not found');
        }

        $pUserS15->setWantsNotifications(true);
        $pUserS15->setNotificationMethods([NotificationMethods::EMAIL]);
        $pUserS15->setNotificationTimes([NotificationTimings::DAY0_BEFORE,NotificationTimings::DAY1_BEFORE,NotificationTimings::DAY7_BEFORE]); // Simulating that user wants multiple notification types

        $nUserS15->setWantsNotifications(true);
        $nUserS15->setNotificationMethods([NotificationMethods::EMAIL]);
        $nUserS15->setNotificationTimes([NotificationTimings::DAY1_BEFORE]);

        $eventData = [
            [
                'title' => "Event Today",
                'description' => 'Description A',
                'location' => 'Moose Jaw',
                'startDate' => new \DateTime('today'),
                'endDate' => new \DateTime('+7 days'),
                'audience' => 'General',
                'category' => 'Others',
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('14:00:00'),
                'links' => 'https://google.com',
                'creator' => 'nUserS15',
//                'moderatorApproval' => false
            ],
            [
                'title' => "Event Tomorrow",
                'description' => 'Description B',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('+1 days'),
                'endDate' => new \DateTime('+7 days'),
                'audience' => 'General',
                'category' => 'Others',
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('14:00:00'),
                'links' => 'https://google.com',
                'creator' => 'nUserS15',
//                'moderatorApproval' => false

            ],
            [
                'title' => "Event in 7 Days",
                'description' => 'Description C',
                'location' => 'Regina',
                'startDate' => new \DateTime('+7 days'),
                'endDate' => new \DateTime('+10 days'),
                'audience' => 'General',
                'category' => 'Others',
                'startTime' => new \DateTime('10:00:00'),
                'endTime' => new \DateTime('18:00:00'),
                'links' => 'https://google.com',
                'creator' => 'nUserS15',
//                'moderatorApproval' => false

            ]
        ];

        // Loop through the data and create each event
        foreach ($eventData as $data) {
            $event = new Event();
            $event->setUserId($nUserS15);
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

            $event->setEventLink($data['links']);
            $event->setEventCreator($data['creator']);
            $event->setModeratorApproval(true);

            // Add bookmarked events
            $bookmark = new BookmarkedEvent();
            $bookmark->setUser($pUserS15);
            $bookmark->setEvent($event);

            $manager->persist($event);
            $manager->persist($eventCat);
            $manager->persist($bookmark);
        }

        // Event 4: Happens in 3 days (User 2 should NOT be notified)
        $event4 = new Event();
        $event4->setUserId($pUserS15);
        $event4->setEventTitle('Event in 3 Days');
        $event4->setEventStartDate(new \DateTime('+3 days'));
        $event4->setEventDescription('Event in 3 Days');
        $event4->setEventLocation('Saskatoon');
        $event4->setEventEndDate(new \DateTime('+7 days'));
        $event4->setEventAudience('General');

        $eventCat4 = new Category();
        $eventCat4->setCategoryName('Others');
        $event4->addCategory($eventCat4);

        $event4->setEventLink('https://google.com');
        $event4->setEventCreator('pUserS15');
        $event4->setModeratorApproval(true);

        $bookmark4 = new BookmarkedEvent();
        $bookmark4->setUser($nUserS15);
        $bookmark4->setEvent($event4);

        $manager->persist($event4);
        $manager->persist($eventCat4);
        $manager->persist($bookmark4);

        $bookmark5 = new BookmarkedEvent();
        $bookmark5->setUser($pUserS15);
        $bookmark5->setEvent($event4);


        $manager->flush();

    }

    public function getDependencies(): array
    {
        // TODO: Implement getDependencies() method.
        return [
            AppUserFixture::class,
        ];
    }
}