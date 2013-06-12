<?php

namespace Erichard\DmsBundle;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Erichard\DmsBundle\Entity\DocumentMetadata;
use Erichard\DmsBundle\Entity\DocumentNodeMetadata;
use GetId3\GetId3Core as GetId3;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class DmsManager
{
    protected $registry;
    protected $securityContext;
    protected $options;

    public function __construct(Registry $registry, SecurityContextInterface $securityContext, array $options = array())
    {
        $this->registry = $registry;
        $this->securityContext = $securityContext;
        $this->options = $options;
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

            $this->prepareDocument($document);
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

    public function getDocument($documentSlug, $nodeSlug)
    {
        $document = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\Document')
            ->findOneBySlugAndNode($documentSlug, $nodeSlug)
        ;

        if (null == $document) {
            throw new NotFoundHttpException(sprintf('Document not found : %s', $documentSlug));
        }

        if (!$this->securityContext->isGranted('VIEW', $document)) {
            throw new AccessDeniedHttpException('You are not allowed to view this document.');
        }

        $this->prepareDocument($document);

        return $document;
    }


    public function prepareDocument(DocumentInterface $document)
    {
        // Set all metadata on the document
        $metadatas = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\Metadata')
            ->findByScope(array('document', 'both'))
        ;

        foreach ($metadatas as $m) {
            if (!$document->hasMetadata($m->getName())) {
                $metadata = new DocumentMetadata($m);
                $document->addMetadata($metadata);
            }
        }

        // Set the mimetype
        $absPath  = $this->options['storage_path'] . DIRECTORY_SEPARATOR . $document->getFilename();
        $getID3 = new GetId3;
        $info = $getID3->analyze($absPath);
        $document->setMimeType($info['mime_type']);

        return $document;
    }
}
