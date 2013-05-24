<?php

namespace Erichard\DmsBundle\Entity;

use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class DocumentNodeRepository extends ClosureTreeRepository
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

    public function findNodeOrThrowError($slug)
    {
        $documentNode = $this->findOneBySlugWithChildren($slug);

        if (null == $documentNode) {
            throw new NotFoundHttpException(sprintf('Node not found : %s', $slug));
        }

        if (!$this->getSecurityContext()->isGranted('VIEW', $documentNode)) {
            throw new AccessDeniedHttpException('You are not allowed to view this node.');
        }

        foreach ($documentNode->getNodes() as $node) {
            if (!$this->getSecurityContext()->isGranted('VIEW', $node)) {
                $documentNode->removeNode($node);
            }
        }

        foreach ($documentNode->getDocuments() as $document) {
            if (!$this->getSecurityContext()->isGranted('VIEW', $document)) {
                $documentNode->removeDocument($document);
            }
        }

        $metadatas = $this
            ->getEntityManager()
            ->getRepository('Erichard\DmsBundle\Entity\Metadata')
            ->findByScope(array('node', 'both'))
        ;

        foreach ($metadatas as $m) {
            if (!$documentNode->hasMetadata($m->getName())) {
                $metadata = new DocumentNodeMetadata($m);
                $documentNode->addMetadata($metadata);
            }
        }

        return $documentNode;
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

        $query = "SELECT a.role, a.allow, a.deny ".
            "FROM $table c INNER JOIN $authorizationTableName a ON (a.node_id = c.ancestor) ".
            "WHERE c.descendant = :node AND a.role IN ($queryRoles) ORDER BY c.depth ASC "
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

        $query = "SELECT a.* ".
            "FROM $table c INNER JOIN $authorizationTableName a ON (a.node_id = c.ancestor) ".
            "WHERE a.role IN ($queryRoles) ORDER BY c.depth ASC "
        ;

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

}
