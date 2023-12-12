<?php

namespace App\DataFixtures;

use App\Entity\Post;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PostFixtures extends Fixture
{
    public function load(ObjectManager $manager): void {
        //initialiser faker
        $faker = Factory::create('fr_FR');
        //créer 10 posts
        for ($i = 0; $i < 10; $i++) {
            $post = new Post();
            $post->setTitre($faker->words(6,true));
            $post->setContenu($faker->realTextBetween(200,300));
            $post->setCreatedAt($faker->dateTimeBetween('-6 months'));
            $manager->persist($post);
        }

        $manager->flush();
    }
}
