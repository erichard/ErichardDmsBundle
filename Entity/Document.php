<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;
use Erichard\DmsBundle\Entity\Behavior\TranslatableEntity;
use Erichard\DmsBundle\Entity\DocumentMetadata;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

class Document implements DocumentInterface, Translatable
{
    use TranslatableEntity;

    protected $id;
    protected $node;
    protected $name;
    protected $filename;
    protected $thumbnail;
    protected $originalName;
    protected $mimeType;
    protected $type;
    protected $slug;
    protected $enabled;
    protected $metadatas;
    protected $createdAt;
    protected $updatedAt;
    protected $parent;
    protected $aliases;
    protected $filesize;

    public function __construct(DocumentNodeInterface $node)
    {
        $this->node = $node;
        $this->type = DocumentInterface::TYPE_FILE;
        $this->enabled = true;
        $this->metadatas = new ArrayCollection();
        $this->aliases = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;
        $this->slug = null;
        $this->createdAt = null;
        $this->updatedAt = null;
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

    public function setNode(DocumentNodeInterface $node)
    {
        $this->node = $node;

        return $this;
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
        return $this->isLink()? $this->parent->getFilename() : $this->filename;
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

    public function removeMetadataByName($metadataName)
    {
        if ($this->hasMetadata($metadataName)) {
            $this->removeMetadata($this->getMetadata($metadataName));
        }

        return $this;
    }

    public function removeEmptyMetadatas($strict = false)
    {
        foreach ($this->metadatas as $m) {
            if (($strict && null === $m->getId()) || null === $m->getValue()) {
                $this->metadatas->removeElement($m);
            }
        }
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

    public function getAliases()
    {
        return $this->aliases;
    }

    public function addAlias(DocumentInterface $document)
    {
        if (!$this->aliases->contains($document)) {
            $document->setParent($this);
            $this->aliases->add($document);
        }

        return $this;
    }

    public function removeAlias(DocumentInterface $document)
    {
        if ($this->aliases->contains($document)) {
            $this->aliases->removeElement($document);
        }

        return $this;
    }

    public function setParent(DocumentInterface $document)
    {
        $this->parent = $document;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function isLink()
    {
        return $this->parent !== null;
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

    /**
     * Sets createdAt.
     *
     * @param DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets updatedAt.
     *
     * @param DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    public function getFilesize()
    {
        return $this->filesize;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }
}
