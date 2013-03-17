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

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->nodes     = new ArrayCollection();
    }

    public function getId()
    {
        return $this->getId();
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
}

