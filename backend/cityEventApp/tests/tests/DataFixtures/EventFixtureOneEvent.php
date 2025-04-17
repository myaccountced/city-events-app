<?php

namespace App\Tests\tests\DataFixtures;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtureOneEvent extends Fixture
{
    public function load(ObjectManager $manager): void
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
        $event = new Event();
        $event->setEventTitle('Test 1');
        $event->setEventDescription('Description 1');
        $event->setEventLocation('Prince Albert');
        $event->setEventStartDate(new \DateTime('2026-01-01 12:00:00'));
        $event->setEventEndDate(new \DateTime('2026-01-01 17:00:00'));
        $event->setEventAudience('Family Friendly');

        $eventCat = new Category();
        $eventCat->setCategoryName("Sports");
        $event->addCategory($eventCat);

        $eventCat2 = new Category();
        $eventCat2->setCategoryName("Food and Drink");
        $event->addCategory($eventCat2);

        $event->setEventLink('Links');
        $event->setEventCreator('creator');
        $event->setModeratorApproval('true');

        $event->setUserId($creator);

        $manager->persist($event);
        $manager->persist($eventCat);
        $manager->persist($eventCat2);

        $manager->flush();
    }
}
