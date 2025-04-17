<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppUserFixtureTwentyfiveUsers extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $usersData = [];

        // All of these users will have SIMILAR but not TOO similar names and emails
        for ($i = 0; $i < 5; $i++) {
            $oneUserData = [
                'username' => 'bob'.$i,
                'email' => 'bob'.( 5 - $i).'@example.com',
                'password' => '$2y$13$C3DjgzRMwOkdIZC5W3vn.eHXtn03G8m6FNIZXd1PTTbJi9cmqQKNS',
            ];

            $usersData[] = $oneUserData;
        }

        for ($i = 0; $i < 5; $i++) {
            $oneUserData = [
                'username' => 'bobb'.$i,
                'email' => 'bobb'.( 5 - $i).'@example.com',
                'password' => '$2y$13$C3DjgzRMwOkdIZC5W3vn.eHXtn03G8m6FNIZXd1PTTbJi9cmqQKNS',
            ];

            $usersData[] = $oneUserData;
        }

        for ($i = 0; $i < 5; $i++) {
            $oneUserData = [
                'username' => 'rob'.$i,
                'email' => 'rob'.( 5 - $i).'@example.com',
                'password' => '$2y$13$C3DjgzRMwOkdIZC5W3vn.eHXtn03G8m6FNIZXd1PTTbJi9cmqQKNS',
            ];

            $usersData[] = $oneUserData;
        }

        for ($i = 0; $i < 5; $i++) {
            $oneUserData = [
                'username' => 'robert'.$i,
                'email' => 'bobert'.( 5 - $i).'@example.com',
                'password' => '$2y$13$C3DjgzRMwOkdIZC5W3vn.eHXtn03G8m6FNIZXd1PTTbJi9cmqQKNS',
            ];

            $usersData[] = $oneUserData;
        }

        for ($i = 0; $i < 5; $i++) {
            $oneUserData = [
                'username' => 'bo'.$i,
                'email' => 'bo'.( 5 - $i).'@example.com',
                'password' => '$2y$13$C3DjgzRMwOkdIZC5W3vn.eHXtn03G8m6FNIZXd1PTTbJi9cmqQKNS',
            ];

            $usersData[] = $oneUserData;
        }

        foreach ($usersData as $userData) {
            $product = new User();
            $product->setUsername($userData['username']);
            $product->setEmail($userData['email']);
            $product->setPassword($userData['password']);

            $manager->persist($product);
        }

        $manager->flush();

    }
}