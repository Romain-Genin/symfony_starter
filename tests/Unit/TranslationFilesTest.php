<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Chaque langue ajoutée (translations/<domaine>.<code>.yaml) doit traduire toutes les clés
 * de la langue de référence (français), avec les mêmes paramètres (%name%, {{ limit }}…).
 */
final class TranslationFilesTest extends TestCase
{
    private const DIR = __DIR__.'/../../translations';

    /** @return iterable<string, array{string, string}> */
    public static function translationFiles(): iterable
    {
        yield 'référence' => ['messages', 'fr'];
        foreach (glob(self::DIR.'/*.yaml') ?: [] as $file) {
            if (preg_match('/^(\w+)\.(?!fr\.)([\w-]+)\.yaml$/', basename($file), $m)) {
                yield basename($file) => [$m[1], $m[2]];
            }
        }
    }

    #[DataProvider('translationFiles')]
    public function testLocaleTranslatesEveryReferenceKey(string $domain, string $locale): void
    {
        if ('fr' === $locale) {
            self::assertNotEmpty(self::load($domain, 'fr'), 'Le site est monolingue par défaut : messages.fr.yaml fait référence.');

            return;
        }

        $reference = self::load($domain, 'fr');
        $translated = self::load($domain, $locale);

        self::assertSame([], array_values(array_diff(array_keys($reference), array_keys($translated))), "Clés absentes de $domain.$locale.yaml");
        self::assertSame([], array_values(array_diff(array_keys($translated), array_keys($reference))), "Clés inconnues dans $domain.$locale.yaml");

        foreach ($reference as $key => $message) {
            self::assertSame(self::placeholders($message), self::placeholders($translated[$key]), "Paramètres différents pour « $key » ($domain.$locale.yaml)");
        }
    }

    /** @return array<string, string> */
    private static function load(string $domain, string $locale): array
    {
        $file = self::DIR."/$domain.$locale.yaml";
        self::assertFileExists($file);

        return self::flatten((array) Yaml::parseFile($file));
    }

    /**
     * @param array<mixed> $messages
     *
     * @return array<string, string>
     */
    private static function flatten(array $messages, string $prefix = ''): array
    {
        $flat = [];
        foreach ($messages as $key => $value) {
            if (\is_array($value)) {
                $flat += self::flatten($value, $prefix.$key.'.');
            } else {
                $flat[$prefix.$key] = (string) $value;
            }
        }

        return $flat;
    }

    /** @return list<string> */
    private static function placeholders(string $message): array
    {
        preg_match_all('/%\w+%|\{\{ ?\w+ ?\}\}/', $message, $m);
        $found = array_unique($m[0]);
        sort($found);

        return $found;
    }
}
