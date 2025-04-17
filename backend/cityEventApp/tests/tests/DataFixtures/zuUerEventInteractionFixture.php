<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Event;
use App\Entity\EventInteraction;
use App\Enum\EventInteractionStatus;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use App\Tests\tests\DataFixtures\AppUserFixture;
use App\Tests\tests\DataFixtures\LinkFixtures;


class zuUerEventInteractionFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get repositories for Entity lookups
        $eventRepository = $manager->getRepository(Event::class);
        $userRepository = $manager->getRepository(User::class);

        $event75 = $this->getReference('event-75', Event::class);
        $event77 = $this->getReference('event-77', Event::class);

        $user2 = $this->getReference('user-2', User::class);
        $user4 = $this->getReference('user-4', User::class);
        $user5 = $this->getReference('user-5', User::class);
        $user6 = $this->getReference('user-6', User::class);

//        dump('Created User ID: ' . $user2->getId());

        if (!$event75) {
            throw new \RuntimeException('Event 75 not found');
        }
        if (!$event77) {
            throw new \RuntimeException('Event 77 not found');
        }
        if (!$user2) {
            throw new \RuntimeException('User 2 not found');
        }
        if (!$user4) {
            throw new \RuntimeException('User 4 not found');
        }
        if (!$user5) {
            throw new \RuntimeException('User 5 not found');
        }
        if (!$user6) {
            throw new \RuntimeException('User 6 not found');
        }
        // Create Event Interaction 1 for Event 75, User 2, Status: INTERESTED
        $ei1 = new EventInteraction();
        $ei1->setEvent($event75);
        $ei1->setUser($user2);
        $ei1->setStatus(EventInteractionStatus::INTERESTED);
        $manager->persist($ei1);

        // Create Event Interaction 2 for Event 75, User 5, Status: INTERESTED
        $ei2 = new EventInteraction();
        $ei2->setEvent($event75);
        $ei2->setUser($user5);
        $ei2->setStatus(EventInteractionStatus::INTERESTED);
        $manager->persist($ei2);

        // Create Event Interaction 3 for Event 75, User 4, Status: ATTENDING
        $ei3 = new EventInteraction();
        $ei3->setEvent($event75);
        $ei3->setUser($user4);
        $ei3->setStatus(EventInteractionStatus::ATTENDING);
        $manager->persist($ei3);

        // Create Event Interaction 4 for Event 75, User 6, Status: ATTENDING
        $ei4 = new EventInteraction();
        $ei4->setEvent($event75);
        $ei4->setUser($user6);
        $ei4->setStatus(EventInteractionStatus::ATTENDING);
        $manager->persist($ei4);

        // Create Event Interaction 5 for Event 77, User 5, Status: INTERESTED
        $ei5 = new EventInteraction();
        $ei5->setEvent($event77);
        $ei5->setUser($user5);
        $ei5->setStatus(EventInteractionStatus::INTERESTED);
        $manager->persist($ei5);

        // Create Event Interaction 6 for Event 77, User 6, Status: ATTENDING
        $ei6 = new EventInteraction();
        $ei6->setEvent($event77);
        $ei6->setUser($user6);
        $ei6->setStatus(EventInteractionStatus::ATTENDING);
        $manager->persist($ei6);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppUserFixture::class,
            LinkFixtures::class,
        ];
    }
}