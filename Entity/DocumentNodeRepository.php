<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\ORM\EntityRepository;

class DocumentNodeRepository extends EntityRepository
{
    public function findOneBySlugWithChildren($slug)
    {
        return $this
            ->createQueryBuilder('n')
            ->addSelect('nodes','d', 'p')
            ->leftJoin('n.nodes', 'nodes', 'nodes.id')
            ->leftJoin('n.documents', 'd', 'd.id')
            ->leftJoin('n.parent', 'p', 'd.id')
            ->where('n.slug = :node')
            ->setParameter('node', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}

