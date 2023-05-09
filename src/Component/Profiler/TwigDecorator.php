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
        $currentDomain = '';
        if (isset($context['context'])) {
            $jsonContext = json_encode($context['context']);
            $arrContext = json_decode($jsonContext, true);
            $salesChannelId = (string)$arrContext['salesChannel']['id'];
            $currentDomain = $arrContext['salesChannel']['domains'][0]['url'];
        }

        return $this->overwriteImgTag($pageContent, $salesChannelId, $currentDomain);
    }

    public function getTemplateData(): array
    {
        return $this->renders;
    }

    private function overwriteImgTag(string $pageContent, string $salesChannelId, string $currentDomain): string
    {
        if ($salesChannelId != '') {
            $connection = \Shopware\Core\Kernel::getConnection();
            $query = $connection->executeQuery("SELECT configuration_key, configuration_value, sales_channel_id 
                    FROM system_config 
                    WHERE configuration_key LIKE 'ScaleflexCloudimage.config%'");
            $config = $query->fetchAllAssociative();
            // AND sales_channel_id = '" . Uuid::fromHexToBytes($salesChannelId) . "'
            if (count($config) > 0) {
                $arrConfig = [];
                foreach ($config as $item) {
                    $itemValue = json_decode($item['configuration_value'], true);
                    if (isset($arrConfig[$item['configuration_key']]) && $item['sales_channel_id'] == Uuid::fromHexToBytes($salesChannelId)) {
                        $arrConfig[$item['configuration_key']] = $itemValue['_value'];
                    } else if (!isset($arrConfig[$item['configuration_key']])) {
                        $arrConfig[$item['configuration_key']] = $itemValue['_value'];
                    }
                }

                if ($arrConfig['ScaleflexCloudimage.config.ciActivation'] && isset($arrConfig['ScaleflexCloudimage.config.ciToken'])
                    && $arrConfig['ScaleflexCloudimage.config.ciToken'] != '') {
                    if (stripos($pageContent, '<img') !== false) {
                        $dom = new \DOMDocument();
                        $useErrors = libxml_use_internal_errors(true);
                        $dom->loadHTML(mb_convert_encoding($pageContent, 'HTML-ENTITIES', 'UTF-8'));
                        libxml_use_internal_errors($useErrors);
                        $dom->preserveWhiteSpace = false;
                        $quality = '';
                        if (isset($arrConfig['ScaleflexCloudimage.config.ciImageQuality']) && $arrConfig['ScaleflexCloudimage.config.ciImageQuality'] < 100) {
                            $quality = '?q=' . $arrConfig['ScaleflexCloudimage.config.ciImageQuality'];
                        }

                        $ignoreSvg = (isset($arrConfig['ScaleflexCloudimage.config.ciIgnoreSvgImage'])) ? $arrConfig['ScaleflexCloudimage.config.ciIgnoreSvgImage'] : false;
                        $ciToken = $arrConfig['ScaleflexCloudimage.config.ciToken'];
                        $ciRemoveV7 = $arrConfig['ScaleflexCloudimage.config.ciRemoveV7'];
                        $v7 = '';
                        if (!$ciRemoveV7) {
                            $v7 = 'v7/';
                        }

                        $ciUrl = 'https://' . $ciToken . '.cloudimg.io/' . $v7;
                        if (strpos($ciToken, '.')) {
                            $ciUrl = 'https://' . $ciToken . '/' . $v7;
                        }

                        $ciImageQuality = $arrConfig['ScaleflexCloudimage.config.ciImageQuality'];
                        $ciCustomLibrary = $arrConfig['ScaleflexCloudimage.config.ciCustomLibrary'];
                        $ciPreventImageUpsize = $arrConfig['ScaleflexCloudimage.config.ciPreventImageUpsize'];

                        foreach ($dom->getElementsByTagName('img') as $element) {
                            /** @var \DOMElement $element */
                            if ($arrConfig['ScaleflexCloudimage.config.ciStandardMode'] == false) {
                                if ($element->hasAttribute('src')) {
                                    if ($ignoreSvg && strtolower(pathinfo($element->getAttribute('src'), PATHINFO_EXTENSION)) === 'svg') {
                                        continue;
                                    }

                                    $src = $element->getAttribute('src');
                                    if (strpos($src, 'http://') === false && strpos($src, 'https://') === false) {
                                        $src = $currentDomain . $src;
                                    }

                                    $element->setAttribute('ci-src', $src . $quality);
                                    $element->removeAttribute('src');

                                    if ($element->hasAttribute('srcset')) {
                                        $element->removeAttribute('srcset');
                                    }
                                } else {
                                    if ($element->hasAttribute('srcset')) {
                                        $srcset = $element->getAttribute('srcset');
                                        $srcsetArray = explode(' ', $srcset);

                                        if ($ignoreSvg && strtolower(pathinfo($srcsetArray[0], PATHINFO_EXTENSION)) === 'svg') {
                                            continue;
                                        }

                                        if (strpos($srcset, 'http://') === false && strpos($srcset, 'https://') === false) {
                                            $srcsetArray[0] = $currentDomain . $srcsetArray[0];
                                        }

                                        $element->setAttribute('ci-src', $srcsetArray[0] . $quality);
                                        $element->removeAttribute('srcset');
                                    }
                                }
                            } else {
                                if (!$element->hasAttribute('src')) {
                                    if ($element->hasAttribute('srcset')) {
                                        $srcset = $element->getAttribute('srcset');
                                        $srcsetArray = explode(' ', $srcset);

                                        if ($ignoreSvg && strtolower(pathinfo($srcsetArray[0], PATHINFO_EXTENSION)) === 'svg') {
                                            continue;
                                        }

                                        if (strpos($srcset, 'http://') === false && strpos($srcset, 'https://') === false) {
                                            $srcsetArray[0] = $currentDomain . $srcsetArray[0];
                                        }

                                        $url = $srcsetArray[0];
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
                                        $element->setAttribute('src', $ciUrl . $url);
                                        $element->removeAttribute('srcset');
                                    }
                                } else {
                                    $url = $element->getAttribute('src');
                                    if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                                        $url = $currentDomain . $url;
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

                                        $element->setAttribute('src', $ciUrl . $url);
                                        $element->removeAttribute('srcset');
                                    }
                                }
                            }
                        }

                        $pageContent = $dom->saveHTML($dom->documentElement);
                        $pageContent = str_replace('https%3A', 'https:', $pageContent);
                        $pageContent = str_replace('http%3A', 'http:', $pageContent);
                        $pageContent = str_ireplace(['<html><body>', '</body></html>'], '', $pageContent);
                    }
                }
            }
        }

        return $pageContent;
    }
}
