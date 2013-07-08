<?php

namespace Erichard\DmsBundle;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\Entity\Document;
use Erichard\DmsBundle\Entity\DocumentMetadata;
use Erichard\DmsBundle\Entity\DocumentNodeMetadata;
use Erichard\DmsBundle\MimeTypeManager;
use GetId3\GetId3Core as GetId3;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class DmsManager
{
    protected $registry;
    protected $securityContext;
    protected $mimeTypeManager;
    protected $router;
    protected $options;


    public function __construct(
        Registry $registry,
        SecurityContextInterface $securityContext,
        MimeTypeManager $mimeTypeManager,
        RouterInterface $router,
        array $options = array()
    )
    {
        $this->registry = $registry;
        $this->securityContext = $securityContext;
        $this->mimeTypeManager = $mimeTypeManager;
        $this->router = $router;
        $this->options = $options;
    }

    public function getRoots()
    {
        $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->getRoots()
        ;

        foreach ($nodes as $idx => $node) {
            try {
                $this->prepareNode($node);
            } catch (AccessDeniedException $e) {
                unset($nodes[$idx]);
            }
        }

        return $nodes;
    }

    public function getNodeById($nodeId)
    {
        $documentNode = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findOneByIdWithChildren($nodeId)
        ;

        if (null !== $documentNode) {
            $this->prepareNode($documentNode);
        }

        return $documentNode;
    }

    public function getNode($nodeSlug)
    {
        $documentNode = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findOneBySlugWithChildren($nodeSlug)
        ;

        if (null !== $documentNode) {
            $this->prepareNode($documentNode);
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

        if (null !== $document) {
            $this->prepareDocument($document);
        }

        return $document;
    }

    public function findNodesByMetadatas(array $metatadas = array(), array $sortBy = array())
    {
        $documentNodes = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findByMetadatas($metatadas, $sortBy)
        ;

        return array_filter($documentNodes, function(DocumentNodeInterface $documentNode) {
            return $this->isViewable($documentNode);
        });
    }

    public function findDocumentsByMetadatas(array $metatadas = array(), array $sortBy = array())
    {
        $documents = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\Document')
            ->findByMetadatas($metatadas, $sortBy)
        ;

        return array_filter($documents, function(DocumentInterface $document) {
            return $this->isViewable($document);
        });
    }

    protected function prepareNode(DocumentNodeInterface $documentNode)
    {
        if (!$this->isViewable($documentNode)) {
            throw new AccessDeniedException('You are not allowed to view this node : '. $documentNode->getName());
        }

        foreach ($documentNode->getNodes() as $node) {
            if (!$this->isViewable($node)) {
                $documentNode->removeNode($node);
            }
        }

        foreach ($documentNode->getDocuments() as $document) {
            if (!$this->isViewable($document)) {
                $documentNode->removeDocument($document);
            }

            $this->prepareDocument($document);
        }

        return $documentNode;
    }

    protected function prepareDocument(DocumentInterface $document)
    {
        if (!$this->isViewable($document)) {
            throw new AccessDeniedException('You are not allowed to view this document: '. $document->getName());
        }

        $mimetype = $this->mimeTypeManager->getMimeType($this->options['storage_path'] . DIRECTORY_SEPARATOR . $document->getFilename());

        $document->setMimeType($mimetype);

        return $document;
    }

    public function getDocumentMimetype(DocumentInterface $document)
    {
        $absPath  = $this->options['storage_path'] . DIRECTORY_SEPARATOR . $document->getFilename();
        $getID3 = new GetId3;
        $info = $getID3->analyze($absPath);

        return isset($info['mime_type'])? $info['mime_type'] : 'unknown';
    }

    public function getNodeMetadatas(DocumentNodeInterface $node)
    {
        $metadatas = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\Metadata')
            ->findByScope(array('node', 'both'))
        ;

        foreach ($metadatas as $m) {
            if (!$node->hasMetadata($m->getName())) {
                $metadata = new DocumentNodeMetadata($m);
                $node->addMetadata($metadata);
            }
        }
    }

    public function getDocumentMetadatas(DocumentInterface $document)
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
    }

    public function generateThumbnail(DocumentInterface $document, $dimension)
    {
        list($width, $height) = array_map('intval', explode('x', $dimension));

        $size = new Box($width, $height);
        $mode = ImageInterface::THUMBNAIL_INSET;
        $absPath = $this->options['storage_path'] . DIRECTORY_SEPARATOR . $document->getFilename();

        $mimetype = $this->mimeTypeManager->getMimeType($absPath);

        if (filesize($absPath) >= 5000000 || max([$width,$height]) < 100 ||
            strpos($mimetype, 'image') === false && strpos($mimetype, 'pdf') === false) {
            $absPath = $this->mimeTypeManager->getMimetypeImage($absPath, max([$width, $height]));
        }

        $url = $this->router->generate('erichard_dms_document_preview', array(
            'dimension' => $dimension,
            'node'      => $document->getNode()->getSlug(),
            'document'  => $document->getSlug()
        ));

        $cacheFile = $this->options['web_path'] . $url;

        try {
            if (pathinfo($absPath, PATHINFO_EXTENSION) === 'pdf') {
                $absPath .= '[0]';
            }
            $imagick = new \Imagick($absPath);

            $imagick->setCompression(\Imagick::COMPRESSION_LZW);
            $imagick->setResolution(72, 72);
            $imagick->setCompressionQuality(90);
            $image = new \Imagine\Imagick\Image($imagick);

            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0777, true);
            }
            $image
                ->thumbnail($size, $mode)
                ->save($cacheFile, array('quality' => 90))
            ;
        } catch (\Exception $e) {
            $cacheFile = $this->mimeTypeManager->getMimetypeImage(
                $this->options['storage_path'] . DIRECTORY_SEPARATOR . $document->getFilename(),
                max([$width, $height])
            );
        }

        return $cacheFile;
    }

    public function isViewable($entity)
    {
        $editPermission = $entity instanceof Document ? 'DOCUMENT_EDIT' : 'NODE_EDIT';

        return $this->securityContext->isGranted('VIEW', $entity) &&
            ($this->securityContext->isGranted($editPermission, $entity) || $entity->isEnabled())
        ;
    }
}
