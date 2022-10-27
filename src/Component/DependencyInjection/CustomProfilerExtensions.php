<?php

namespace Scaleflex\Cloudimage\Component\DependencyInjection;

use Scaleflex\Cloudimage\Component\Profiler\TwigDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomProfilerExtensions implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('twig')) {
            $container->getDefinition('twig')->setClass(TwigDecorator::class);
        }
    }
}
