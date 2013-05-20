<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\ORM\EntityRepository;

class DocumentRepository extends EntityRepository
{
    public function findOneBySlugAndNode($documentSlug, $nodeSlug)
    {
        return $this
            ->createQueryBuilder('d')
            ->addSelect('d', 'm')
            ->leftJoin('d.metadatas', 'm')
            ->where('d.slug = :document')
            ->setParameter('document', $documentSlug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getDocumentAuthorizationsByRoles($id, array $roles)
    {
        $queryRoles = array_map(function($role) { return "'$role'"; }, $roles);
        $queryRoles = implode(',', $queryRoles);

        $authorizationTableName = $this->getEntityManager()->getClassMetadata('Erichard\DmsBundle\Entity\DocumentAuthorization')->getTableName();

        $query = "SELECT a.role, a.allow, a.deny FROM $authorizationTableName a WHERE a.document_id = :document AND a.role IN ($queryRoles)";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->bindValue('document', $id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getAuthorizationsByRoles(array $roles)
    {
        $queryRoles = array_map(function($role) { return "'$role'"; }, $roles);
        $queryRoles = implode(',', $queryRoles);

        $authorizationTableName = $this->getEntityManager()->getClassMetadata('Erichard\DmsBundle\Entity\DocumentAuthorization')->getTableName();

        $query = "SELECT a.* FROM $authorizationTableName a WHERE a.role IN ($queryRoles)";

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
