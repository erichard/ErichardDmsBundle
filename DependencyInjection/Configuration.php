<?php

namespace Erichard\DmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('erichard_dms');

        $viewModes = array('gallery', 'showcase', 'table', 'content');
        $rootNode
            ->children()
                ->arrayNode('view_modes')
                    ->defaultValue($viewModes)
                    ->prototype('scalar')
                        ->validate()
                            ->ifNotInArray($viewModes)
                            ->thenInvalid('%s is not a valid view mode.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(function($v) { return !is_dir($v); })
                                ->thenInvalid('The given path does not exist : %s')
                            ->end()
                        ->end()
                        ->scalarNode('tmp_path')
                            ->cannotBeEmpty()
                            ->defaultValue('%kernel.root_dir%/tmp')
                            ->validate()
                                ->ifTrue(function($v) { return !is_dir($v); })
                                ->thenInvalid('The given path does not exist : %s')
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('gallery')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('items_per_row')->defaultValue(4)->end()
                        ->scalarNode('image_size')
                            ->defaultValue('260x180')
                            ->validate()
                                ->ifTrue(function($v) { return !preg_match('/\d+x\d+/', $v); })
                                ->thenInvalid('The given size "%s" is not valid. Please use the {width}x{height} format.')
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('content')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('image_size')
                            ->defaultValue('190x80')
                            ->validate()
                                ->ifTrue(function($v) { return !preg_match('/\d+x\d+/', $v); })
                                ->thenInvalid('The given size "%s" is not valid. Please use the {width}x{height} format.')
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('showcase')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('image_size')
                            ->defaultValue('870x400')
                            ->validate()
                                ->ifTrue(function($v) { return !preg_match('/\d+x\d+/', $v); })
                                ->thenInvalid('The given size "%s" is not valid. Please use the {width}x{height} format.')
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('show')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('image_size')
                            ->defaultValue('576x400')
                            ->validate()
                                ->ifTrue(function($v) { return !preg_match('/\d+x\d+/', $v); })
                                ->thenInvalid('The given size "%s" is not valid. Please use the {width}x{height} format.')
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('table')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('image_size')
                            ->defaultValue('32x32')
                            ->validate()
                                ->ifTrue(function($v) { return !preg_match('/\d+x\d+/', $v); })
                                ->thenInvalid('The given size "%s" is not valid. Please use the {width}x{height} format.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    public function addImageSizeNode($defaultSize)
    {
        $builder = new TreeBuilder();
        $node = $builder->root('image_size');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('image_size')
                    ->defaultValue($defaultSize)
                    ->validate()
                        ->ifTrue(function($v) { return !preg_match('/\d+x\d+/', $v); })
                        ->thenInvalid('The given size "%s" is not valid. Please use the {width}x{height} format.')
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
