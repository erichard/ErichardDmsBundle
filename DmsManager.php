<?php

namespace Erichard\DmsBundle;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Erichard\DmsBundle\Entity\Document;
use Erichard\DmsBundle\Entity\DocumentMetadata;
use Erichard\DmsBundle\Entity\DocumentNodeMetadata;
use Imagick;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\RGB;
use Imagine\Imagick\Image;
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
    protected $locale;

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
        $this->locale  = \Locale::getDefault();
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
        $registry = $this->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
        ;

        $sortField = $registry->findSortField($nodeSlug);

        if (null !== $sortField) {
            list($sortByField, $sortByOrder) = explode(',', $sortField);

            $documentNode = $registry->findOneBySlugWithChildren($nodeSlug, $sortByField, $sortByOrder);
        } else {
            $documentNode = $registry->findOneBySlugWithChildren($nodeSlug);
        }

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

    public function findNodesByMetadatas($node = null, array $metatadas = array(), array $sortBy = array())
    {
        $documentNodes = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findByMetadatas($node, $metatadas, $sortBy)
        ;

        return array_filter($documentNodes, function (DocumentNodeInterface $documentNode) {
            return $this->isViewable($documentNode);
        });
    }

    public function findDocumentsByMetadatas($node = null, array $metatadas = array(), array $sortBy = array())
    {
        $documents = $nodes = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\Document')
            ->findByMetadatas($node, $metatadas, $sortBy)
        ;

        return array_filter($documents, function (DocumentInterface $document) {
            return $this->isViewable($document);
        });
    }

    public function prepareNode(DocumentNodeInterface $documentNode)
    {
        if (!$this->isViewable($documentNode)) {
            throw new AccessDeniedException('You are not allowed to view this node : '. $documentNode->getName());
        }

        $documentNode->setLocale($this->getLocale());
        foreach ($documentNode->getNodes() as $node) {
            $node->setLocale($this->getLocale());
            if (!$this->isViewable($node)) {
                $documentNode->removeNode($node);
            }
        }

        foreach ($documentNode->getDocuments() as $document) {
            if (!$this->isViewable($document)) {
                $documentNode->removeDocument($document);
                continue;
            }

            $this->prepareDocument($document);
        }

        return $documentNode;
    }

    public function prepareDocument(DocumentInterface $document)
    {
        if (!$this->isViewable($document)) {
            throw new AccessDeniedException('You are not allowed to view this document: '. $document->getName());
        }

        $mimetype = $this
            ->mimeTypeManager
            ->getMimeType($this->options['storage_path'] . DIRECTORY_SEPARATOR . $document->getFilename())
        ;

        $document->setMimeType($mimetype);
        $document->setLocale($this->getLocale());

        return $document;
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
            } else {
                $metadata = $node->getMetadata($m->getName());
                $metadata->setLocale($this->getLocale());
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
            } else {
                $metadata = $document->getMetadata($m->getName());
                $metadata->setLocale($this->getLocale());
            }
        }
    }

    public function generateThumbnail(DocumentInterface $document, $dimension)
    {
        list($width, $height) = array_map('intval', explode('x', $dimension));
        $cacheFile = sprintf(
            '%s/%s/%s/%s.png',
            $this->options['cache_path'],
            $dimension,
            $document->getNode()->getSlug(),
            $document->getSlug()
        );

        if (!is_file($cacheFile)) {
            $size = new Box($width, $height);
            $mode = ImageInterface::THUMBNAIL_INSET;
            $absPath = $this->options['storage_path'] . DIRECTORY_SEPARATOR . (null !== $document->getThumbnail() ? $document->getThumbnail() : $document->getFilename());

            $mimetype = $this->mimeTypeManager->getMimeType($absPath);

            if (!is_file($absPath) || filesize($absPath) >= 100000000) {
                $cacheFile = $this->mimeTypeManager->getMimetypeImage($absPath, max([$width, $height]));
            } else {
                try {
                    if (pathinfo($absPath, PATHINFO_EXTENSION) === 'pdf') {
                        $absPath .= '[0]';
                    }
                    $imagick = new Imagick($absPath);

                    $imagick->setCompression(Imagick::COMPRESSION_LZW);
                    $imagick->setResolution(72, 72);
                    $imagick->setCompressionQuality(90);
                    $image = new Image($imagick, new RGB(), new MetadataBag());

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
            }
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

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

}
