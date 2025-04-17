<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventWithMedia extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        $creator = $manager->getRepository(User::class)->findOneBy(['username' => 'username1']);

        // the 'creator' does not exist?
        if (!$creator) {
            // Password SHOULD be @Password1
            $creatorData = [
                'username' => 'username1',
                'email' => 'username1@example.com',
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
        $event1 = new Event();
        $event1->setEventTitle('Chili Cook-Off');
        $event1->setEventDescription('Bring some really good chili and a positive attitude to this cooking competition!');
        $event1->setEventLocation('Moose Jaw');
        $event1->setEventStartDate(new \DateTime('2025-07-08 12:00:00'));
        $event1->setEventEndDate(new \DateTime('2025-07-08 17:00:00'));
        $event1->setEventAudience('Family Friendly');

        $eventCat1 = new Category();
        $eventCat1->setCategoryName("Food and Drink");
        $event1->addCategory($eventCat1);

        $event1->setEventImages(3);
        $event1->setEventLink('https://www.chilis.com/');
        $event1->setEventCreator('username1');
        $event1->setModeratorApproval('true');

        $event1->setUserId($creator);

        $manager->persist($event1);
        $manager->persist($eventCat1);

        $manager->flush();


        date_default_timezone_set('Canada/Central');
        $event2 = new Event();
        $event2->setEventTitle('Dance Competition');
        $event2->setEventDescription('Dance till you drop in this annual competition!');
        $event2->setEventLocation('Lloydminster');
        $event2->setEventStartDate(new \DateTime('2025-08-06 12:00:00'));
        $event2->setEventEndDate(new \DateTime('2025-08-06 19:00:00'));
        $event2->setEventAudience('Family Friendly');

        $eventCat2 = new Category();
        $eventCat2->setCategoryName("Arts and Culture");
        $event2->addCategory($eventCat2);

        $event2->setEventImages(1);
        $event2->setEventLink('https://www.google.com/');
        $event2->setEventCreator('username1');
        $event2->setModeratorApproval('true');

        $event2->setUserId($creator);

        $manager->persist($event2);
        $manager->persist($eventCat2);

        $manager->flush();

        date_default_timezone_set('Canada/Central');
        $event3 = new Event();
        $event3->setEventTitle('Flea Market');
        $event3->setEventDescription('Dozens of vendors are coming to Saskatoon, don\'t miss out!');
        $event3->setEventLocation('Prince Albert');
        $event3->setEventStartDate(new \DateTime('2025-06-08 12:00:00'));
        //$event3->setEventEndDate(new \DateTime('2025-06-08 17:00:00'));
        $event3->setEventAudience('Family Friendly');

        $eventCat = new Category();
        $eventCat->setCategoryName("Shopping");
        $event3->addCategory($eventCat);

        $event3->setEventLink('https://prairielandpark.com/');
        $event3->setEventCreator('username1');
        $event3->setModeratorApproval('true');

        $event3->setUserId($creator);

        $manager->persist($event3);
        $manager->persist($eventCat);

        $manager->flush();

        $danceimage = new Media();
        $danceimage->setEvent($event2);
        $danceimage->setPath('dancing.jpg');

        $chiliimage1 = new Media();
        $chiliimage1->setEvent($event1);
        $chiliimage1->setPath('chili1.jpg');

        $chiliimage2 = new Media();
        $chiliimage2->setEvent($event1);
        $chiliimage2->setPath('kevin-chili.webp');

        $chiliimage3 = new Media();
        $chiliimage3->setEvent($event1);
        $chiliimage3->setPath('chilidisaster.jpg');

        $manager->persist($danceimage);
        $manager->persist($chiliimage1);
        $manager->persist($chiliimage2);
        $manager->persist($chiliimage3);

        $manager->flush();

    }
}