<?php

namespace Erichard\DmsBundle\Controller;

use Erichard\DmsBundle\Entity\DocumentNode;
use Erichard\DmsBundle\Form\NodeType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NodeController extends Controller
{
    public function listAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        return $this->render('ErichardDmsBundle:Node:list.html.twig', array(
            'node'       => $documentNode,
            'mode'       => $this->get('request')->query->get('mode', 'gallery'),
            'show_nodes' => $this->container->getParameter('dms.workspace.show_nodes')
        ));
    }

    public function indexAction()
    {
        $nodes = $this
            ->get('doctrine')
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findByParent(null)
        ;

        $securityContext = $this->get('security.context');

        $nodes = array_filter($nodes, function($node) use ($securityContext) {
            return $securityContext->isGranted('VIEW', $node);
        });

        $response = $this->render('ErichardDmsBundle:Node:index.html.twig', array(
            'nodes' => $nodes
        ));

        return $response;
    }

    public function addAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        $form = $this->createForm('dms_node');

        return $this->render('ErichardDmsBundle:Node:add.html.twig', array(
            'node' => $documentNode,
            'form' => $form->createView()
        ));
    }

    public function editAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        $form = $this->createForm('dms_node', $documentNode);

        return $this->render('ErichardDmsBundle:Node:edit.html.twig', array(
            'node' => $documentNode,
            'form' => $form->createView()
        ));
    }

    public function createAction($node)
    {
        $parentNode = $this->findNodeOrThrowError($node);
        $newNode    = new DocumentNode();
        $parentNode->addNode($newNode);
        $form       = $this->createForm('dms_node', $newNode);

        $form->bind($this->get('request'));

        if (!$form->isValid()) {
            $response = $this->render('ErichardDmsBundle:Node:add.html.twig', array(
                'node' => $parentNode,
                'form' => $form->createView()
            ));
        } else {

            $metadatas = $form->get('metadatas')->getData();
            foreach ($metadatas as $metaName => $metaValue) {
                $documentNode->getMetadata($metaName)->setValue($metaValue);
            }
            $documentNode->removeEmptyMetadatas();

            $em = $this->get('doctrine')->getManager();
            $em->persist($newNode);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'New node successfully created !');

            $response = $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $node)));
        }

        return $response;
    }

    public function updateAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        $form = $this->createForm('dms_node', $documentNode);
        $form->bind($this->get('request'));

        if (!$form->isValid()) {
            $response = $this->render('ErichardDmsBundle:Node:edit.html.twig', array(
                'node' => $documentNode,
                'form' => $form->createView()
            ));
        } else {
            $metadatas = $form->get('metadatas')->getData();
            foreach ($metadatas as $metaName => $metaValue) {
                $documentNode->getMetadata($metaName)->setValue($metaValue);
            }

            $documentNode->removeEmptyMetadatas();
            $em = $this->get('doctrine')->getManager();
            $em->persist($documentNode);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Node successfully updated !');

            $response = $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $documentNode->getSlug())));
        }

        return $response;
    }

    public function deleteAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        $em = $this->get('doctrine')->getManager();
        $em->remove($documentNode);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Node successfully removed !');

        return $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $documentNode->getParent()->getSlug())));
    }

    public function removeAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        return $this->render('ErichardDmsBundle:Node:remove.html.twig', array(
            'node' => $documentNode,
        ));
    }

    public function findNodeOrThrowError($node)
    {
        return $this
            ->get('dms.repository.documentNode')
            ->findNodeOrThrowError($node)
        ;
    }
}
