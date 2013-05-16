<?php

namespace Erichard\DmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 */
class DocumentAuthorization
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Erichard\DmsBundle\Entity\Document")
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id")
     */
    protected $document;

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
