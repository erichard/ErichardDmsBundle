<?php

namespace Erichard\DmsBundle;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Erichard\DmsBundle\Entity\DocumentNodeMetadata;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class DmsManager
{
    protected $registry;
    protected $securityContext;

    public function __construct(Registry $registry, SecurityContextInterface $securityContext)
    {
        $this->registry = $registry;
        $this->securityContext = $securityContext;
    }

    public function getRoots()
    {
        $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->getRoots()
        ;

        return array_filter($nodes, function($node) {
            return $this->securityContext->isGranted('VIEW', $node);
        });
    }

    public function getNode($nodeSlug)
    {
        $documentNode = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findOneBySlugWithChildren($nodeSlug)
        ;

        if (null == $documentNode) {
            throw new NotFoundHttpException(sprintf('Node not found : %s', $nodeSlug));
        }

        if (!$this->securityContext->isGranted('VIEW', $documentNode)) {
            throw new AccessDeniedHttpException('You are not allowed to view this node.');
        }

        foreach ($documentNode->getNodes() as $node) {
            if (!$this->securityContext->isGranted('VIEW', $node)) {
                $documentNode->removeNode($node);
            }
        }

        foreach ($documentNode->getDocuments() as $document) {
            if (!$this->securityContext->isGranted('VIEW', $document)) {
                $documentNode->removeDocument($document);
            }
        }

        $metadatas = $this
            ->registry
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
}
