<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages légales (contenus dans templates/legal/). Les informations société viennent de config/packages/app_site.yaml.
 */
final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal_notice', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function notice(): Response
    {
        return $this->render('legal/notice.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'app_legal_privacy', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig', [
            'retentionMonths' => $this->getParameter('app.contact_retention_months'),
        ]);
    }

    #[Route('/politique-cookies', name: 'app_legal_cookies', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function cookies(): Response
    {
        return $this->render('legal/cookies.html.twig');
    }

    #[Route('/conditions-generales-utilisation', name: 'app_legal_terms', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function terms(): Response
    {
        return $this->render('legal/terms.html.twig');
    }

    #[Route('/accessibilite', name: 'app_legal_accessibility', methods: ['GET'], options: ['sitemap' => ['priority' => 0.3]])]
    public function accessibility(): Response
    {
        return $this->render('legal/accessibility.html.twig');
    }

    #[Route('/plan-du-site', name: 'app_sitemap_page', methods: ['GET'], options: ['sitemap' => ['priority' => 0.2]])]
    public function sitemap(): Response
    {
        return $this->render('legal/sitemap.html.twig');
    }
}
