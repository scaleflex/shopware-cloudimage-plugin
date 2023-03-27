<?php declare(strict_types=1);

namespace Scaleflex\Cloudimage\Subscriber;

use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Media\MediaEvents;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\Context\CachedSalesChannelContextFactory;

class MediaSubscriber implements EventSubscriberInterface
{
    private $systemConfigService;
    private $contextFactory;

    public function __construct(
        SystemConfigService $systemConfigService,
        CachedSalesChannelContextFactory $contextFactory
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->contextFactory = $contextFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaEvents::MEDIA_LOADED_EVENT => 'onMediaLoaded'
        ];
    }

    public function onMediaLoaded(EntityLoadedEvent $event): void
    {
        $ciActivation = $this->systemConfigService->get('ScaleflexCloudimage.config.ciActivation');
        if (!$ciActivation) {
            return;
        }

        $ciStandardMode = $this->systemConfigService->get('ScaleflexCloudimage.config.ciStandardMode');
        if (!$ciStandardMode) {
            return;
        }

        $ciToken = $this->systemConfigService->get('ScaleflexCloudimage.config.ciToken');
        if ($ciToken == '') {
            return;
        }

        $context = (array)$event->getContext()->getSource();
        $SalesChannelId = '';
        foreach ($context as $key => $value) {
            if (strpos($key,'isAdmin')) {
                return;
            } else {
                $SalesChannelId = $value;
            }
        }

        $contextFactory = $this->contextFactory->create('', $SalesChannelId);
        $domain = (array)$contextFactory->getSalesChannel()->getDomains()->first();
        $currentDomain = '';
        foreach ($domain as $key => $value) {
            if (strpos($key,'url')) {
                $currentDomain = $value;
                break;
            }
        }

        $ciRemoveV7 = $this->systemConfigService->get('ScaleflexCloudimage.config.ciRemoveV7');
        $v7 = '';
        if (!$ciRemoveV7) {
            $v7 = 'v7/';
        }

        $ciUrl = 'https://' . $ciToken . '.cloudimg.io/' . $v7;
        if (strpos($ciToken, '.')) {
            $ciUrl = 'https://' . $ciToken . '/' . $v7;
        }

        $ciImageQuality = $this->systemConfigService->get('ScaleflexCloudimage.config.ciImageQuality');
        $ciPreventImageUpsize = $this->systemConfigService->get('ScaleflexCloudimage.config.ciPreventImageUpsize');
        $ciCustomLibrary = $this->systemConfigService->get('ScaleflexCloudimage.config.ciCustomLibrary');

        /** @var MediaEntity $mediaEntity */
        foreach ($event->getEntities() as $mediaEntity) {
            $fileExtension = $mediaEntity->getFileExtension();
            if (in_array($fileExtension, ['png', 'jpg', 'svg', 'jpeg', 'gif', 'tiff'])) {
                $url = $mediaEntity->getUrl();
                if (!strpos($url, 'http') && !strpos($url, 'https')) {
                    $url = $currentDomain . $url;
                }

                if ($ciImageQuality != '' && $ciImageQuality <= 100) {
                    $quality = '?q=' . $ciImageQuality;
                    if (strpos($url, '?')) {
                        $quality = '&q=' . $ciImageQuality;
                    }
                    $url = $url . $quality;
                }

                if ($ciCustomLibrary != '') {
                    if (strpos($url, '?')) {
                        $url = $url . '&' . $ciCustomLibrary;
                    } else {
                        $url = $url . '?' . $ciCustomLibrary;
                    }
                }

                if ($ciPreventImageUpsize) {
                    if (strpos($url, '?')) {
                        $url = $url . '&org_if_sml=1';
                    } else {
                        $url = $url . '?org_if_sml=1';
                    }
                }
                $mediaEntity->setUrl($ciUrl . $url);
                $mediaEntity->setThumbnails(new MediaThumbnailCollection());
            }
        }
    }
}