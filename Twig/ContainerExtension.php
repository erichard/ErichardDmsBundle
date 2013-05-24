<?php

namespace Erichard\DmsBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerExtension extends \Twig_Extension
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return array(
            'parameter' => new \Twig_Function_Method($this, 'getParameter')
        );
    }

    public function getParameter($paramName)
    {
        return $this->container->getParameter($paramName);
    }

    public function getName()
    {
        return 'container_extension';
    }
}
