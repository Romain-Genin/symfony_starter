<?php

declare(strict_types=1);

namespace App\Security\Encryption;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Chiffrement symétrique authentifié des données sensibles stockées en base.
 *
 * - Algorithme : XChaCha20-Poly1305 (libsodium), nonce aléatoire de 192 bits par valeur.
 * - Deux sous-clés sont dérivées de APP_ENCRYPTION_KEY (KDF libsodium) :
 *     1. chiffrement des valeurs ;
 *     2. « blind index » HMAC-SHA256 permettant les recherches exactes (ex : email) sans déchiffrer.
 * - Format stocké : "enc:v1:" . base64(nonce . ciphertext). Une valeur sans préfixe est
 *   considérée comme du clair (pratique pour chiffrer une colonne existante progressivement).
 */
final class Encryptor
{
    public const string PREFIX = 'enc:v1:';

    private const int KDF_ENCRYPTION_ID = 1;
    private const int KDF_BLIND_INDEX_ID = 2;
    private const string KDF_CONTEXT = 'appcrypt';

    private ?string $encryptionKey = null;
    private ?string $blindIndexKey = null;

    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%env(APP_ENCRYPTION_KEY)%')]
        private readonly string $masterKey,
    ) {
    }

    /**
     * Génère une nouvelle clé maître encodée en base64 (à placer dans APP_ENCRYPTION_KEY).
     */
    public static function generateKey(): string
    {
        return base64_encode(sodium_crypto_kdf_keygen());
    }

    public function encrypt(#[\SensitiveParameter] ?string $plaintext): ?string
    {
        if (null === $plaintext) {
            return null;
        }

        $nonce = random_bytes(\SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, self::PREFIX, $nonce, $this->getEncryptionKey());

        return self::PREFIX.sodium_bin2base64($nonce.$ciphertext, \SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    public function decrypt(?string $value): ?string
    {
        if (null === $value || !self::isEncrypted($value)) {
            return $value;
        }

        try {
            $raw = sodium_base642bin(substr($value, \strlen(self::PREFIX)), \SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (\SodiumException $e) {
            throw new EncryptionException('Valeur chiffrée corrompue (base64 invalide).', previous: $e);
        }

        $nonceLength = \SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (\strlen($raw) <= $nonceLength) {
            throw new EncryptionException('Valeur chiffrée corrompue (longueur invalide).');
        }

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($raw, $nonceLength),
            self::PREFIX,
            substr($raw, 0, $nonceLength),
            $this->getEncryptionKey(),
        );

        if (false === $plaintext) {
            throw new EncryptionException('Impossible de déchiffrer la valeur : clé incorrecte ou donnée altérée.');
        }

        return $plaintext;
    }

    /**
     * Empreinte déterministe (HMAC) d'une valeur, pour les recherches / contraintes d'unicité.
     */
    public function blindIndex(#[\SensitiveParameter] string $value): string
    {
        return hash_hmac('sha256', $value, $this->getBlindIndexKey());
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    private function getEncryptionKey(): string
    {
        return $this->encryptionKey ??= sodium_crypto_kdf_derive_from_key(
            \SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            self::KDF_ENCRYPTION_ID,
            self::KDF_CONTEXT,
            $this->getMasterKey(),
        );
    }

    private function getBlindIndexKey(): string
    {
        return $this->blindIndexKey ??= sodium_crypto_kdf_derive_from_key(
            32,
            self::KDF_BLIND_INDEX_ID,
            self::KDF_CONTEXT,
            $this->getMasterKey(),
        );
    }

    private function getMasterKey(): string
    {
        if ('' === $this->masterKey) {
            throw new EncryptionException('APP_ENCRYPTION_KEY est vide. Générez une clé avec "php bin/console app:encryption:generate-key".');
        }

        $key = base64_decode($this->masterKey, true);
        if (false === $key || \SODIUM_CRYPTO_KDF_KEYBYTES !== \strlen($key)) {
            throw new EncryptionException(\sprintf('APP_ENCRYPTION_KEY doit être une chaîne base64 de %d octets.', \SODIUM_CRYPTO_KDF_KEYBYTES));
        }

        return $key;
    }
}
