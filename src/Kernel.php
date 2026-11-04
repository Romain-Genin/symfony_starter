<?php

declare(strict_types=1);

namespace App;

use App\Doctrine\Type\EncryptedType;
use App\Security\Encryption\Encryptor;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Les types DBAL sont instanciés hors conteneur : on leur fournit l'Encryptor ici.
        EncryptedType::setEncryptor($this->getContainer()->get(Encryptor::class));
    }
}
