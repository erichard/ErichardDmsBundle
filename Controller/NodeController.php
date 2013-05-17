<?php

namespace Erichard\DmsBundle\Controller;

use Erichard\DmsBundle\Entity\DocumentNode;
use Erichard\DmsBundle\Entity\DocumentNodeMetadata;
use Erichard\DmsBundle\Form\NodeType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NodeController extends Controller
{
    public function listAction($node)
    {
        $documentNode = $this->findNodeOr404($node);

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
        $documentNode = $this->findNodeOr404($node);

        $form = $this->createForm(new NodeType());

        return $this->render('ErichardDmsBundle:Node:add.html.twig', array(
            'node' => $documentNode,
            'form' => $form->createView()
        ));
    }

    public function editAction($node)
    {
        $documentNode = $this->findNodeOr404($node);

        $form = $this->createForm(new NodeType(), $documentNode);

        return $this->render('ErichardDmsBundle:Node:edit.html.twig', array(
            'node' => $documentNode,
            'form' => $form->createView()
        ));
    }

    public function createAction($node)
    {
        $parentNode = $this->findNodeOr404($node);
        $newNode    = new DocumentNode();
        $parentNode->addNode($newNode);
        $form       = $this->createForm(new NodeType(), $newNode);

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
        $documentNode = $this->findNodeOr404($node);

        $form = $this->createForm(new NodeType(), $documentNode);
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
        $documentNode = $this->findNodeOr404($node);

        $em = $this->get('doctrine')->getManager();
        $em->remove($documentNode);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Node successfully removed !');

        return $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $documentNode->getParent()->getSlug())));
    }

    public function removeAction($node)
    {
        $documentNode = $this->findNodeOr404($node);

        return $this->render('ErichardDmsBundle:Node:remove.html.twig', array(
            'node' => $documentNode,
        ));
    }

    protected function findNodeOr404($slug)
    {
        $documentNode = $this
            ->get('doctrine')
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->findOneBySlugWithChildren($slug)
        ;

        if (null == $documentNode) {
            throw $this->createNotFoundException(sprintf('Document not found : %s', $slug));
        }

        if (!$this->get('security.context')->isGranted('VIEW', $documentNode)) {
            throw new AccessDeniedHttpException('You are not allowed to view this node.');
        }

        foreach ($documentNode->getNodes() as $node) {
            if (!$this->get('security.context')->isGranted('VIEW', $node)) {
                $documentNode->removeNode($node);
            }
        }

        foreach ($documentNode->getDocuments() as $document) {
            if (!$this->get('security.context')->isGranted('VIEW', $document)) {
                $documentNode->removeDocument($document);
            }
        }

        $metadatas = $this
            ->get('doctrine')
            ->getRepository('Erichard\DmsBundle\Entity\Metadata')
            ->findByScope(array('node', 'both'))
        ;

        foreach ($metadatas as $m) {
            if (!$documentNode->hasMetadata($m->getName())) {
                $metadata = new DocumentNodeMetadata($m);
                $documentNode->addMetadata($metadata);
            }
        }

        return $documentNode;
    }
}
