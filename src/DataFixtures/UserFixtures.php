<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    //permet de hasher le mdp
    private UserPasswordHasherInterface $hasher;

    /**
     * @param UserPasswordHasherInterface $hasher
     */
    public function __construct(UserPasswordHasherInterface $hasher) {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void {
        $faker = Factory::create('fr_FR');
        // 5 utilisateurs
        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail($faker->email());
            $user->setPassword($this->hasher->hashPassword($user,"prout"));
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
