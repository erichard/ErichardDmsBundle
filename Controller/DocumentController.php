<?php

namespace Erichard\DmsBundle\Controller;

use Erichard\DmsBundle\Form\DocumentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DocumentController extends Controller
{
    public function addAction($node)
    {
        $documentNode = $this->findNodeOr404($node);

        $form = $this->createForm(new DocumentType());

        return $this->render('ErichardDmsBundle:Document:add.html.twig', array(
            'node' => $documentNode,
            'form' => $form->createView()
        ));
    }

    public function createAction($node)
    {
        $parentNode = $this->findNodeOr404($node);
        $form       = $this->createForm(new DocumentType());

        $form->bind($this->get('request'));

        if (!$form->isValid()) {
            $response = $this->render('ErichardDmsBundle:Document:add.html.twig', array(
                'node' => $parentNode,
                'form' => $form->createView()
            ));
        } else {

            $em = $this->get('doctrine')->getManager();
            $em->persist($newNode);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'New node successfully created !');

            $response = $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $node)));
        }

        return $response;
    }

    protected function findNodeOr404($slug)
    {
        $documentNode = $this
            ->get('doctrine')
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findBySlugWithChildren($slug)
        ;

        if (null == $documentNode) {
            throw $this->createNotFoundException(sprintf('Document not found : %s', $slug));
        }

        return $documentNode;
    }
}
