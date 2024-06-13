<?php

namespace Shcc\YandexBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

#[Exclude]
class ShccYandexBundle extends AbstractBundle
{
    protected string $extensionAlias = 'shcc_yandex';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()->children()
            ->arrayNode('tts')->children()
            ->scalarNode('speaker')->defaultValue('jane')->end()
            ->scalarNode('format')->defaultValue('mp3')->end()
            ->scalarNode('lang')->defaultValue('ru-RU')->end()
            ->scalarNode('emotion')->defaultValue('neutral')->end()
            ->scalarNode('quality')->end()
            ->scalarNode('speed')->end()
            ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void
    {
        $containerConfigurator->import('../config/services.yaml');
        $containerConfigurator->services()->get('shcc.tts.yandex')
            ->arg(0, $config['tts']);
    }
}
