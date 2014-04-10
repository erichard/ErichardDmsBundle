<?php

namespace Erichard\DmsBundle\Event;

use Erichard\DmsBundle\DocumentNodeInterface;
use Symfony\Component\EventDispatcher\Event;

class DmsNodeEvent extends Event
{
    protected $node;

    public function __construct(DocumentNodeInterface $node)
    {
        $this->node = $node;
    }

    public function getNode()
    {
        return $this->node;
    }
}
