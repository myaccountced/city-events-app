<?php

namespace App\Tests\tests\DataFixtures;

use App\Entity\Banned;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppUserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $usersData = [
            [
                'username' => 'username1',
                'email' => 'username1@example.com',
                'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
                'roles' => ['ROLE_REGISTERED']
            ],
            [
                'username' => 'username2',
                'email' => 'username2@example.com',
                'password' => '$2y$13$WyWcqxPyWWOOFfASF3mPzOcH4SAe8HUHVxOSuqS3ZFYYKb8EyNVnq',
                'roles' => ['ROLE_REGISTERED']
            ],
            [
                'username' => 'nUserS15',
                'email' => 'nUserS15@example.com',
                'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
                'roles' => ['ROLE_REGISTERED']
            ],
            [
                'username'=> 'zuUser',
                'email' => 'zueventsproject@gmail.com',
                'password' => '$2y$13$25z/daKzpNjDqkk7sE0iD.wNiX3YCariqPJTelFbho.rCCKZ0vjp2',
                'roles' => ['ROLE_REGISTERED']
            ],
            [
                'username' => 'username11',
                'email' => 'username11@example.com',
                'password' => '$2y$13$E/JkR9N3PM81xRKoxbLIzuMEw3i7mUXzrzq4MFw1ytlKzvT4K5I9e',
                'roles' => ['ROLE_REGISTERED'],
                'banned' => true,
                'bannedDate' => new \DateTime(),
                'bannedReason' => 'Abuse of System'

            ]
        ];
        $i=2;
        foreach ($usersData as $userData) {
            // Seeing if this user already exists
            $u = $manager->getRepository(User::class)->findOneBy(['username' => $userData['username']]);

            // The user we are trying to create already exists
            if ($u !== null) {
                continue;
            }

            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setPassword($userData['password']);
            $user->setRoles($userData['roles']);
            // Check if the user should be banned
            if (isset($userData['banned']) && $userData['banned'] === true) {
                // Create the banned entity and set its properties
                $banned = new Banned();
                $banned->setReason($userData['bannedReason'] ?? 'Other');

                // Set the banned date if provided in the data:
                if (isset($userData['bannedDate']) && $userData['bannedDate'] instanceof \DateTimeInterface) {
                    $banned->setDatetime($userData['bannedDate']);
                }

                // Associate the banned entity with the user object directly
                $banned->setUserId($user);

                // Update the inverse side in the User entity
                $user->setBanned($banned);

                $manager->persist($banned);
            }

            $manager->persist($user);
            $this->addReference('user-' . ($i++), $user);
        }

        $manager->flush();
        $i=null;
    }
}