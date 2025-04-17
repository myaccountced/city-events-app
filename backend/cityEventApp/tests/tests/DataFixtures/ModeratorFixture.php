<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// $2y$13$lwnPsCTueHsyIMYNmbPvR.B3OWc55pEtHuMQQM/3TzeUQCcDasl1G

class ModeratorFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Password SHOULD be 'ABC123def'
        $userData = [
            'username'=> 'Moderator',
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