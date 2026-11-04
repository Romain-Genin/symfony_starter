<?php

declare(strict_types=1);

namespace App;

use App\Doctrine\Type\EncryptedType;
use App\Security\Encryption\Encryptor;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
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

    private function configureContainer(ContainerConfigurator $container): void
    {
        $this->configureBaseContainer($container);

        $container->parameters()->set('app.modules', $this->getModules());
        foreach ($this->getModules() as $module) {
            $container->import($this->getConfigDir().'/modules/'.$module.'/*.yaml');
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
