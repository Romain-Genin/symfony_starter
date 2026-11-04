<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Security\Encryption\EncryptionException;
use App\Security\Encryption\Encryptor;
use PHPUnit\Framework\TestCase;

final class EncryptorTest extends TestCase
{
    private Encryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new Encryptor(Encryptor::generateKey());
    }

    public function testRoundTrip(): void
    {
        $cipher = $this->encryptor->encrypt('jeanne@example.com');

        self::assertStringStartsWith(Encryptor::PREFIX, (string) $cipher);
        self::assertStringNotContainsString('jeanne', (string) $cipher);
        self::assertSame('jeanne@example.com', $this->encryptor->decrypt($cipher));
    }

    public function testNonceIsRandom(): void
    {
        self::assertNotSame($this->encryptor->encrypt('same'), $this->encryptor->encrypt('same'));
    }

    public function testNullAndPlaintextPassThrough(): void
    {
        self::assertNull($this->encryptor->encrypt(null));
        self::assertNull($this->encryptor->decrypt(null));
        self::assertSame('legacy clear value', $this->encryptor->decrypt('legacy clear value'));
    }

    public function testBlindIndexIsDeterministic(): void
    {
        self::assertSame($this->encryptor->blindIndex('a@b.c'), $this->encryptor->blindIndex('a@b.c'));
        self::assertNotSame($this->encryptor->blindIndex('a@b.c'), $this->encryptor->blindIndex('x@b.c'));
        self::assertSame(64, \strlen($this->encryptor->blindIndex('a@b.c')));
    }

    public function testWrongKeyFails(): void
    {
        $cipher = $this->encryptor->encrypt('secret');

        $this->expectException(EncryptionException::class);
        new Encryptor(Encryptor::generateKey())->decrypt($cipher);
    }

    public function testTamperedValueFails(): void
    {
        $cipher = (string) $this->encryptor->encrypt('secret');
        $tampered = substr($cipher, 0, -4).(str_ends_with($cipher, 'AAAA') ? 'BBBB' : 'AAAA');

        $this->expectException(EncryptionException::class);
        $this->encryptor->decrypt($tampered);
    }

    public function testInvalidKeyIsRejected(): void
    {
        $this->expectException(EncryptionException::class);
        new Encryptor('trop-courte')->encrypt('x');
    }
}
