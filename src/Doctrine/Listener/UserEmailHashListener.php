<?php

declare(strict_types=1);

namespace App\Doctrine\Listener;

use App\Entity\User;
use App\Security\Encryption\Encryptor;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Maintient le blind index de l'email (User::$emailHash) synchronisé avec l'email chiffré.
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
final readonly class UserEmailHashListener
{
    public function __construct(private Encryptor $encryptor)
    {
    }

    public function prePersist(User $user, PrePersistEventArgs $args): void
    {
        $user->setEmailHash($this->hash($user));
    }

    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('email')) {
            $args->setNewValue('emailHash', $this->hash($user));
        }
    }

    private function hash(User $user): string
    {
        return $this->encryptor->blindIndex(User::normalizeEmail((string) $user->getEmail()));
    }
}
