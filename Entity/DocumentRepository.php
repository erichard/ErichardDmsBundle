<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Erichard\DmsBundle\Entity\DocumentMetadata;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class DocumentRepository extends EntityRepository
{
    protected $securityContext;

    public function setSecurityContext(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    public function findOneBySlugAndNode($documentSlug, $nodeSlug)
    {
        return $this
            ->createQueryBuilder('d')
            ->addSelect('d', 'm', 'p')
            ->innerJoin('d.node', 'n')
            ->leftJoin('d.parent', 'p')
            ->leftJoin('d.metadatas', 'm')
            ->where('d.slug = :document AND n.slug = :node')
            ->setParameter('document', $documentSlug)
            ->setParameter('node', $nodeSlug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByMetadatas(array $metadatas = array(), array $sortBy = array(), $limit = 10)
    {
        $qb = $this
            ->createQueryBuilder('d')
            ->innerJoin('d.metadatas', 'dm')
            ->innerJoin('dm.metadata', 'm')
        ;

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


    public function findDocumentOrThrowError($documentSlug, $nodeSlug)
    {
        $document = $this->findOneBySlugAndNode($documentSlug, $nodeSlug);

        if (null == $document) {
            throw new NotFoundHttpException(sprintf('Document not found : %s', $documentSlug));
        }

        if (!$this->getSecurityContext()->isGranted('VIEW', $document)) {
            throw new AccessDeniedHttpException('You are not allowed to view this document.');
        }

        $metadatas = $this
            ->getEntityManager()
            ->getRepository('Erichard\DmsBundle\Entity\Metadata')
            ->findByScope(array('document', 'both'))
        ;

        foreach ($metadatas as $m) {
            if (!$document->hasMetadata($m->getName())) {
                $metadata = new DocumentMetadata($m);
                $document->addMetadata($metadata);
            }
        }

        return $document;
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
