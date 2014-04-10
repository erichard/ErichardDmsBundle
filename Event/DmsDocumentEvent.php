<?php

namespace Erichard\DmsBundle\Event;

use Erichard\DmsBundle\DocumentInterface;
use Symfony\Component\EventDispatcher\Event;

class DmsDocumentEvent extends Event
{
    protected $document;

    public function __construct(DocumentInterface $document)
    {
        $this->document = $document;
    }

    public function getDocument()
    {
        return $this->document;
    }
}
