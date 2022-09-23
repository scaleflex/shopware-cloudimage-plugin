<?php declare(strict_types=1);

namespace CloudImage;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CloudImage extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        //set the specified values as defaults
        $config->set('CloudImage.config.ciMaximumPixelRatio', 2);
        $config->set('CloudImage.config.ciImageQuality', 90);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        //set the specified values as defaults
        $config->set('CloudImage.config.ciMaximumPixelRatio', 2);
        $config->set('CloudImage.config.ciImageQuality', 90);
    }
}