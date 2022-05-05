<?php

namespace App\DataFixtures;

use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        UserFactory::new()
                   ->withAttributes([
                       'email' => 'magali@bilemo.fr',
                       'plainPassword' => 'bilemo',
                       'createdAt' => UserFactory::faker()->dateTimeBetween('-60 days', '-30 days'),
                   ])
                   ->create()
        ;

        UserFactory::createMany(10);
        ProductFactory::createMany(50);
    }
}
