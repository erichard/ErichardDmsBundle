<?php

namespace Erichard\DmsBundle\Command;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadAliceFixturesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('alice:fixtures:load')
            ->setDescription('Load data fixtures to your database.')
            ->addOption('fixtures', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory or file to load data fixtures from.')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The Faker locale to use.', 'fr_FR');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em       = $doctrine->getManager($input->getOption('em'));
        $files    = $input->getOption('fixtures');
        $fixtures = array();

        if ($files) {
            $fixtures = is_array($files) ? $files : array($files);
        } else {
            $paths = array();
            foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
                $paths[] = $bundle->getPath().'/DataFixtures/ORM';
            }

            $finder = new Finder();
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    foreach ($finder->in($path)->name('*.yml') as $file) {
                        $fixtures[] = $file->getRealpath();
                    }
                }
            }
        }

        if (empty($fixtures)) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        if ($input->isInteractive() && !$input->getOption('append')) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, '<question>Careful, database will be purged. Do you want to continue Y/N ?</question>', false)) {
                return;
            }
        }

        if (!$input->getOption('append')) {
            $purger = new ORMPurger($em);
            $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
            $purger->purge();
        }

        \Nelmio\Alice\Fixtures::load($fixtures, $em, array(
            'locale' => $input->getOption('locale')
        ));
    }
}
