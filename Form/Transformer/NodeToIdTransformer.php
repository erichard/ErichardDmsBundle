<?php

namespace Erichard\DmsBundle\Form\Transformer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\DataTransformerInterface;

class NodeToIdTransformer implements DataTransformerInterface
{
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function transform($node)
    {
        if (null === $node) {
            return '';
        }

        return $node->getId();
    }

    public function reverseTransform($id)
    {
        return $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->find($id)
        ;
    }
}
