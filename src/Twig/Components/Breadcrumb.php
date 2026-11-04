<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Fil d'Ariane accessible + données structurées schema.org BreadcrumbList.
 *
 *   <twig:Breadcrumb :items="[{label: 'Contact'}]" />
 *   <twig:Breadcrumb :items="[{label: 'Mon compte', route: 'app_account'}, {label: 'Mot de passe'}]" />
 *
 * L'accueil est ajouté automatiquement ; le dernier élément est la page courante.
 */
#[AsTwigComponent]
final class Breadcrumb
{
    /** @var list<array{label: string, route?: string, params?: array<string, mixed>}> */
    public array $items = [];

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @return list<array{label: string, url: ?string}>
     */
    public function getLinks(): array
    {
        $links = [['label' => 'Accueil', 'url' => $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)]];

        foreach ($this->items as $item) {
            $links[] = [
                'label' => $item['label'],
                'url' => isset($item['route']) ? $this->urlGenerator->generate($item['route'], $item['params'] ?? [], UrlGeneratorInterface::ABSOLUTE_URL) : null,
            ];
        }

        return $links;
    }

    /**
     * @return array<string, mixed>
     */
    public function getJsonLd(): array
    {
        $elements = [];
        foreach ($this->getLinks() as $position => $link) {
            $elements[] = array_filter([
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $link['label'],
                'item' => $link['url'],
            ]);
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}
