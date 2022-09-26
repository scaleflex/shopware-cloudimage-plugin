<?php declare(strict_types=1);

namespace CloudImage\Storefront\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FooterSubscriber implements EventSubscriberInterface
{
    private $systemConfigService;

    public function __construct(
        SystemConfigService $systemConfigService
    )
    {
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents()
    {
        return [
            FooterPageletLoadedEvent::class => 'onFooterPageletLoadedEvent'
        ];
    }

    public function onFooterPageletLoadedEvent(FooterPageletLoadedEvent $event): void
    {
        if (!$this->systemConfigService->get('CloudImage.config.ciActivation')) {
            return;
        }

        $ciConfig = [
            'cloudimageToken' => $this->systemConfigService->get('CloudImage.config.cloudimageToken'),
            'ciUseOriginalUrl' => $this->systemConfigService->get('CloudImage.config.ciUseOriginalUrl'),
            'ciLazyLoading' => $this->systemConfigService->get('CloudImage.config.ciLazyLoading'),
            'ciIgnoreSvgImage' => $this->systemConfigService->get('CloudImage.config.ciIgnoreSvgImage'),
            'ciPreventImageResize' => $this->systemConfigService->get('CloudImage.config.ciPreventImageResize'),
            'ciImageQuality' => $this->systemConfigService->get('CloudImage.config.ciImageQuality'),
            'ciMaximumPixelRatio' => $this->systemConfigService->get('CloudImage.config.ciMaximumPixelRatio'),
            'ciRemoveV7' => $this->systemConfigService->get('CloudImage.config.ciRemoveV7'),
            'ciCustomFunction' => $this->systemConfigService->get('CloudImage.config.ciCustomFunction'),
            'ciCustomLibrary' => $this->systemConfigService->get('CloudImage.config.ciCustomLibrary')
        ];

//        $event->getPagelet()->addExtension('cloudImage', $ciConfig);
    }
}