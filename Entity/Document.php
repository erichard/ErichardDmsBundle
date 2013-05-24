<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;
use Erichard\DmsBundle\Entity\DocumentMetadata;

class Document implements DocumentInterface
{
    protected $id;
    protected $node;
    protected $name;
    protected $filename;
    protected $originalName;
    protected $mimeType;
    protected $type;
    protected $slug;
    protected $enabled;
    protected $metadatas;

    public function __construct(DocumentNodeInterface $node)
    {
        $this->node = $node;
        $this->type = DocumentInterface::TYPE_FILE;
        $this->enabled = true;
        $this->metadatas = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getContent()
    {
        if (!is_readable($this->filename)) {
            throw new \RuntimeException(sprintf('The file "%s" is not readable.',$this->filename));
        }

        return file_get_contents($this->filename);
    }

    public function getNode()
    {
        return $this->node;
    }

    public function getPath()
    {
        $path = $this->node->getPath();
        $path->add($this->node);

        return $path;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
        if (null === $this->originalName) {
            $this->originalName = basename($filename);
        }
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFilename()
    {
        return $this->filename;
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

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getOriginalName()
    {
        return $this->originalName;
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

    public function addMetadata(DocumentMetadata $metadata)
    {
        if (!$this->hasMetadata($metadata->getMetadata()->getName())) {
            $metadata->setDocument($this);
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

    public function removeMetadata(DocumentMetadata $metadata)
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

    public function getComputedFilename()
    {
        if (null === $this->id) {
            throw new \RuntimeException('You must persist the document before calling getComputedFilename().');
        }

        $reverseId = str_pad($this->id, 8, '0', STR_PAD_LEFT);
        $path = '';

        for ($i = 0; $i < 6; $i+=2) {
            $path .= substr($reverseId, $i, 2) . DIRECTORY_SEPARATOR;
        }

        $extension = pathinfo($this->originalName,PATHINFO_EXTENSION);
        $extension = empty($extension)? 'noext' : $extension;

        $path .= $reverseId . '.' . $extension;

        return $path;
    }

    public function getExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    public function removeEmptyMetadatas()
    {
        foreach ($this->metadatas as $m) {
            if (null === $m->getId() || null === $m->getValue()) {
                $this->metadatas->removeElement($m);
            }
        }

        $this->getNode()->removeEmptyMetadatas();
    }
}
