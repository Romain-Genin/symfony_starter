<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Pages légales (contenus dans templates/legal/). Les informations société viennent de config/packages/app_site.yaml.
 *
 * Textes longs et propres au droit de chaque pays : ils sont traduits par fichier plutôt que par clé.
 * Pour une autre langue, copiez templates/legal/notice.html.twig en templates/legal/notice.en.html.twig
 * et traduisez-le ; sans traduction, la version française est affichée.
 */
final class LegalController extends AbstractController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route('/mentions-legales', name: 'app_legal_notice', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function notice(): Response
    {
        return $this->renderLegal('notice');
    }

    #[Route('/politique-de-confidentialite', name: 'app_legal_privacy', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function privacy(): Response
    {
        return $this->renderLegal('privacy', [
            'retentionMonths' => $this->getParameter('app.contact_retention_months'),
        ]);
    }

    #[Route('/politique-cookies', name: 'app_legal_cookies', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function cookies(): Response
    {
        return $this->renderLegal('cookies');
    }

    #[Route('/conditions-generales-utilisation', name: 'app_legal_terms', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function terms(): Response
    {
        return $this->renderLegal('terms');
    }

    #[Route('/accessibilite', name: 'app_legal_accessibility', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function accessibility(): Response
    {
        return $this->renderLegal('accessibility');
    }

    #[Route('/plan-du-site', name: 'app_sitemap_page', methods: ['GET'], options: ['sitemap' => ['priority' => 0.2]])]
    public function sitemap(): Response
    {
        return $this->renderLegal('sitemap');
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function renderLegal(string $page, array $parameters = []): Response
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale();
        $localized = \sprintf('legal/%s.%s.html.twig', $page, $locale);

        return $this->render($this->twig->getLoader()->exists($localized) ? $localized : 'legal/'.$page.'.html.twig', $parameters);
    }
}
