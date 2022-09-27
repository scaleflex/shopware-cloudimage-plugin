<?php

namespace CloudImage\Component\Profiler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractSQLServerDriver\Exception\PortWithoutHost;
use Shopware\Core\Maintenance\System\Struct\DatabaseConnectionInformation;
use Twig\Environment;
use Twig\TemplateWrapper;

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

        return $this->overwriteImgTag($pageContent);
    }

    public function getTemplateData(): array
    {
        return $this->renders;
    }

    private function overwriteImgTag(string $pageContent): string
    {
        $connection = \Shopware\Core\Kernel::getConnection();
        $query = $connection->executeQuery("SELECT configuration_key, configuration_value FROM system_config WHERE configuration_key LIKE 'CloudImage.config%'");
        $config = $query->fetchAllAssociative();
        dd($config);

        if (stripos($pageContent, '<img') !== false) {
            $dom = new \DOMDocument();
            $useErrors = libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $pageContent);
            libxml_use_internal_errors($useErrors);
            $dom->preserveWhiteSpace = false;
            $replaceHtml = false;
//            $quality = '';
//            if ($config->get('CloudImage.config.ciImageQuality') < 100) {
//                $quality = '?q=' . $config->get('CloudImage.config.ciImageQuality');
//            }
            $quality = '?q=90';
            //$ignoreSvg = $config->get('CloudImage.config.ciIgnoreSvgImage');
            $ignoreSvg = true;

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

        return $pageContent;
    }
}
