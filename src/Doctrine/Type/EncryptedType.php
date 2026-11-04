<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Security\Encryption\Encryptor;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;

/**
 * Type Doctrine « encrypted » : la valeur PHP (string) est chiffrée de manière transparente
 * à l'écriture et déchiffrée à la lecture. Colonne SQL de type TEXT.
 *
 * Usage : #[ORM\Column(type: EncryptedType::NAME)]
 *
 * ⚠️ Une colonne chiffrée n'est ni triable, ni filtrable en SQL (LIKE, ORDER BY…).
 *    Pour une recherche exacte, ajoutez une colonne « blind index » (cf. User::$emailHash).
 */
final class EncryptedType extends Type
{
    public const string NAME = 'encrypted';

    private static ?Encryptor $encryptor = null;

    /**
     * Les types Doctrine n'ont pas accès au conteneur : l'Encryptor est injecté au boot du Kernel.
     */
    public static function setEncryptor(Encryptor $encryptor): void
    {
        self::$encryptor = $encryptor;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return self::getEncryptor()->encrypt($value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (\is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return self::getEncryptor()->decrypt((string) $value);
    }

    private static function getEncryptor(): Encryptor
    {
        return self::$encryptor ?? throw new \LogicException('Encryptor non initialisé : EncryptedType::setEncryptor() doit être appelé au boot du Kernel.');
    }
}
