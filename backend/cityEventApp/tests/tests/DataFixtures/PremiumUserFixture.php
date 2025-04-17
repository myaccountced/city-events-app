<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Subscription;
use App\Enum\NotificationMethods;
use App\Enum\NotificationTimings;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PremiumUserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Password SHOULD be 'ABC123def' ~ $2y$13$C3DjgzRMwOkdIZC5W3vn.eHXtn03G8m6FNIZXd1PTTbJi9cmqQKNS
        $userData = [
            'username'=> 'premium',
            'email' => 'premium@test.com',
            'password' => '$2y$13$C3DjgzRMwOkdIZC5W3vn.eHXtn03G8m6FNIZXd1PTTbJi9cmqQKNS',
            'roles' => ['ROLE_REGISTERED']
        ];

        $product = new User();
        $product->setUsername($userData['username']);
        $product->setEmail($userData['email']);
        $product->setPassword($userData['password']);
        $product->setRoles($userData['roles']);

        $manager->persist($product);
        $manager->flush();

        // Giving the user a subscription that expires in 1 year from today
        $subscription = new Subscription();
        $subscription->setUserId($product->getId());

        $today = new \DateTime();
        $subscription->setStartDate(new DateTimeImmutable($today->format('Y-m-d')));

        $today->add(new DateInterval('P1Y'));
        $subscription->setExpireDate(new DateTimeImmutable($today->format('Y-m-d')));

        $product->addSubscription($subscription);

        $manager->persist($subscription);
        $manager->flush();

        // Password SHOULD be '@Password1'
        $userData = [
            'username'=> 'pUserS15',
            'email' => 'pUserS15@example.com',
            'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
            'roles' => ['ROLE_REGISTERED']
        ];

        $user = new User();
        $user->setUsername($userData['username']);
        $user->setEmail($userData['email']);
        $user->setPassword($userData['password']);
        $user->setRoles($userData['roles']);
        $user->setWantsNotifications(true);
        $user->setNotificationMethods([NotificationMethods::EMAIL]);
        $user->setNotificationTimes([NotificationTimings::DAY0_BEFORE]);

        $manager->persist($user);
        $manager->flush();

        // Giving the user a subscription that expires in 1 year from today
        $subscription = new Subscription();
        $subscription->setUserId($user->getId());
        $user->addSubscription($subscription);

        $today = new \DateTime();
        $subscription->setStartDate(new DateTimeImmutable($today->format('Y-m-d')));

        // Generate a random number of days between 20 and 50
        $randomDays = rand(20, 50);
        $today->add(new DateInterval('P' . $randomDays . 'D'));  // Add random days
        $subscription->setExpireDate(new DateTimeImmutable($today->format('Y-m-d')));

        $manager->persist($subscription);
        $manager->flush();

        $manager->flush();
    }
}
