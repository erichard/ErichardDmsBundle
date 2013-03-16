<?php

namespace Erichard\DmsBundle\Command;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Erichard\DmsBundle\Faker\DmsProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class LoadAliceFixturesCommand extends LoadDataFixturesDoctrineCommand
{

    protected function configure()
    {
        parent::configure();

        $this->setName('alice:fixtures:load');
        $this->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The Faker locale to use.', 'fr_FR');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $doctrine \Doctrine\Common\Persistence\ManagerRegistry */
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager($input->getOption('em'));

        if ($input->isInteractive() && !$input->getOption('append')) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, '<question>Careful, database will be purged. Do you want to continue Y/N ?</question>', false)) {
                return;
            }
        }

        $files = $input->getOption('fixtures');
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

        if (!$input->getOption('append')) {
            $purger = new ORMPurger($em);
            $purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
            $purger->purge();
        }

        sort($fixtures);

        \Nelmio\Alice\Fixtures::load($fixtures, $em, array(
            'locale' => $input->getOption('locale'),
            'providers' => array(
                new DmsProvider($this->getContainer())
            ),
            'logger' => function($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        ));
    }
}
