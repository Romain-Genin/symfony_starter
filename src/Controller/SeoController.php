<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class SeoController extends AbstractController
{
    /**
     * Sitemap généré à partir des routes déclarant l'option « sitemap »
     * (ex : options: ['sitemap' => ['priority' => 0.8, 'changefreq' => 'monthly']]).
     * Pour des URLs dynamiques (articles…), compléter $urls ici.
     */
    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'], format: 'xml')]
    public function sitemap(RouterInterface $router): Response
    {
        $urls = [];
        foreach ($router->getRouteCollection() as $name => $route) {
            $options = $route->getOption('sitemap');
            if (!$options || $route->compile()->getPathVariables()) {
                continue;
            }

            $options = \is_array($options) ? $options : [];
            $urls[] = [
                'loc' => $this->generateUrl($name, [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => $options['priority'] ?? 0.5,
                'changefreq' => $options['changefreq'] ?? 'monthly',
            ];
        }

        usort($urls, static fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);

        $response = $this->render('seo/sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic()->setMaxAge(3600);

        return $response;
    }

    /**
     * robots.txt dynamique : tout est bloqué tant que APP_INDEXABLE n'est pas activé
     * (dev, préprod, staging…). À activer uniquement sur le domaine de production.
     */
    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'], format: 'txt')]
    public function robots(#[Autowire('%app.indexable%')] bool $indexable): Response
    {
        $response = $this->render('seo/robots.txt.twig', ['allow' => $indexable]);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->setPublic()->setMaxAge(3600);

        return $response;
    }
}
