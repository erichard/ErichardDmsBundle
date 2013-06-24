<?php

namespace Erichard\DmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateThumbnailsCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('dms:thumbnails:generate')
            ->setDescription('Generate thumbnails for all documents.')
            ->addArgument('sizes', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'List of thumbnail to generated.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sizes = $input->getArgument('sizes');

        $iterator = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->createQuery('SELECT d FROM Erichard\DmsBundle\Entity\Document d')
            ->iterate()
        ;

        $dmsManager = $this->getContainer()->get('dms.manager');

        foreach ($iterator as $row) {
            $document = $row[0];

            foreach ($sizes as $size) {
                $thumbnail = $dmsManager->generateThumbnail($document, $size);
                $output->writeLn('<info> > </info>'.$thumbnail);
            }
        }

    }
}
