<?php

namespace Erichard\DmsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ErichardDmsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (count($config['permission']['roles']) === 0
            && empty($config['permission']['roleProvider'])
        )  {
            throw new \RuntimeException("The DMS need to know which roles it can use. Please configure 'erichard_dms.permission.roles' or 'erichard_dms.permission.roleProvider.");
        }

        if (!empty($config['permission']['roleProvider'])
            && !$container->hasDefinition($config['permission']['roleProvider'])
        ) {
            throw new ServiceNotFoundException($config['permission']['roleProvider']);
        }

        foreach ($config as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $subName => $subValue) {
                    $container->setParameter('dms.'.$name.'.'.$subName, $subValue);
                }
            } else {
                $container->setParameter('dms.'.$name, $value);
            }
        }
    }
}
