<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder
            ->root('nelmio_api_doc')
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return !isset($v['areas']) && isset($v['routes']);
                })
                ->then(function ($v) {
                    $v['areas'] = $v['routes'];
                    unset($v['routes']);
                    @trigger_error('The `nelmio_api_doc.routes` config option is deprecated. Please use `nelmio_api_doc.areas` instead (just replace `routes` by `areas` in your config).', E_USER_DEPRECATED);

                    return $v;
                })
            ->end()
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return isset($v['routes']);
                })
                ->thenInvalid('You must not use both `nelmio_api_doc.areas` and `nelmio_api_doc.routes` config options. Please update your config to only use `nelmio_api_doc.areas`.')
            ->end()
            ->children()
                ->arrayNode('documentation')
                    ->useAttributeAsKey('key')
                    ->info('The documentation used as base')
                    ->example(['info' => ['title' => 'My App']])
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('areas')
                    ->info('Filter the routes that are documented')
                    ->defaultValue(['default' => ['path_patterns' => []]])
                    ->beforeNormalization()
                        ->ifTrue(function ($v) {
                            return empty($v) or isset($v['path_patterns']);
                        })
                        ->then(function ($v) {
                            return ['default' => $v];
                        })
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return !isset($v['default']);
                        })
                        ->thenInvalid('You must specify a `default` area under `nelmio_api_doc.areas`.')
                    ->end()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('path_patterns')
                                ->example(['^/api', '^/api(?!/admin)'])
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('models')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('use_jms')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
