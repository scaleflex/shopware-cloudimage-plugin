<?php declare(strict_types=1);

namespace CloudImage\Storefront\Controller;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorRoute;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Framework\Adapter\Twig\TemplateFinder;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Routing\RequestTransformerInterface;
use Shopware\Core\Framework\Script\Execution\Hook;
use Shopware\Core\Framework\Script\Execution\ScriptExecutor;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Profiling\Profiler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Framework\Routing\Router;
use Shopware\Storefront\Framework\Routing\StorefrontResponse;
use Shopware\Storefront\Framework\Twig\Extension\IconCacheTwigFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Twig\Environment;

abstract class StorefrontController extends AbstractController
{
    public const SUCCESS = 'success';
    public const DANGER = 'danger';
    public const INFO = 'info';
    public const WARNING = 'warning';

    private Environment $twig;

    protected function renderStorefront(string $view, array $parameters = []): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request === null) {
            $request = new Request();
        }

        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        /* @feature-deprecated $view will be original template in StorefrontRenderEvent from 6.5.0.0 */
        if (Feature::isActive('FEATURE_NEXT_17275')) {
            $event = new StorefrontRenderEvent($view, $parameters, $request, $salesChannelContext);
        } else {
            $inheritedView = $this->getTemplateFinder()->find($view);

            $event = new StorefrontRenderEvent($inheritedView, $parameters, $request, $salesChannelContext);
        }
        $this->container->get('event_dispatcher')->dispatch($event);

        $iconCacheEnabled = $this->getSystemConfigService()->get('core.storefrontSettings.iconCache');

        /** @deprecated tag:v6.5.0 - icon cache will be true by default. */
        if ($iconCacheEnabled || (Feature::isActive('v6.5.0.0') && $iconCacheEnabled === null)) {
            IconCacheTwigFilter::enable();
        }

        $response = Profiler::trace('twig-rendering', function () use ($view, $event) {
            return $this->render($view, $event->getParameters(), new StorefrontResponse());
        });

        /** @deprecated tag:v6.5.0 - icon cache will be true by default. */
        if ($iconCacheEnabled || (Feature::isActive('v6.5.0.0') && $iconCacheEnabled === null)) {
            IconCacheTwigFilter::disable();
        }

        if (!$response instanceof StorefrontResponse) {
            throw new \RuntimeException('Symfony render implementation changed. Providing a response is no longer supported');
        }

        $host = $request->attributes->get(RequestTransformer::STOREFRONT_URL);

        $seoUrlReplacer = $this->container->get(SeoUrlPlaceholderHandlerInterface::class);
        $content = $response->getContent();
        if ($content !== false) {
            $response->setContent(
                $seoUrlReplacer->replace($content, $host, $salesChannelContext)
            );
        }

        $response->setData($parameters);
        $response->setContext($salesChannelContext);
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, '1');
        $response->headers->set('Content-Type', 'text/html');

        $content = $response->getContent();
        $overwriteContent = $this->overwriteImgTag($content);
        $response->setContent($overwriteContent);
        return $response;
    }

    private function overwriteImgTag($html)
    {
        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        if ($config->get('ciActivation') && stripos($html, '<img') !== false) {
            $dom = new domDocument();
            $useErrors = libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            libxml_use_internal_errors($useErrors);
            $dom->preserveWhiteSpace = false;
            $replaceHtml = false;

            $quality = '';
            if ($config->get('ciImageQuality') < 100) {
                $quality = '?q=' . $config->get('ciImageQuality');
            }
            $ignoreSvg = $config->get('ciIgnoreSvgImage');

            foreach ($dom->getElementsByTagName('img') as $element) {
                /** @var DOMElement $element */
                if ($element->hasAttribute('src')) {
                    if ($ignoreSvg && strtolower(pathinfo($element->getAttribute('src'), PATHINFO_EXTENSION)) === 'svg') {
                        continue;
                    }

                    $element->setAttribute('ci-src', $element->getAttribute('src') . $quality);
                    $element->removeAttribute('src');
                    $replaceHtml = true;
                }
            }

            if ($replaceHtml) {
                $html = $dom->saveHTML($dom->documentElement);
                $html = str_ireplace(['<html><body>', '</body></html>'], '', $html);
            }
        }
        return $html;
    }
}
