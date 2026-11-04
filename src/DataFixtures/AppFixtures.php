<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\ContactMessageFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Données de démo : make fixtures
 * Admin : admin@example.com / password-de-test-123.
 */
final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::new()->admin()->create(['email' => 'admin@example.com', 'firstName' => 'Admin', 'lastName' => 'Astra']);
        UserFactory::createMany(5);
        UserFactory::new()->unverified()->create();
        ContactMessageFactory::createMany(8);
    }
}
