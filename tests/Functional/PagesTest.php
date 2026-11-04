<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

final class PagesTest extends WebTestCase
{
    use SitePaths;
    use ResetDatabase;

    /** @return iterable<string, array{string}> */
    public static function publicPages(): iterable
    {
        yield 'accueil' => ['/'];
        yield 'contact' => ['/contact'];
        yield 'connexion' => ['/connexion'];
        yield 'inscription' => ['/inscription'];
        yield 'mot de passe oublié' => ['/mot-de-passe-oublie'];
        yield 'renvoi confirmation' => ['/inscription/renvoyer-confirmation'];
        yield 'mentions légales' => ['/mentions-legales'];
        yield 'confidentialité' => ['/politique-de-confidentialite'];
        yield 'cookies' => ['/politique-cookies'];
        yield 'cgu' => ['/conditions-generales-utilisation'];
        yield 'accessibilité' => ['/accessibilite'];
        yield 'plan du site' => ['/plan-du-site'];
    }

    #[DataProvider('publicPages')]
    public function testPublicPageIsAccessibleAndWellFormed(string $url): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', self::page($url));

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'h1', 'Une page doit avoir un unique <h1>.');
        self::assertSelectorExists('main#main-content');
        self::assertSelectorExists('a[href="#main-content"]');
        self::assertSelectorExists('link[rel="canonical"]');
        self::assertSelectorExists('meta[name="description"]');
        self::assertSame('fr', $crawler->filter('html')->attr('lang'));
    }

    public function testNotFoundPage(): void
    {
        $client = self::createClient();
        $client->request('GET', '/page-qui-n-existe-pas');

        self::assertResponseStatusCodeSame(404);
    }

    public function testSitemapListsPublicPages(): void
    {
        $client = self::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('<loc>http://localhost'.self::page('/contact').'</loc>', $content);
        self::assertStringNotContainsString('/admin', $content);
        self::assertStringNotContainsString('/connexion', $content);
    }

    public function testRobotsBlocksNonProductionEnvironments(): void
    {
        $client = self::createClient();
        $client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Disallow: /', (string) $client->getResponse()->getContent());
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
    }

    public function testSecurityHeaders(): void
    {
        $client = self::createClient();
        $client->request('GET', self::page('/'));

        self::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        self::assertResponseHeaderSame('X-Frame-Options', 'SAMEORIGIN');
        self::assertResponseHeaderSame('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
