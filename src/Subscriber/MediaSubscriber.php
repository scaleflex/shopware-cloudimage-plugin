<?php declare(strict_types=1);

namespace Scaleflex\Cloudimage\Subscriber;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Media\MediaEvents;

class MediaSubscriber implements EventSubscriberInterface
{
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaEvents::MEDIA_LOADED_EVENT => 'onMediaLoaded'
        ];
    }

    public function onMediaLoaded(EntityLoadedEvent $event): void
    {
        $context = (array)$event->getContext()->getSource();
        $isAdmin = false;
        foreach ($context as $key => $value) {
            if (strpos($key,'isAdmin')) {
                $isAdmin = true;
            }
        }

        if (!$isAdmin) {
            $ciStandardMode = $this->systemConfigService->get('ScaleflexCloudimage.config.ciStandardMode');
            if ($ciStandardMode) {
                $tokenOrCname = $this->systemConfigService->get('ScaleflexCloudimage.config.ciToken');

                $v7 = '';
                if (!$this->systemConfigService->get('ScaleflexCloudimage.config.ciRemoveV7')) {
                    $v7 = 'v7/';
                }

                $ciUrl = 'https://' . $tokenOrCname . '.cloudimg.io/' . $v7;
                if (strpos($tokenOrCname, '.')) {
                    $ciUrl = 'https://' . $tokenOrCname . '/' . $v7;
                }
                /** @var MediaEntity $mediaEntity */
                foreach ($event->getEntities() as $mediaEntity) {
                    $imageUrl = $mediaEntity->getUrl();
                    $mediaEntity->setUrl($ciUrl . $imageUrl);
                }
            }
        }
    }
}