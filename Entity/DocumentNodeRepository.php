<?php

namespace Erichard\DmsBundle\Entity;

use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

class DocumentNodeRepository extends ClosureTreeRepository
{
    public function findOneBySlugWithChildren($slug)
    {
        return $this
            ->createQueryBuilder('n')
            ->addSelect('nodes','d', 'p', 'm')
            ->leftJoin('n.nodes', 'nodes', 'nodes.id')
            ->leftJoin('n.documents', 'd', 'd.id')
            ->leftJoin('n.parent', 'p', 'd.id')
            ->leftJoin('n.metadatas', 'm', 'm.metadata.name')
            ->where('n.slug = :node')
            ->setParameter('node', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByIdWithChildren($id)
    {
        return $this
            ->createQueryBuilder('n')
            ->addSelect('nodes','d', 'p', 'm')
            ->leftJoin('n.nodes', 'nodes', 'nodes.id')
            ->leftJoin('n.documents', 'd', 'd.id')
            ->leftJoin('n.parent', 'p', 'd.id')
            ->leftJoin('n.metadatas', 'm', 'm.metadata.name')
            ->where('n.id = :node')
            ->setParameter('node', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getRoots()
    {
        return $this
            ->createQueryBuilder('n')
            ->where('n.parent IS NULL')
            ->orderBy('n.name')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByMetadatas($node = null, array $metadatas = array(), array $sortBy = array(), $limit = 10)
    {
        $qb = $this
            ->createQueryBuilder('n')
            ->innerJoin('n.metadatas', 'dm')
            ->innerJoin('dm.metadata', 'm')
        ;

        if (null !== $node) {
            $descendants = $this
                ->getEntityManager()
                ->createQuery("SELECT n.id FROM Erichard\DmsBundle\Entity\DocumentNodeClosure c INNER JOIN c.descendant n WHERE c.ancestor = :ancestor")
                ->setParameter('ancestor', $node)
                ->getScalarResult()
            ;

            $descendants = array_map(function($row) { return $row['id']; }, $descendants);

            $qb
                ->andWhere('n.parent IN (:parents)')
                ->setParameter('parents', $descendants)
            ;
        }

        $idx = 0;
        foreach ($metadatas as $metaName => $metaValue) {
            $qb
                ->andWhere("m.name = :meta_$idx AND dm.value = :value_$idx")
                ->setParameter('meta_'.$idx, $metaName)
                ->setParameter('value_'.$idx, $metaValue)
            ;
            $idx++;
        }

        foreach ($sortBy as $key => $value) {
            $qb->addOrderBy($qb->getRootAlias().'.'.$key, $value);
        }

        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getNodeAuthorizationsByRoles($id, array $roles)
    {
        $queryRoles = array_map(function($role) { return "'$role'"; }, $roles);
        $queryRoles = implode(',', $queryRoles);

        $table = $this
            ->getEntityManager()
            ->getClassMetadata('Erichard\DmsBundle\Entity\DocumentNodeClosure')
            ->getTableName()
        ;

        $authorizationTableName = $this
            ->getEntityManager()
            ->getClassMetadata('Erichard\DmsBundle\Entity\DocumentNodeAuthorization')
            ->getTableName()
        ;

        $query = "SELECT a.role, a.allow, a.deny, c.depth ".
            "FROM $table c INNER JOIN $authorizationTableName a ON (a.node_id = c.ancestor)".
            "WHERE c.descendant = :node AND a.role IN ($queryRoles) ORDER BY depth DESC"
        ;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->bindValue("node", $id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getAuthorizationsByRoles(array $roles)
    {
        $queryRoles = array_map(function($role) { return "'$role'"; }, $roles);
        $queryRoles = implode(',', $queryRoles);

        $table = $this
            ->getEntityManager()
            ->getClassMetadata('Erichard\DmsBundle\Entity\DocumentNodeClosure')
            ->getTableName()
        ;

        $authorizationTableName = $this
            ->getEntityManager()
            ->getClassMetadata('Erichard\DmsBundle\Entity\DocumentNodeAuthorization')
            ->getTableName()
        ;

        $query = "SELECT a.role, a.allow, a.deny, c.depth ".
            "FROM $table c INNER JOIN $authorizationTableName a ON (a.node_id = c.ancestor)".
            "WHERE c.descendant = :node AND a.role IN ($queryRoles) ORDER BY depth DESC"
        ;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
