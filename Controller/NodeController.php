<?php

namespace Erichard\DmsBundle\Controller;

use Erichard\DmsBundle\Entity\DocumentNode;
use Erichard\DmsBundle\Entity\DocumentNodeMetadata;
use Erichard\DmsBundle\Form\NodeType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class NodeController extends Controller
{
    public function listAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);
        $this->get('dms.manager')->getNodeMetadatas($documentNode);

        return $this->render('ErichardDmsBundle:Node:list.html.twig', array(
            'node'       => $documentNode,
            'mode'       => $this->get('request')->query->get('mode', 'table'),
            'show_nodes' => $this->container->getParameter('dms.workspace.show_nodes')
        ));
    }

    public function indexAction()
    {
        $dmsManager = $this->get('dms.manager');

        $response = $this->render('ErichardDmsBundle:Node:index.html.twig', array(
            'nodes' => $dmsManager->getRoots()
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

        $this->get('dms.manager')->getNodeMetadatas($documentNode);

        $form = $this->createForm('dms_node', $documentNode);

        return $this->render('ErichardDmsBundle:Node:edit.html.twig', array(
            'node' => $documentNode,
            'form' => $form->createView()
        ));
    }

    public function createAction($node)
    {
        $em = $this->get('doctrine')->getManager();
        $parentNode = $this->findNodeOrThrowError($node);

        $newNode    = new DocumentNode();
        $newNode->setParent($parentNode);
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
                if (null !== $metaValue) {
                    $metadata = new DocumentNodeMetadata(
                        $em->getRepository('Erichard\DmsBundle\Entity\Metadata')->findOneByName($metaName)
                    );
                    $newNode->addMetadata($metadata);
                    $em->persist($metadata);
                }
            }

            foreach ($parentNode->getDocuments() as $document) {
                $document->removeEmptyMetadatas();
            }

            $parentNode->removeEmptyMetadatas();
            $newNode->removeEmptyMetadatas();

            $em->persist($newNode);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'documentNode.add.successfully_created');

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

            $em = $this->get('doctrine')->getManager();

            $metadatas = $form->get('metadatas')->getData();
            foreach ($metadatas as $metaName => $metaValue) {

                if (null === $metaValue) {
                    if ($metadata = $documentNode->getMetadata($metaName)) {
                        $documentNode->removeMetadataByName($metaName);
                        $em->remove($metadata);
                    }
                    continue;
                }

                if (!$documentNode->hasMetadata($metaName)) {
                    $metadata = new DocumentNodeMetadata(
                        $em->getRepository('Erichard\DmsBundle\Entity\Metadata')->findOneByName($metaName)
                    );
                    $documentNode->addMetadata($metadata);
                }

                $documentNode->getMetadata($metaName)->setValue($metaValue);
                $em->persist($documentNode->getMetadata($metaName));
            }

            $em->persist($documentNode);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'documentNode.edit.successfully_updated');

            $response = $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $documentNode->getSlug())));
        }

        return $response;
    }

    public function deleteAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        $redirectUrl = $this->generateUrl('erichard_dms_node_list', array('node' => $documentNode->getParent()->getSlug()));

        $em = $this->get('doctrine')->getManager();
        $em->refresh($documentNode);
        $em->remove($documentNode);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'documentNode.remove.successfully_removed');

        return $this->redirect($redirectUrl);
    }

    public function removeAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        return $this->render('ErichardDmsBundle:Node:remove.html.twig', array(
            'node' => $documentNode,
        ));
    }

    public function findNodeOrThrowError($nodeSlug)
    {
        try {
            $node = $this
                ->get('dms.manager')
                ->getNode($nodeSlug)
            ;
        } catch (AccessDeniedException $e) {
            throw AccessDeniedHttpException($e->getMessage());
        }

        if (null === $node) {
            throw NotFoundHttpException(sprintf('The node "%s" was not found', $nodeSlug));
        }

        return $node;
    }
}
