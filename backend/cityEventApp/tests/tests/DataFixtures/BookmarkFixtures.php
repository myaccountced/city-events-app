<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\BookmarkedEvent;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BookmarkFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $bookmarkUser = $manager->getRepository(User::class)->findOneBy(['username' => 'zuUser']);

        // the 'bookmarkUser' does not exist?
        if (!$bookmarkUser) {
            $creatorData = [
                'username'=> 'zuUser',
                'email' => 'zueventsproject@gmail.com',
                'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
                'roles' => ['ROLE_REGISTERED']
            ];

            // Making the creator
            $bookmarkUser = new User();
            $bookmarkUser->setUsername($creatorData['username']);
            $bookmarkUser->setEmail($creatorData['email']);
            $bookmarkUser->setPassword($creatorData['password']);
            $bookmarkUser->setRoles($creatorData['roles']);

            $manager->persist($bookmarkUser);
        }

        // password SHOULD be @Password1
        $eventCreator = [
            'username' => 'eventCreator',
            'email' => 'eventCreator@example.com',
            'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
            'roles' => ['ROLE_REGISTERED']
        ];

        // Making the user
        $user = new User();
        $user->setUsername($eventCreator['username']);
        $user->setEmail($eventCreator['email']);
        $user->setPassword($eventCreator['password']);
        $user->setRoles($eventCreator['roles']);

        $manager->persist($user);

        // They make an event:
        $event = new Event();
        $event->setEventCreator($user->getUsername());
        $event->setUserId($user);
        $event->setEventTitle("Bookmark this event");
        $event->setEventDescription("This is the description of the event");
        $event->setEventStartDate(new \DateTime("2025/02/02"));
        $event->setEventEndDate(new \DateTime("2025/12/12"));

        // Event has two categories
        $category1 = new Category();
        $category1->setCategoryName("Music");
        $event->addCategory($category1);

        $category2 = new Category();
        $category2->setCategoryName("Sports");
        $event->addCategory($category2);

        $event->setEventLocation("Martensville");
        $event->setEventAudience("General");
        $event->setEventLink("www.google.com");
        $event->setModeratorApproval(false);

        // This makes the event look like it has been created yesterday but edited today!
        $yesterday = new \DateTime("yesterday");
        $event->setCreationDate($yesterday);

        // zuUser has bookmarked this event!
        $bookmark = new BookmarkedEvent();
        $bookmark->setEvent($event);
        $bookmark->setUser($bookmarkUser);

        $manager->persist($event);
        $manager->persist($bookmark);


        // also this user has followed eventCreator
        $follower = [
            'username' => 'followerUser',
            'email' => 'follow@example.com',
            'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
            'roles' => ['ROLE_REGISTERED']
        ];

        $user2 = new User();
        $user2->setUsername($follower['username']);
        $user2->setEmail($follower['email']);
        $user2->setPassword($follower['password']);
        $user2->setRoles($follower['roles']);

        $manager->persist($user);

        // FOLLOWING!!
        $user2->followUser($user);



        // Making a second event!

        // They make an event:
        $event2 = new Event();
        $event2->setEventCreator($user->getUsername());
        $event2->setUserId($user);
        $event2->setEventTitle("Approved event");
        $event2->setEventDescription("This is the description of the approved event");
        $event2->setEventStartDate(new \DateTime("2025/01/02"));
        $event2->setEventEndDate(new \DateTime("2025/12/12"));

        // Event has two categories
        $category3 = new Category();
        $category3->setCategoryName("Technology");
        $event2->addCategory($category3);

        $category4 = new Category();
        $category4->setCategoryName("Others");
        $event2->addCategory($category4);

        $event2->setEventLocation("Martensville");
        $event2->setEventAudience("General");
        $event2->setEventLink("www.google.com");
        $event2->setModeratorApproval(true);

        $manager->persist($event2);


        $manager->flush();
    }
}