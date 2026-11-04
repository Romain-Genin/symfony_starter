<?php

declare(strict_types=1);

namespace App;

use App\Doctrine\Type\EncryptedType;
use App\Security\Encryption\Encryptor;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        configureContainer as private configureBaseContainer;
        configureRoutes as private configureBaseRoutes;
    }

    /**
     * Modules optionnels : actifs dès que leur bundle est installé (composer require),
     * inactifs sinon. Chacun apporte sa configuration dans config/modules/<module>/.
     *
     * @var array<string, string> module => nom du bundle
     */
    public const MODULES = [
        'multilang' => 'MultiLangBundle',             // romain-genin/symfony-multilang-bundle
        'shop' => 'AstraCheckoutBundle',              // romain-genin/symfony-ecom
        'images' => 'ImageOptimizerBundle',           // romain-genin/symfony-image-optimizer-bundle
    ];

    public function boot(): void
    {
        parent::boot();

        // Les types DBAL sont instanciés hors conteneur : on leur fournit l'Encryptor ici.
        EncryptedType::setEncryptor($this->getContainer()->get(Encryptor::class));
    }

    public function hasModule(string $module): bool
    {
        return isset(self::MODULES[$module], $this->getBundles()[self::MODULES[$module]]);
    }

    /** @return list<string> */
    public function getModules(): array
    {
        return array_values(array_filter(array_keys(self::MODULES), $this->hasModule(...)));
    }

    /**
     * Langues du site : une par fichier translations/messages.<code>.yaml (fr, en, pt_BR…).
     * Le starter n'en fournit qu'une (français) ; en déposer d'autres suffit à les activer
     * avec le module multilingue.
     *
     * @return list<string>
     */
    public function getLocales(): array
    {
        $locales = [];
        foreach (glob($this->getProjectDir().'/translations/messages*.*.{yaml,yml,xlf,php}', \GLOB_BRACE) ?: [] as $file) {
            if (preg_match('/^messages(?:\+intl-icu)?\.([a-z]{2,3}(?:_[A-Za-z]{2,4})?)\.\w+$/', basename($file), $m)) {
                $locales[] = $m[1];
            }
        }
        $locales = array_values(array_unique($locales));
        sort($locales);

        return $locales;
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $this->configureBaseContainer($container);

        // Une langue ajoutée ou retirée (fichier de traductions) reconstruit le conteneur en dev.
        if (is_dir($translations = $this->getProjectDir().'/translations')) {
            $builder->addResource(new DirectoryResource($translations, '/^messages/'));
        }

        $container->parameters()
            ->set('app.modules', $this->getModules())
            ->set('app.locales', $this->getLocales());

        foreach ($this->getModules() as $module) {
            $container->import($this->getConfigDir().'/modules/'.$module.'/*.yaml');
        }

        if ($this->hasModule('multilang') && $locales = $this->getLocales()) {
            $container->extension('multi_lang', ['locales' => $locales]);
            $container->extension('framework', ['enabled_locales' => $locales]);
        }
    }

    /**
     * config/routes.yaml et config/routes/ ne contiennent que les routes toujours à la racine
     * (sitemap.xml, robots.txt, admin, profiler). Les pages du site sont importées ici :
     * préfixées par /{_locale} (/fr/contact, /en/contact) quand le module multilingue est installé.
     */
    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $this->configureBaseRoutes($routes);

        $controllers = $this->getProjectDir().'/src/Controller';
        $localized = [
            $routes->import($controllers.'/', 'attribute', false, [$controllers.'/Admin/', $controllers.'/SeoController.php']),
        ];

        if ($this->hasModule('shop')) {
            $localized[] = $routes->import('@AstraCheckoutBundle/config/routes.yaml');
        }

        if ($this->hasModule('images')) {
            $routes->import('.', 'image_optimizer');
        }

        if ($this->hasModule('multilang')) {
            // "/" → /{langue préférée}/ et /_locale/{code} (changement de langue)
            $routes->import('@MultiLangBundle/config/routes.php');
            foreach ($localized as $import) {
                $import->prefix('/{_locale}')->requirements(['_locale' => '%multilang.locales_requirement%']);
            }
        }
    }
}
