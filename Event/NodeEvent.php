<?php

namespace Erichard\DmsBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class NodeEvent extends Event
{
    protected $node;

    public function __construct(\Erichard\DmsBundle\Entity\DocumentNode $node)
    {
        $this->setNode($node);
    }

    public function getNode()
    {
        return $this->node;
    }

    public function setNode(\Erichard\DmsBundle\Entity\DocumentNode $node)
    {
        $this->node = $node;

        return $this;
    }

}
