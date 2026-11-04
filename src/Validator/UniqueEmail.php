<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Unicité de l'email d'un utilisateur. UniqueEntity ne fonctionne pas sur une colonne chiffrée
 * (le chiffré change à chaque écriture) : on compare donc les blind index.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class UniqueEmail extends Constraint
{
    public string $message = 'email.taken';
}
