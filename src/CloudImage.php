<?php declare(strict_types=1);

namespace CloudImage;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use CloudImage\Component\DependencyInjection\CustomProfilerExtensions;

class CloudImage extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CustomProfilerExtensions());
        parent::build($container);
    }

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        //set the specified values as defaults
        $config->set('CloudImage.config.ciMaximumPixelRatio', "2");
        $config->set('CloudImage.config.ciImageQuality', "90");
        $config->set('CloudImage.config.ciRemoveV7', true);
        $config->set('CloudImage.config.ciActivation', true);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        //set the specified values as defaults
        $config->set('CloudImage.config.ciMaximumPixelRatio', "2");
        $config->set('CloudImage.config.ciImageQuality', "90");
        $config->set('CloudImage.config.ciRemoveV7', true);
        $config->set('CloudImage.config.ciActivation', true);
    }
}