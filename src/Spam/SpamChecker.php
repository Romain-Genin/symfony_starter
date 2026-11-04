<?php

declare(strict_types=1);

namespace App\Spam;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;

/**
 * Anti-spam sans service tiers (aucun cookie, aucune donnée envoyée à l'extérieur) :
 *  1. champ « pot de miel » invisible pour les humains, rempli par les robots ;
 *  2. jeton horodaté signé (HMAC) : un humain met plus de MIN_SECONDS à remplir le formulaire,
 *     et un jeton trop ancien ou falsifié est rejeté ;
 *  3. limitation de débit par IP (rate limiter, côté contrôleur).
 *
 * Le formulaire doit utiliser AntiSpamType (champ « antispam »).
 */
final readonly class SpamChecker
{
    public const int MIN_SECONDS = 3;
    public const int MAX_SECONDS = 7200;

    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%kernel.secret%')]
        private string $secret,
    ) {
    }

    public function createToken(?int $time = null): string
    {
        $time ??= time();

        return $time.'.'.$this->sign((string) $time);
    }

    public function isSpam(FormInterface $form): bool
    {
        if (!$form->has('antispam')) {
            throw new \LogicException('Le formulaire doit contenir un champ "antispam" (AntiSpamType).');
        }

        $antispam = $form->get('antispam');

        if ('' !== trim((string) $antispam->get('website')->getData())) {
            return true;
        }

        return !$this->isTokenValid((string) $antispam->get('token')->getData());
    }

    private function isTokenValid(string $token): bool
    {
        [$time, $signature] = array_pad(explode('.', $token, 2), 2, '');

        if (!ctype_digit($time) || !hash_equals($this->sign($time), $signature)) {
            return false;
        }

        $elapsed = time() - (int) $time;

        return $elapsed >= self::MIN_SECONDS && $elapsed <= self::MAX_SECONDS;
    }

    private function sign(string $value): string
    {
        return hash_hmac('sha256', 'antispam|'.$value, $this->secret);
    }
}
