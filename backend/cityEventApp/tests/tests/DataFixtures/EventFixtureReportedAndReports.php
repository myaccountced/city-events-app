<?php

namespace App\Tests\tests\DataFixtures;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Media;
use App\Entity\Report;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtureReportedAndReports extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $this->loadReportedEvents($manager);
        $this->loadReports($manager);
        $this->loadMedia($manager);

    }

    private function loadReportedEvents(ObjectManager $manager): void
    {
        $creator = $manager->getRepository(User::class)->findOneBy(['username' => 'creator']);

        // the 'creator' does not exist?
        if (!$creator) {
            // Password SHOULD be @Password1
            $creatorData = [
                'username' => 'creator',
                'email' => 'creator@example.com',
                'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
                'roles' => ['ROLE_REGISTERED']
            ];

            // Making the creator
            $creator = new User();
            $creator->setUsername($creatorData['username']);
            $creator->setEmail($creatorData['email']);
            $creator->setPassword($creatorData['password']);
            $creator->setRoles($creatorData['roles']);

            $manager->persist($creator);
        }

        date_default_timezone_set('Canada/Central');

        // Array of reported events data
        $eventsData = [
            [
                'title' => 'RepEvent1',
                'description' => 'Description of the event',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2025-12-13 12:00:00'),
                'endDate' => new \DateTime('2025-12-20 12:00:00'),
                'category' => 'Sports',
                'creator' => 'creator',
                'audience' => 'Youth',
                'links' => 'Link',
                'eventImages' => '1',
            ],
            [
                'title' => 'RepEvent3',
                'description' => 'Description of the event',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2025-12-12 12:00:00'),
                'endDate' => new \DateTime('2025-12-20 12:00:00'),
                'category' => 'Sports',
                'creator' => 'creator',
                'audience' => 'Youth',
                'links' => 'Link',
                'eventImages' => '2',
            ],
            [
                'title' => 'RepEvent2',
                'description' => 'Description of the event',
                'location' => 'Saskatoon',
                'startDate' => new \DateTime('2025-12-12 12:00:00'),
                'endDate' => new \DateTime('2025-12-20 12:00:00'),
                'category' => 'Sports',
                'creator' => 'creator',
                'audience' => 'Youth',
                'links' => 'Link',
                'eventImages' => '3',
            ]
        ];

        foreach ($eventsData as $eventData) {
            $event = new Event();
            $event->setEventTitle($eventData['title']);
            $event->setEventDescription($eventData['description']);
            $event->setEventLocation($eventData['location']);
            $event->setEventStartDate($eventData['startDate']);
            $event->setEventEndDate($eventData['endDate']);

            $eventCat = new Category();
            $eventCat->setCategoryName("Arts and Culture");
            $event->addCategory($eventCat);
            //$event->setEventCategory("Arts and Culture");

            $event->setEventCreator($eventData['creator']);
            $event->setEventAudience($eventData['audience']);
            $event->setEventLink($eventData['links']);
            $event->setEventImages($eventData['eventImages']);
            $event->setModeratorApproval(true);

            $event->setUserId($creator);

            $event->setReportCount(3);
            $manager->persist($event);
            $manager->persist($eventCat);
        }

        $manager->flush();
    }

    private function loadReports(ObjectManager $manager): void
    {
        date_default_timezone_set('Canada/Central');

        $eventRepEvent1 = $manager->getRepository(Event::class)->findOneBy(['eventTitle' => 'RepEvent1']);
        $eventRepEvent3 = $manager->getRepository(Event::class)->findOneBy(['eventTitle' => 'RepEvent3']);
        $eventRepEvent2 = $manager->getRepository(Event::class)->findOneBy(['eventTitle' => 'RepEvent2']);

        // Array of reports data
        $reportsDataRepEvent1 = [
            [
                'reportDate' => '2025-12-01',
                'reportTime' => '12:00:00',
                'reason' => 'False Information',
            ],
            [
                'reportDate' => '2025-12-02',
                'reportTime' => '12:00:00',
                'reason' => 'This hurts people',
            ],
            [
                'reportDate' => '2025-12-03',
                'reportTime' => '12:00:00',
                'reason' => 'Harassment or abuse',
            ]
        ];
        $reportsDataRepEvent2 = [
            [
                'reportDate' => '2025-12-01',
                'reportTime' => '12:00:00',
                'reason' => 'Spam',
            ],
            [
                'reportDate' => '2025-12-02',
                'reportTime' => '12:00:00',
                'reason' => 'Illegal activity',
            ],
            [
                'reportDate' => '2025-12-03',
                'reportTime' => '12:00:00',
                'reason' => 'Hurts my pride',
            ]
        ];

        $reportsDataRepEvent3 = [
            [
                'reportDate' => '2025-12-01',
                'reportTime' => '12:00:00',
                'reason' => 'Spam',
            ],
            [
                'reportDate' => '2025-12-02',
                'reportTime' => '12:00:00',
                'reason' => 'Misleading location or time',
            ],
            [
                'reportDate' => '2025-12-03',
                'reportTime' => '12:00:00',
                'reason' => 'Misleading location or time',
            ]
        ];

        foreach ($reportsDataRepEvent1 as $reportData) {
            $report = new Report();
            $report->setEventID($eventRepEvent1->getId());
            // Set the report date and time
            $reportDate = new \DateTime($reportData['reportDate']);
            $reportTime = \DateTime::createFromFormat('H:i:s', $reportData['reportTime']);
            $report->setReportDate($reportDate);
            $report->setReportTime($reportTime);
            $report->setReason($reportData['reason']);
            // Persist the report
            $manager->persist($report);
        }

        foreach ($reportsDataRepEvent3 as $reportData) {
            $report = new Report();
            $report->setEventID($eventRepEvent3->getId());
            // Set the report date and time
            $reportDate = new \DateTime($reportData['reportDate']);
            $reportTime = \DateTime::createFromFormat('H:i:s', $reportData['reportTime']);
            $report->setReportDate($reportDate);
            $report->setReportTime($reportTime);
            $report->setReason($reportData['reason']);
            // Persist the report
            $manager->persist($report);
        }

        foreach ($reportsDataRepEvent2 as $reportData) {
            $report = new Report();
            $report->setEventID($eventRepEvent2->getId());
            // Set the report date and time
            $reportDate = new \DateTime($reportData['reportDate']);
            $reportTime = \DateTime::createFromFormat('H:i:s', $reportData['reportTime']);
            $report->setReportDate($reportDate);
            $report->setReportTime($reportTime);
            $report->setReason($reportData['reason']);
            // Persist the report
            $manager->persist($report);
        }

        $manager->flush();
    }

    private function loadMedia(ObjectManager $manager): void
    {
        // Define the events and media paths
        $events = [
            'RepEvent1' => 1,
            'RepEvent3' => 2,
            'RepEvent2' => 3
        ];
        $mediaPath = "chilidisaster.jpg";

        foreach ($events as $eventTitle => $mediaCount) {
            $event = $manager->getRepository(Event::class)->findOneBy(['eventTitle' => $eventTitle]);

            for ($i = 1; $i <= $mediaCount; $i++) {
                $media = new Media();
                $media->setEvent($event);
                $media->setPath($mediaPath);
                $manager->persist($media);
            }
            $manager->flush();
        }
    }
}
