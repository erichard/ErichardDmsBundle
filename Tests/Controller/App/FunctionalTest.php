<?php

namespace Erichard\DmsBundle\Tests\Controller\App;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTest extends WebTestCase
{

    protected function get($service)
    {
        return $this->getContainer()->get($service);
    }

    protected function getContainer()
    {
        return $this->getKernel()->getContainer();
    }

    protected function getKernel()
    {
        return static::$kernel;
    }

    public static function setupBeforeClass()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        // Creates the database schema
        $em = static::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $schemaTool = new SchemaTool($em);
        $cmf        = $em->getMetadataFactory();
        $classes    = $cmf->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($classes);

        // Loading fixtures
        \Nelmio\Alice\Fixtures::load(__DIR__.'/../../Fixtures/documents.yml', $em);
    }

    protected function teardown()
    {
        static::$kernel->shutdown();
    }
}
