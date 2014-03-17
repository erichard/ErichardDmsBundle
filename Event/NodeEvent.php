<?php

namespace Erichard\DmsBundle\Event;

class NodeEvent
{
    const CREATE = 'dms.node.create';
    const UPDATE = 'dms.node.update';
    const DELETE = 'dms.node.delete';

    protected $node;
    protected $name;

    public function __construct($name, \Erichard\DmsBundle\Entity\DocumentNode $node)
    {
        $this->setName($name);
        $this->setNode($node);
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
