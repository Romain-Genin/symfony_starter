<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Chemins des pages du site, valables avec ou sans le module multilingue :
 * self::page('/contact') vaut « /contact », ou « /fr/contact » quand le module est installé.
 */
trait SitePaths
{
    private static function page(string $path): string
    {
        $container = self::getContainer();
        if (!\in_array('multilang', (array) $container->getParameter('app.modules'), true)) {
            return $path;
        }

        return '/'.$container->getParameter('multilang.default_locale').$path;
    }
}
