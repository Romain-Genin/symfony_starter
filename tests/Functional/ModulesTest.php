<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Modules optionnels (src/Kernel.php) : chaque test ne s'exécute que si le bundle est installé.
 * La CI lance la suite sans module puis avec les trois, et une seconde langue (.github/workflows/ci.yml).
 */
final class ModulesTest extends WebTestCase
{
    use ResetDatabase;
    use SitePaths;

    public function testMultilangPrefixesPagesAndTranslatesLayout(): void
    {
        $client = self::createClient();
        self::skipUnless('multilang');
        if (!\in_array('en', (array) self::getContainer()->getParameter('app.locales'), true)) {
            self::markTestSkipped('Ajoutez translations/messages.en.yaml pour tester le changement de langue.');
        }

        $client->request('GET', '/');
        self::assertResponseRedirects();
        self::assertStringStartsWith('/fr/', (string) $client->getResponse()->headers->get('Location'));

        $crawler = $client->request('GET', '/en/contact');
        self::assertResponseIsSuccessful();
        self::assertSame('en', $crawler->filter('html')->attr('lang'));
        self::assertSelectorExists('link[rel="alternate"][hreflang="fr"]');
        self::assertSelectorExists('meta[property="og:locale"][content="en_US"]');

        $client->request('GET', '/sitemap.xml');
        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<loc>http://localhost/en/contact</loc>', $content);
        self::assertStringContainsString('hreflang="fr" href="http://localhost/fr/contact"', $content);
    }

    public function testShopPagesUseTheSiteLayoutAndAccounts(): void
    {
        $client = self::createClient();
        self::skipUnless('shop');

        $crawler = $client->request('GET', self::page('/boutique'));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('main#main-content');
        self::assertSelectorExists('a[href="#main-content"]');
        self::assertCount(1, $crawler->filter('link[rel="canonical"]'));

        // Les pages du compte boutique exigent la connexion du site.
        $client->request('GET', self::page('/mon-compte/boutique'));
        self::assertResponseRedirects(self::page('/connexion'));
    }

    public function testImagesModuleRegistersResponsiveRoutes(): void
    {
        self::bootKernel();
        self::skipUnless('images');

        $routes = self::getContainer()->get('router')->getRouteCollection();
        $paths = array_map(static fn ($route): string => $route->getPath(), iterator_to_array($routes));
        self::assertNotEmpty(array_filter($paths, static fn (string $path): bool => str_starts_with($path, '/_image')));
    }

    private static function skipUnless(string $module): void
    {
        if (!\in_array($module, (array) self::getContainer()->getParameter('app.modules'), true)) {
            self::markTestSkipped(\sprintf('Module « %s » non installé.', $module));
        }
    }
}
