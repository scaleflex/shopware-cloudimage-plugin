<?php

namespace Scaleflex\Cloudimage\Component\Profiler;

use Twig\Environment;
use Twig\TemplateWrapper;
use Shopware\Core\Framework\Uuid\Uuid;

class TwigDecorator extends Environment
{
    private $renders = [];

    public function render($name, array $context = []): string
    {
        $template = $name;

        if ($name instanceof TemplateWrapper) {
            $name = $name->getTemplateName();
        }

        if (strpos($name, 'WebProfiler') === false) {
            $this->renders[$name] = $context;
        }
        $pageContent = parent::render($template, $context);

        $salesChannelId = '';
        if (isset($context['context'])) {
            $jsonContext = json_encode($context['context']);
            $arrContext = json_decode($jsonContext, true);
            $salesChannelId = (string)$arrContext['salesChannel']['id'];
        }

        return $this->overwriteImgTag($pageContent, $salesChannelId);
    }

    public function getTemplateData(): array
    {
        return $this->renders;
    }

    private function overwriteImgTag(string $pageContent, string $salesChannelId): string
    {
        if ($salesChannelId != '') {
            $connection = \Shopware\Core\Kernel::getConnection();
            $query = $connection->executeQuery("SELECT configuration_key, configuration_value 
                    FROM system_config 
                    WHERE configuration_key LIKE 'ScaleflexCloudimage.config%' AND sales_channel_id = '" . Uuid::fromHexToBytes($salesChannelId) . "'");
            $config = $query->fetchAllAssociative();

            if (count($config) > 0) {
                $arrConfig = [];
                foreach ($config as $item) {
                    $itemValue = json_decode($item['configuration_value'], true);
                    $arrConfig[$item['configuration_key']] = $itemValue['_value'];
                }

                if ($arrConfig['ScaleflexCloudimage.config.ciActivation'] && isset($arrConfig['ScaleflexCloudimage.config.ciToken'])
                    && $arrConfig['ScaleflexCloudimage.config.ciToken'] != '') {
                    if (stripos($pageContent, '<img') !== false) {
                        $dom = new \DOMDocument();
                        $useErrors = libxml_use_internal_errors(true);
                        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $pageContent);
                        libxml_use_internal_errors($useErrors);
                        $dom->preserveWhiteSpace = false;
                        $replaceHtml = false;
                        $quality = '';
                        if (isset($arrConfig['ScaleflexCloudimage.config.ciImageQuality']) && $arrConfig['ScaleflexCloudimage.config.ciImageQuality'] < 100) {
                            $quality = '?q=' . $arrConfig['ScaleflexCloudimage.config.ciImageQuality'];
                        }

                        $ignoreSvg = (isset($arrConfig['ScaleflexCloudimage.config.ciIgnoreSvgImage'])) ? $arrConfig['ScaleflexCloudimage.config.ciIgnoreSvgImage'] : false;

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

                            if ($element->hasAttribute('srcset')) {
                                $element->removeAttribute('srcset');
                            }
                        }

                        if ($replaceHtml) {
                            $pageContent = $dom->saveHTML($dom->documentElement);
                            $pageContent = str_ireplace(['<html><body>', '</body></html>'], '', $pageContent);
                        }
                    }
                }
            }
        }

        return $pageContent;
    }
}
