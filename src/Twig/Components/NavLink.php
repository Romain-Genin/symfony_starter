<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Lien de navigation qui porte aria-current="page" sur la page active.
 *
 *   <twig:NavLink route="app_contact">Contact</twig:NavLink>
 */
#[AsTwigComponent]
final class NavLink
{
    public string $route;

    /** @var array<string, mixed> */
    public array $params = [];

    /** Considère aussi actives les routes commençant par ce préfixe (ex : "app_account"). */
    public ?string $activePrefix = null;

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function isActive(): bool
    {
        $current = (string) $this->requestStack->getMainRequest()?->attributes->get('_route');

        return $current === $this->route
            || (null !== $this->activePrefix && str_starts_with($current, $this->activePrefix));
    }
}
