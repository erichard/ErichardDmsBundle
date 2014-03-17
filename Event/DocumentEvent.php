<?php

namespace Erichard\DmsBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class DocumentEvent extends Event
{
    protected $document;

    public function __construct(\Erichard\DmsBundle\Entity\Document $document)
    {
        $this->setDocument($document);
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
