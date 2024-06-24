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

        $normalizeSourcesItem = function (mixed $v) {
            if ([] === $v || null === $v || '' === $v) {
                return null;
            }
            // Simply a string means a type with no configuration.
            if (\is_string($v)) {
                // Or a user given path.
                if (\str_contains($v, \DIRECTORY_SEPARATOR) || \str_contains($v, '/')) {
                    return ['type' => 'finder', 'directory' => $v];
                }
                return ['type' => $v];
            }
            // Array with no type is an array list.
            if (\is_array($v) && !isset($v['type'])) {
                return ['type' => 'array', 'classes' => $v];
            }
            throw new \InvalidArgumentException();
        };

        $normalizeSources = function (mixed $v) use ($normalizeSourcesItem) {
            if ([] === $v || null === $v || '' === $v) {
                return null;
            }
            // A single string means a single source.
            if (\is_string($v)) {
                return [$normalizeSourcesItem($v)];
            }
            if (!\is_array($v)) {
                throw new \InvalidArgumentException();
            }
            return \array_map($normalizeSourcesItem, $v);
        };

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
                            ->variableNode('sources')
                                ->beforeNormalization()->always()->then($normalizeSources)->end()
                            ->end()
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
                                ->variableNode('sources')
                                    ->isRequired()
                                    ->beforeNormalization()->always()->then($normalizeSources)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
