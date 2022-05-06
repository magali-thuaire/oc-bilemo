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

        $magali = UserFactory::new()
           ->withAttributes([
               'email' => 'magali@bilemo.fr',
               'plainPassword' => 'bilemo',
               'createdAt' => UserFactory::faker()->dateTimeBetween('-60 days', '-30 days'),
           ])
           ->create()
        ;


        UserFactory::new()
            ->setClient($magali)
           ->createMany(10);

        $client = UserFactory::new()
                     ->withAttributes([
                         'email' => 'client@bilemo.fr',
                         'plainPassword' => 'bilemo',
                         'createdAt' => UserFactory::faker()->dateTimeBetween('-60 days', '-30 days'),
                     ])
                     ->create()
        ;


        UserFactory::new()
           ->setClient($client)
           ->createMany(10);

        $client = UserFactory::new()
                     ->withAttributes([
                         'email' => 'admin@bilemo.fr',
                         'plainPassword' => 'bilemo',
                         'createdAt' => UserFactory::faker()->dateTimeBetween('-60 days', '-30 days'),
                     ])
                    ->promoteRole('ROLE_ADMIN')
                    ->create()
        ;

        ProductFactory::createMany(50);
    }
}
