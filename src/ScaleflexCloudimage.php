<?php declare(strict_types=1);

namespace Scaleflex\Cloudimage;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Scaleflex\Cloudimage\Component\DependencyInjection\CustomProfilerExtensions;

class ScaleflexCloudimage extends Plugin
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
        $config->set('ScaleflexCloudimage.config.ciMaximumPixelRatio', "2");
        $config->set('ScaleflexCloudimage.config.ciImageQuality', "90");
        $config->set('ScaleflexCloudimage.config.ciRemoveV7', true);
        $config->set('ScaleflexCloudimage.config.ciPreventImageUpsize', true);
        $config->set('ScaleflexCloudimage.config.ciActivation', true);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        //set the specified values as defaults
        $config->set('ScaleflexCloudimage.config.ciMaximumPixelRatio', "2");
        $config->set('ScaleflexCloudimage.config.ciImageQuality', "90");
        $config->set('ScaleflexCloudimage.config.ciRemoveV7', true);
        $config->set('ScaleflexCloudimage.config.ciPreventImageUpsize', true);
        $config->set('ScaleflexCloudimage.config.ciActivation', true);
    }
}