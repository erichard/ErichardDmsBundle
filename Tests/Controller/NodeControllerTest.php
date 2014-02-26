<?php

namespace Erichard\DmsBundle\Tests\Controller;

use Erichard\DmsBundle\Tests\Controller\App\FunctionalTest;

class NodeControllerTest extends FunctionalTest
{

    public function test_node_creation()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/video/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $saveButton = $crawler->selectButton('Save changes');
        $form = $saveButton->form([
            'dms_node[name]'    => 'New DocumentNode',
            'dms_node[enabled]' => true
        ]);
        $client->submit($form);
        $this->assertEquals(302,      $client->getResponse()->getStatusCode());
        $this->assertEquals('/video', $client->getResponse()->headers->get('location'));
    }

    public function test_node_update()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/new-documentnode/edit');

        $saveButton = $crawler->selectButton('Save changes');
        $form = $saveButton->form([
            'dms_node[name]'    => 'My Document',
            'dms_node[enabled]' => true
        ]);
        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/new-documentnode', $client->getResponse()->headers->get('location'));
    }

    public function test_node_update_with_translation()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/new-documentnode/edit?_locale=en&_translation=fr');

        $saveButton = $crawler->selectButton('Save changes');
        $form = $saveButton->form([
            'dms_node[name]'    => 'Mon Document',
            'dms_node[enabled]' => true
        ]);
        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/new-documentnode', $client->getResponse()->headers->get('location'));

        // Check that original value has not been updated (but translation must have)
        $dms          = $this->get('dms.manager');
        $documentNode = $dms->getNode('new-documentnode');
        $this->assertInstanceOf('Erichard\DmsBundle\Entity\DocumentNode', $documentNode);
        $this->assertEquals('My Document', $documentNode->getName());

        // Check that an 'fr' translation has been stored
        $em           = $this->get('doctrine.orm.default_entity_manager');
        $repository   = $em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repository->findTranslations($documentNode);

        $this->assertGreaterThan(0, count($translations), 'Expecting at least one translation available');
        $this->assertEquals(true, array_key_exists('fr', $translations));
        $this->assertEquals(true, array_key_exists('name', $translations['fr']));
        $this->assertEquals('Mon Document', $translations['fr']['name']);

    }
}
