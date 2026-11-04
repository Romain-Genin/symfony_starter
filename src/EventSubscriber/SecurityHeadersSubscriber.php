<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * En-têtes de sécurité HTTP (et X-Robots-Tag) appliqués à toutes les réponses.
 */
final readonly class SecurityHeadersSubscriber
{
    public function __construct(
        #[Autowire('%app.indexable%')]
        private bool $indexable,
    ) {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -10)]
    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // Hors production (préprod…) ou zones privées : pas d'indexation.
        $path = $event->getRequest()->getPathInfo();
        if (!$this->indexable || str_starts_with($path, '/admin') || str_starts_with($path, '/mon-compte')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($event->getRequest()->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
