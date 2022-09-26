<?php

namespace CloudImage\Component\Profiler;

use Twig\Environment;
use Twig\TemplateWrapper;
use CloudImage\Helper\Helper;

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
        $helper = new Helper();
        $pageContent = $helper->overwriteImgTag($pageContent);
        return parent::render($template, $context);
    }

    public function getTemplateData(): array
    {
        return $this->renders;
    }
}
