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

    public function getFilters()
    {
        return array(
            'filesize' => new \Twig_Filter_Method($this, 'getFileSize')
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

    public function getFileSize($sizeInBytes)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($sizeInBytes/pow(1024,($i=floor(log($sizeInBytes,1024)))),2).' '.$unit[$i];
    }
}
