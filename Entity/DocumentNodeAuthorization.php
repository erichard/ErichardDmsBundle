<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
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
}
