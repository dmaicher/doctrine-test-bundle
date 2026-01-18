<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @final
 */
class Configuration implements ConfigurationInterface
{
    public const ENABLE_STATIC_CONNECTION = 'enable_static_connection';
    public const CONNECTION_KEYS = 'connection_keys';
    public const STATIC_META_CACHE = 'enable_static_meta_data_cache';
    public const STATIC_QUERY_CACHE = 'enable_static_query_cache';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dama_doctrine_test');

        $root = $treeBuilder->getRootNode();

        $root
            ->addDefaultsIfNotSet()
            ->children()
                ->variableNode(self::ENABLE_STATIC_CONNECTION)
                    ->defaultTrue()
                    ->validate()
                        ->ifTrue(function ($value) {
                            if (is_bool($value)) {
                                return false;
                            }

                            if (!is_array($value)) {
                                return true;
                            }

                            foreach ($value as $k => $v) {
                                if (!is_string($k) || !is_bool($v)) {
                                    return true;
                                }
                            }

                            return false;
                        })
                        ->thenInvalid('Must be a boolean or an array with name -> bool')
                    ->end()
                ->end()
                ->booleanNode(self::STATIC_META_CACHE)->defaultTrue()->end()
                ->booleanNode(self::STATIC_QUERY_CACHE)->defaultTrue()->end()
                ->arrayNode(self::CONNECTION_KEYS)
                    ->normalizeKeys(false)
                    ->variablePrototype()
                    ->end()
                    ->validate()
                    ->ifTrue(function ($value) {
                        if ($value === null) {
                            return false;
                        }

                        if (!is_array($value)) {
                            return true;
                        }

                        foreach ($value as $k => $v) {
                            if (!is_string($k) || !(is_string($v) || is_array($v))) {
                                return true;
                            }

                            if (!is_array($v)) {
                                continue;
                            }

                            if (count($v) !== 2
                                || !is_string($v['primary'] ?? null)
                                || !is_array($v['replicas'] ?? null)
                                || !$this->isAssocStringArray($v['replicas'])
                            ) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->thenInvalid('Must be array<string, string|array{primary: string, replicas: array<string, string>}>')
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function isAssocStringArray(array $value): bool
    {
        foreach ($value as $k => $v) {
            if (!is_string($k) || !is_string($v)) {
                return false;
            }
        }

        return true;
    }
}
