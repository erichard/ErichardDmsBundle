<?php

namespace Erichard\DmsBundle\Tests\Controller;

use Erichard\DmsBundle\Tests\Controller\App\FunctionalTest;

class NodeControllerTest extends FunctionalTest
{

    public function test_node_creation()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/en/video/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $saveButton = $crawler->selectButton('Save changes');
        $form = $saveButton->form([
            'dms_node[name]'    => 'New DocumentNode',
            'dms_node[enabled]' => true
        ]);
        $client->submit($form);
        $this->assertEquals(302,      $client->getResponse()->getStatusCode());
        $this->assertEquals('/en/video', $client->getResponse()->headers->get('location'));
    }

    public function test_node_update()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/en/new-documentnode/edit');

        $saveButton = $crawler->selectButton('Save changes');
        $form = $saveButton->form([
            'dms_node[name]'    => 'My Document',
            'dms_node[enabled]' => true
        ]);
        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/en/new-documentnode', $client->getResponse()->headers->get('location'));
    }

    public function test_node_update_with_translation()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/en/new-documentnode/edit?_locale=en&_translation=fr');

        $saveButton = $crawler->selectButton('Save changes');
        $form = $saveButton->form([
            'dms_node[name]'    => 'Mon Document',
            'dms_node[enabled]' => true
        ]);
        $client->submit($form);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/en/new-documentnode', $client->getResponse()->headers->get('location'));
    }

    public function test_node_translations()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/en/new-documentnode');

        $this->assertEquals('My Document', $crawler->filter('.breadcrumb li.active')->text());

        $crawler = $client->request('GET', '/fr/new-documentnode');

        $this->assertEquals('Mon Document', $crawler->filter('.breadcrumb li.active')->text());
    }
}
