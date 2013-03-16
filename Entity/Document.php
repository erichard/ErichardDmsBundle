<?php

namespace Erichard\DmsBundle\Entity;

use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;

class Document implements DocumentInterface
{
    protected $id;
    protected $parent;
    protected $filename;
    protected $mimeType;
    protected $name;

    public function __construct(DocumentNodeInterface $parent, $content)
    {
        $this->parent   = $parent;
        $this->filename = $content;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getContent()
    {
        if (!is_readable($this->filename)) {
            throw new \RuntimeException(sprintf('The file "%s" is not readable.',$this->filename));
        }

        return file_get_contents($this->filename);
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getPath()
    {
        $path = $this->parent->getPath();
        $path->add($this->parent);

        return $path;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
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

    public function getSize()
    {
        return filesize($this->filename);
    }
}
