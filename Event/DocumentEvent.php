<?php

namespace Erichard\DmsBundle\Event;

class DocumentEvent
{
    const CREATE = 'dms.document.create';
    const UPDATE = 'dms.document.update';
    const DELETE = 'dms.document.delete';

    protected $document;
    protected $name;

    public function __construct($name, \Erichard\DmsBundle\Entity\Document $document)
    {
        $this->setName($name);
        $this->setDocument($document);
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

    public function getDocument()
    {
        return $this->document;
    }

    public function setDocument(\Erichard\DmsBundle\Entity\Document $document)
    {
        $this->document = $document;

        return $this;
    }

}
