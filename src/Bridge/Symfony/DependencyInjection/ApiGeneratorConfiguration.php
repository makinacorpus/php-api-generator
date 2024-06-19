<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ApiGeneratorConfiguration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('api_generator');

        // @phpstan-ignore-next-line
        $treeBuilder
            ->getRootNode()
                ->children()
                    ->arrayNode('defaults')
                        ->normalizeKeys(true)
                        ->children()
                            ->variableNode('directory')->isRequired()->defaultValue('%kernel.project_dir%/assets/src')->end()
                            ->booleanNode('ignore_internal')->defaultTrue()->end()
                            ->scalarNode('language')->isRequired()->defaultValue('typescript')->end()
                            ->scalarNode('namespace_prefix_input')->defaultValue('App')->end()
                            ->scalarNode('namespace_prefix_output')->defaultValue('interfaces')->end()
                        ->end()
                    ->end()
                    ->arrayNode('targets')
                        ->normalizeKeys(true)
                        ->prototype('array')
                            ->beforeNormalization()->ifString()->then(function ($v) { return ['source' => $v]; })->end()
                            ->children()
                                ->scalarNode('directory')->defaultValue('%kernel.project_dir%/assets/src')->end()
                                ->variableNode('groups')->defaultValue([])->end()
                                ->booleanNode('ignore_internal')->defaultTrue()->end()
                                ->scalarNode('language')->defaultValue('typescript')->end()
                                ->scalarNode('namespace_prefix_input')->defaultValue('App')->end()
                                ->scalarNode('namespace_prefix_output')->defaultValue('interfaces')->end()
                                ->variableNode('source')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
