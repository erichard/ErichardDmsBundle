<?php

namespace Erichard\DmsBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;

class DmsExtension extends \Twig_Extension
{
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function getFunctions()
    {
        return array(
            'roots' => new \Twig_Function_Method($this, 'getRoots')
        );
    }

    public function getRoots()
    {
        return $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findByParent(null)
        ;
    }

    public function getName()
    {
        return 'dms_extension';
    }
}
