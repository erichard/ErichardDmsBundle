<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Erichard\DmsBundle\DocumentNodeInterface;

/**
 * @ORM\Entity
 */
class DocumentNodeAuthorization
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Erichard\DmsBundle\Entity\DocumentNode")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id")
     */
    protected $node;

    /**
     * @ORM\Column(type="string", length="50")
     */
    protected $role;

    /**
     * @ORM\Column(type="integer")
     */
    protected $allow;

    /**
     * @ORM\Column(type="integer")
     */
    protected $deny;

    public function __construct()
    {
        $this->allow = 0;
        $this->deny = 0;
    }

    public function setNode(DocumentNodeInterface $node)
    {
        $this->node = $node;

        return $this;
    }

    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    public function setAllow($allow)
    {
        $this->allow = $allow;

        return $this;
    }

    public function setDeny($deny)
    {
        $this->deny = $deny;

        return $this;
    }

    public function getAllow()
    {
        return $this->allow;
    }

    public function getDeny()
    {
        return $this->deny;
    }
}
