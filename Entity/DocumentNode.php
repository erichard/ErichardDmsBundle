<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;

class DocumentNode implements DocumentNodeInterface
{
    protected $id;
    protected $parent;
    protected $nodes;
    protected $documents;
    protected $name;
    protected $slug;
    protected $depth;
    protected $enabled;
    protected $metadatas;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->nodes     = new ArrayCollection();
        $this->metadatas = new ArrayCollection();
        $this->enabled   = true;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(DocumentNodeInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getDocuments()
    {
        return $this->documents;
    }

    public function addDocument(DocumentInterface $document)
    {
        if (!$this->documents->contains($document)) {
            $document->setParent($this);
            $this->documents->add($document);
        }

        return $this;
    }

    public function removeDocument(DocumentInterface $document)
    {
        if ($this->documents->contains($document)) {
            $this->documents->removeElement($document);
        }

        return $this;
    }

    public function getNodes()
    {
        return $this->nodes;
    }

    public function addNode(DocumentNodeInterface $node)
    {
        if (!$this->nodes->contains($node)) {
            $node->setParent($this);
            $this->nodes->add($node);
        }

        return $this;
    }

    public function removeNode(DocumentNodeInterface $node)
    {
        if ($this->nodes->contains($node)) {
            $this->nodes->removeElement($node);
        }

        return $this;
    }

    public function getPath()
    {
        $path = null !== $this->parent ? $this->parent->getPath() : new ArrayCollection();
        $path->add($this);

        return $path;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function getMetadatas()
    {
        return $this->metadatas;
    }

    public function addMetadata(DocumentNodeMetadata $metadata)
    {
        if (!$this->hasMetadata($metadata->getMetadata()->getName())) {
            $metadata->setNode($this);
            $this->metadatas->add($metadata);
        }

        return $this;
    }

    public function getMetadata($name)
    {
        foreach ($this->metadatas as $m) {
            if ($m->getMetadata()->getName() === $name) {
                return $m;
            }
        }

        return false;
    }

    public function removeMetadata(DocumentNodeMetadata $metadata)
    {
        if ($this->metadatas->contains($metadata)) {
            $this->metadatas->removeElement($metadata);
        }

        return $this;
    }

    public function hasMetadata($name)
    {
        foreach ($this->metadatas as $m) {
            if ($m->getMetadata()->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    public function removeEmptyMetadatas()
    {
        foreach ($this->metadatas as $m) {
            if (null === $m->getId() || null === $m->getValue()) {
                $this->metadatas->removeElement($m);
            }
        }
    }
}
