<?php


namespace App\Tests\tests\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtureNoEvent extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Zero events in the database, but need a moderator
        // Password SHOULD be 'ABC123def'
        $userData = [
            'username'=> 'Moderator2',
            'email' => 'mod1@test.com',
            'password' => '$2y$13$lwnPsCTueHsyIMYNmbPvR.B3OWc55pEtHuMQQM/3TzeUQCcDasl1G',
            'roles' => ['ROLE_MODERATOR']
        ];


        $product = new User();
        $product->setUsername($userData['username']);
        $product->setEmail($userData['email']);
        $product->setPassword($userData['password']);
        $product->setModerator(true);
        $product->setRoles($userData['roles']);

        $manager->persist($product);


        $manager->flush();
    }
}