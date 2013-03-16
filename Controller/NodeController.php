<?php

namespace Erichard\DmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NodeController extends Controller
{
    public function listAction($node)
    {
        $documentNode = $this
            ->get('doctrine')
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findBySlugWithChildren($node)
        ;

        if (null == $documentNode) {
            throw $this->createNotFoundException(sprintf('Document not found : %s', $node));
        }

        return $this->render('ErichardDmsBundle:Node:list.html.twig', array(
            'node' => $documentNode,
            'mode' => $this->get('request')->query->get('mode', 'gallery'),
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
