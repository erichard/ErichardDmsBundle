<?php

namespace Erichard\DmsBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Erichard\DmsBundle\DocumentInterface;

class DocumentListener
{
    protected $storagePath;

    public function __construct($storagePath)
    {
        $this->storagePath = $storagePath;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        if ($entity instanceof DocumentInterface) {
            $absFileName = $this->storagePath .'/'. $entity->getFilename();
            $entity->setFile(new \SplFileInfo($absFileName));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $entity->setMimeType(finfo_file($finfo, $absFileName));
        }
    }
}
