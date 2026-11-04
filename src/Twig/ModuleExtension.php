<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Pont entre les templates du site et les modules optionnels (voir src/Kernel.php).
 *
 * Un module absent ne casse rien : ces fonctions existent toujours et se rabattent sur un
 * comportement neutre. Les templates n'appellent donc jamais directement une fonction d'un
 * bundle optionnel (Twig refuserait de compiler le template sans lui).
 *
 *   {% if app_module('shop') %}<twig:Astra:CartButton />{% endif %}
 *   <html {{ app_html_attributes() }}>
 */
final class ModuleExtension extends AbstractExtension
{
    /** Langue → territoire par défaut pour og:locale (fr → fr_FR). */
    private const TERRITORIES = [
        'ar' => 'AR', 'en' => 'US', 'fa' => 'IR', 'he' => 'IL', 'ja' => 'JP', 'zh' => 'CN', 'pt' => 'PT',
    ];

    /**
     * @param list<string> $modules
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire('%app.modules%')] private readonly array $modules,
        #[Autowire('%kernel.default_locale%')] private readonly string $defaultLocale,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_module', $this->hasModule(...)),
            new TwigFunction('app_html_attributes', $this->htmlAttributes(...), ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('app_alternate_links', $this->alternateLinks(...), ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('app_html_lang', $this->htmlLang(...)),
            new TwigFunction('app_og_locale', $this->ogLocale(...)),
        ];
    }

    public function hasModule(string $module): bool
    {
        return \in_array($module, $this->modules, true);
    }

    /** lang="…" dir="…" de la balise <html>. */
    public function htmlAttributes(Environment $twig): string
    {
        if (null !== $result = $this->delegate($twig, 'multilang_html_attributes')) {
            return $result;
        }

        return \sprintf('lang="%s" dir="ltr"', htmlspecialchars($this->htmlLang(), \ENT_QUOTES));
    }

    /** <link rel="alternate" hreflang="…"> de chaque langue (vide sans le module multilingue). */
    public function alternateLinks(Environment $twig): string
    {
        return $this->delegate($twig, 'multilang_alternate_links') ?? '';
    }

    /** Langue de la page au format BCP 47 (fr, pt-BR…). */
    public function htmlLang(): string
    {
        return str_replace('_', '-', $this->locale());
    }

    /** Locale Open Graph (fr_FR, en_US…). */
    public function ogLocale(): string
    {
        $locale = str_replace('-', '_', $this->locale());
        if (str_contains($locale, '_')) {
            return $locale;
        }

        return $locale.'_'.(self::TERRITORIES[$locale] ?? strtoupper($locale));
    }

    private function locale(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->defaultLocale;
    }

    private function delegate(Environment $twig, string $function): ?string
    {
        $callable = $twig->getFunction($function)?->getCallable();

        return \is_callable($callable) ? (string) $callable() : null;
    }
}
