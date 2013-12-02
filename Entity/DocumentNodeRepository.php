<?php

namespace Erichard\DmsBundle\Entity;

use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;

class DocumentNodeRepository extends ClosureTreeRepository
{
    public function findSortField($slug)
    {
        $ret = $this
            ->createQueryBuilder('n')
            ->select('partial n.{id}, partial m.{id, value}')
            ->innerJoin('n.metadatas', 'm')
            ->innerJoin('m.metadata', 'meta', 'WITH', 'meta.name = :meta')
            ->where('n.slug = :node')
            ->setParameter('node', $slug)
            ->setParameter('meta', 'sortBy')
            ->getQuery()
            ->getArrayResult()
        ;

        return count($ret) > 0 ? current($ret)['metadatas'][0]['value'] : null;
    }

    public function findOneBySlugWithChildren($slug, $sortByField = 'name', $sortByOrder = 'ASC')
    {
        return $this
            ->createQueryBuilder('n')
            ->addSelect('nodes','d', 'p', 'm')
            ->leftJoin('n.nodes', 'nodes', 'nodes.id')
            ->leftJoin('n.documents', 'd', 'd.id')
            ->leftJoin('n.parent', 'p', 'd.id')
            ->leftJoin('n.metadatas', 'm', 'm.metadata.name')
            ->where('n.slug = :node')
            ->orderBy('nodes.'.$sortByField, $sortByOrder)
            ->addOrderBy('d.'.$sortByField, $sortByOrder)
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

            $descendants = array_map(function ($row) { return $row['id']; }, $descendants);

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
        $queryRoles = array_map(function ($role) { return "'$role'"; }, $roles);
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

        $nodeTable = $this
            ->getEntityManager()
            ->getClassMetadata('Erichard\DmsBundle\Entity\DocumentNode')
            ->getTableName()
        ;

        $query = "SELECT a.role, a.allow, a.deny, c.depth, n.reset_permission ".
            "FROM $table c INNER JOIN $nodeTable n ON (c.ancestor = n.id) LEFT JOIN $authorizationTableName a ON (a.node_id = c.ancestor)".
            "WHERE c.descendant = :node AND (a.role IN ($queryRoles) OR n.reset_permission = 1) ORDER BY depth DESC"
        ;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->bindValue("node", $id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getAuthorizationsByRoles(array $roles)
    {
        $queryRoles = array_map(function ($role) { return "'$role'"; }, $roles);
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

        $nodeTable = $this
            ->getEntityManager()
            ->getClassMetadata('Erichard\DmsBundle\Entity\DocumentNode')
            ->getTableName()
        ;

        $query = "SELECT a.role, a.allow, a.deny, c.depth, n.reset_permission ".
            "FROM $table c INNER JOIN $nodeTable n ON (c.ancestor = n.id) LEFT JOIN $authorizationTableName a ON (a.node_id = c.ancestor) ".
            "WHERE c.descendant = :node AND (a.role IN ($queryRoles) OR n.reset_permission = 1) ORDER BY depth DESC"
        ;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
