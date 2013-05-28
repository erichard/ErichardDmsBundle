<?php

namespace Erichard\DmsBundle\Command;

use Erichard\DmsBundle\Entity\Document;
use Erichard\DmsBundle\Entity\DocumentNode;
use Erichard\DmsBundle\Import\FilesystemImporter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDocumentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dms:document:import')
            ->setDescription('Import a document tree into the DMS.')
            ->addArgument('source', InputArgument::REQUIRED, 'From where the document will be imported.')
            ->addOption('exclude', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Exclude some files or directories.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceDir = $input->getArgument('source');
        $manager   = $this->getContainer()->get('doctrine')->getManager();

        // Prepare the destination node
        $dest = new DocumentNode();
        $dest->setName('Imported on '. date('Y-m-d') . ' at '. date('H:i'));

        // Launch the importer
        $importer = new FilesystemImporter($this->getContainer()->get('doctrine')->getManager(), array(
            'storage_path' => $this->getContainer()->getParameter('dms.storage.path')
        ));
        $importer->import($sourceDir, $dest, $input->getOption('exclude'));
    }
}
