<?php

namespace Erichard\DmsBundle\Controller;

use Erichard\DmsBundle\Entity\DocumentNode;
use Erichard\DmsBundle\Entity\DocumentNodeAuthorization;
use Erichard\DmsBundle\Entity\DocumentNodeMetadata;
use Erichard\DmsBundle\Security\Acl\Permission\DmsMaskBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class NodeController extends Controller
{
    public function listAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);
        $this->get('dms.manager')->getNodeMetadatas($documentNode);

        foreach ($documentNode->getDocuments() as $document) {
            $filename = $this->container->getParameter('dms.storage.path').'/'.$document->getFilename();

            if (is_file($filename) && is_readable($filename)) {
                $document->setFilesize(filesize($filename));
            }
        }

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
        $request = $this->get('request');
        $this->setCurrentTranslation($request->get('_translation', \Locale::getDefault()));

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
                    $metadata->setValue($metaName);
                    $newNode->addMetadata($metadata);
                    $em->persist($metadata);
                }
            }

            $em->persist($newNode);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'documentNode.add.successfully_created');

            $response = $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $node)));
        }

        return $response;
    }

    public function updateAction($node)
    {
        $request = $this->get('request');
        $this->setCurrentTranslation($request->get('_translation', \Locale::getDefault()));

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

                if (empty($metaValue)) {
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
                    $metadata->setValue($metaName);
                    $documentNode->addMetadata($metadata);
                }

                $metadata = $documentNode->getMetadata($metaName);
                $metadata
                    ->setLocale($documentNode->getLocale())
                    ->setValue($metaValue)
                ;

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

    public function manageAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        $roles = $this->container->get('dms.security.role_provider')->getRoles();

        return $this->render('ErichardDmsBundle:Node:manage.html.twig', array(
            'node' => $documentNode,
            'roles' => $roles
        ));
    }

    public function manageRoleAction($node, $role)
    {
        $documentNode = $this->findNodeOrThrowError($node);

        $roles = $this->container->get('dms.security.role_provider')->getRoles();

        if (!in_array($role, $roles)) {
            throw new AccessDeniedException(sprintf("The role %s is not managed by the DMS.", $role));
        }

        $authorization = $this
            ->container
            ->get('doctrine')
            ->getManager()
            ->createQuery('SELECT a FROM Erichard\DmsBundle\Entity\DocumentNodeAuthorization a WHERE a.node = :node AND a.role = :role')
            ->setParameter('node', $documentNode->getId())
            ->setParameter('role', $role)
            ->getOneOrNullResult()
        ;

        $reflClass = new \ReflectionClass('Erichard\DmsBundle\Security\Acl\Permission\DmsMaskBuilder');

        $request = $this->getRequest();
        if ($request->isMethod('POST')) {
            $permissions = $request->request->get('permissions');

            $allow = 0;
            $deny  = 0;

            foreach ($permissions as $permission => $value) {
                $bitValue = $reflClass->getConstant('MASK_'.$permission);

                if ($value === '1') {
                    $allow += $bitValue ;
                } elseif ($value === '-1') {
                    $deny += $bitValue;
                }
            }

            if (null === $authorization) {
                $authorization = new DocumentNodeAuthorization();
                $authorization
                    ->setNode($documentNode)
                    ->setRole($role)
                ;
            }
            $authorization
                ->setAllow($allow)
                ->setDeny($deny)
            ;
            $em = $this->container->get('doctrine')->getManager();
            $em->persist($authorization);
            $em->flush();

        }

        $session = $this->container->get('session');
        foreach ($session->all() as $var => $value) {
            if (strpos($var, 'dms.node.mask') === 0) {
                $session->remove($var);
            }
        };

        $basePermissions = array(
            'VIEW'                 => 0,
            'DOCUMENT_ADD'         => 0,
            'DOCUMENT_EDIT'        => 0,
            'DOCUMENT_DELETE'      => 0,
            'DOCUMENT_DOWNLOAD'    => 0,
            'NODE_ADD'             => 0,
            'NODE_EDIT'            => 0,
            'NODE_DELETE'          => 0,
            'NODE_DOWNLOAD'        => 0,
            'MANAGE'               => 0,
        );

        $parentAuthorizations = $basePermissions;
        if (null !== $documentNode->getParent()) {
            $parentAuthorizationsMask = $this->container->get('dms.security.access.control_list')->getDocumentNodeAuthorizationMask($documentNode->getParent(), array($role));

            foreach ($parentAuthorizations as $permission => $value) {
                $permissionBit = $reflClass->getConstant('MASK_'.$permission);
                $parentAuthorizations[$permission] = $permissionBit === ($parentAuthorizationsMask & $permissionBit);
            }
        }

        $permissions = $basePermissions;
        if (null !== $authorization) {
            $allowMask = $authorization->getAllow();
            $denyMask = $authorization->getDeny();
            foreach ($permissions as $permission => $value) {
                $permissionBit = $reflClass->getConstant('MASK_'.$permission);
                if ($permissionBit === ($allowMask & $permissionBit )) {
                    $permissions[$permission] = 1;
                } elseif ($permissionBit === ($denyMask & $permissionBit )) {
                    $permissions[$permission] = -1;
                }
            }
        }

        $authorizations = $basePermissions;

        $authorizationsMask = $this->container->get('dms.security.access.control_list')->getDocumentNodeAuthorizationMask($documentNode, array($role));

        foreach ($authorizations as $permission => $value) {
            $permissionBit = $reflClass->getConstant('MASK_'.$permission);
            $authorizations[$permission] = $permissionBit === ($authorizationsMask & $permissionBit);
        }

        return $this->render('ErichardDmsBundle:Node:manageRole.html.twig', array(
            'node'                 => $documentNode,
            'role'                 => $role,
            'parentAuthorizations' => $parentAuthorizations,
            'permissions'          => $permissions,
            'finalAuthorizations'  => $authorizations,
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
            throw new AccessDeniedHttpException($e->getMessage());
        }

        if (null === $node) {
            throw new NotFoundHttpException(sprintf('The node "%s" was not found', $nodeSlug));
        }

        return $node;
    }

    /**************************************************************************
     * I18n support
     **************************************************************************/
    protected function setCurrentTranslation($translation)
    {
        $this->get('dms.manager')->setLocale($translation);

        return $this;
    }
}
