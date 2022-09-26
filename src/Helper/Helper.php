<?php

namespace CloudImage\Helper;

use Doctrine\DBAL\Connection;

class Helper
{
    public function overwriteImgTag($html)
    {
        $connection = new Connection();
        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        if ($config->get('CloudImage.config.ciActivation') && stripos($html, '<img') !== false) {
            $dom = new domDocument();
            $useErrors = libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            libxml_use_internal_errors($useErrors);
            $dom->preserveWhiteSpace = false;
            $replaceHtml = false;

            $quality = '';
            if ($config->get('CloudImage.config.ciImageQuality') < 100) {
                $quality = '?q=' . $config->get('CloudImage.config.ciImageQuality');
            }
            $ignoreSvg = $config->get('CloudImage.config.ciIgnoreSvgImage');

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