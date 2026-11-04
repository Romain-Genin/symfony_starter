<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class SeoController extends AbstractController
{
    /**
     * Sitemap généré à partir des routes déclarant l'option « sitemap »
     * (ex : options: ['sitemap' => ['priority' => 0.8, 'changefreq' => 'monthly']]).
     * Avec le module multilingue, chaque page est listée dans chaque langue, avec ses
     * alternatives hreflang. Pour des URLs dynamiques (articles…), compléter $urls ici.
     */
    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'], format: 'xml')]
    public function sitemap(RouterInterface $router, ContainerBagInterface $parameters): Response
    {
        /** @var list<string> $locales */
        $locales = $parameters->has('multilang.locales') ? $parameters->get('multilang.locales') : [];

        $urls = [];
        foreach ($router->getRouteCollection() as $name => $route) {
            $options = $route->getOption('sitemap');
            $variables = $route->compile()->getPathVariables();
            $localized = $locales && ['_locale'] === $variables;
            if (!$options || ($variables && !$localized)) {
                continue;
            }

            $options = \is_array($options) ? $options : [];
            $alternates = [];
            foreach ($localized ? $locales : [null] as $locale) {
                $alternates[$locale ?? ''] = $this->generateUrl($name, $locale ? ['_locale' => $locale] : [], UrlGeneratorInterface::ABSOLUTE_URL);
            }

            foreach ($alternates as $loc) {
                $urls[] = [
                    'loc' => $loc,
                    'alternates' => $localized ? $alternates : [],
                    'priority' => $options['priority'] ?? 0.5,
                    'changefreq' => $options['changefreq'] ?? 'monthly',
                ];
            }
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
