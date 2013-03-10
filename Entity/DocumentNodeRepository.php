<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\ORM\EntityRepository;

class DocumentNodeRepository extends EntityRepository
{
    public function findBySlugWithChildren($slug)
    {
        return $this->createQuery(
            'SELECT n, d, nodes FROM Erichard\DmsBundle\Entity\DocumentNode n LEFT JOIN n.nodes nodes'
        )->getOneOrNullResult();
    }
}

