<?php declare(strict_types=1);

namespace Scaleflex\Cloudimage\Subscriber;

use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Media\MediaEvents;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class MediaSubscriber implements EventSubscriberInterface
{
    private $systemConfigService;
    private $salesChannelRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $salesChannelRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelRepository = $salesChannelRepository;
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
        $salesChannelId = '';
        foreach ($context as $key => $value) {
            if (strpos($key,'isAdmin')) {
                return;
            } else {
                $salesChannelId = $value;
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
                if (strpos($url, 'http') === false && strpos($url, 'https') === false) {
                    $salesChannel = $this->salesChannelRepository->search(
                        (new Criteria([$salesChannelId]))->addAssociation('domains'),
                        $event->getContext()
                    )->get($salesChannelId);

                    $currentDomain = '';
                    if ($salesChannel) {
                        $currentDomain = $salesChannel->getDomains()->first()->getUrl();
                    }
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