<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public const string DEFAULT_PASSWORD = 'password-de-test-123';

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    public function admin(): static
    {
        return $this->with(['roles' => [User::ROLE_ADMIN]]);
    }

    public function unverified(): static
    {
        return $this->with(['isVerified' => false]);
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'isVerified' => true,
            'roles' => [],
            'plainPassword' => self::DEFAULT_PASSWORD,
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(function (array $attributes): User {
            $user = new User()
                ->setEmail($attributes['email'])
                ->setFirstName($attributes['firstName'])
                ->setLastName($attributes['lastName'])
                ->setIsVerified($attributes['isVerified'])
                ->setRoles($attributes['roles']);
            $user->setPassword($this->hasher->hashPassword($user, $attributes['plainPassword']));

            return $user;
        });
    }
}
