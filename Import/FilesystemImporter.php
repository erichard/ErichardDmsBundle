<?php

namespace Erichard\DmsBundle\Import;

use Doctrine\ORM\EntityManager;
use Erichard\DmsBundle\DocumentNodeInterface;
use Erichard\DmsBundle\Entity\Document;
use Erichard\DmsBundle\Entity\DocumentNode;
use Symfony\Component\Finder\Finder;

class FilesystemImporter
{
    protected $em;
    protected $options;

    public function __construct(EntityManager $em, array $options = array())
    {
        $this->em = $em;
        $this->options = $options;
    }

    public function import($sourceDir, DocumentNodeInterface $targetNode, array $excludes = array())
    {
        if (!is_dir($sourceDir)) {
            throw new \InvalidArgumentException(sprintf('The directory %s does not exist', $sourceDir));
        }

        $manager = $this->em;
        $currentNode = array( 0 => $targetNode );

        $finder = new Finder();
        $finder->in($sourceDir);

        $files = $finder->getIterator();

        foreach ($files as $file) {

            foreach ($excludes as $exclude) {
                if (strpos($file->getRelativePathname(), $exclude) !== false) {
                    continue 2;
                }
            }

            $depth = $files->getDepth();
            if ($file->isDir()) {
                $node = new DocumentNode();
                $node
                    ->setParent($currentNode[$depth])
                    ->setName($file->getBaseName())
                ;
                $manager->persist($node);
                $currentNode[$depth+1] = $node;
            } elseif ($file->isFile()) {
                $document = new Document($currentNode[$depth]);
                $document
                    ->setName($file->getBaseName())
                    ->setOriginalName($file->getBaseName())
                    ->setFilename($file->getBaseName())
                ;
                $manager->persist($document);
                $manager->flush();

                $document->setFilename($document->getComputedFilename());

                $destFile = $this->options['storage_path'] . '/' . $document->getFilename();

                if (!is_dir(dirname($destFile))) {
                    mkdir(dirname($destFile), 0755, true);
                }
                copy($file->getRealPath(), $destFile);

                $manager->persist($document);
            }

            $manager->flush();
        }
    }
}
