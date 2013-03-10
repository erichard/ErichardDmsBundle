<?php

namespace Erichard\DmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NodeController extends Controller
{
    public function listAction($parent)
    {
        $nodes = $this
            ->get('doctrine')
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findBySlugWithChildren($parent)
            ->getResults()
        ;

        return $this->render('ErichardDmsBundle:Node:list.html.twig', array(
            'nodes' => $nodes
        ));
    }

    public function indexAction()
    {
        $nodes = $this
            ->get('doctrine')
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findByParent(null)
        ;

        $response = $this->render('ErichardDmsBundle:Node:index.html.twig', array(
            'nodes' => $nodes
        ));

        return $response;
    }
}
