<?php

namespace Erichard\DmsBundle\Security\Acl;

use Doctrine\Common\Persistence\ObjectManager;
use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;
use Erichard\DmsBundle\Security\Acl\Permission\DmsMaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class Acl
{
    private $roleHierarchy;
    private $manager;
    private $options;

    private $localCache = array();

    public function __construct(RoleHierarchyInterface $roleHierarchy, ObjectManager $manager, array $options = array())
    {
        $this->roleHierarchy = $roleHierarchy;
        $this->manager = $manager;
        $this->options = $options;
    }

    public function isGranted(TokenInterface $token, $object, $mask)
    {
        $roles = $this->roleHierarchy->getReachableRoles($token->getRoles());
        $roles = array_map(function($role) { return $role->getRole(); }, $roles);

        if (isset($this->options['super_admin_role']) && in_array($this->options['super_admin_role'], $roles)) {
            return true;
        }

        if ($object instanceof DocumentNodeInterface) {
            $objectMask = $this->getDocumentNodeAuthorizationMask($object, $roles);
        } elseif ($object instanceof DocumentInterface) {
            $objectMask = $this->getDocumentAuthorizationMask($object, $roles);
        } else {
            throw new \InvalidArgumentException(
                sprintf('The DmsAcl class cannot handle %s object.', get_class($object))
            );
        }

        return $mask === ($mask & $objectMask);
    }

    protected function getDocumentNodeAuthorizationMask(DocumentNodeInterface $node, array $roles)
    {
        if (!isset($this->localCache['node'][$node->getId()])) {

            $authorizations = $this
                ->manager
                ->getRepository(get_class($node))
                ->getNodeAuthorizationsByRoles($node->getId(), $roles)
            ;

            $this->localCache['node'][$node->getId()] = $this->mergeMask($authorizations);
        }

        return $this->localCache['node'][$node->getId()];
    }

    protected function getDocumentAuthorizationMask(DocumentInterface $document, array $roles)
    {
        if (!isset($this->localCache['document'][$document->getId()])) {
            $nodeMask = $this
                ->getDocumentNodeAuthorizationMask($document->getNode(), $roles)
            ;

            $authorizations = $this
                ->manager
                ->getRepository(get_class($document))
                ->getDocumentAuthorizationsByRoles($document->getId(), $roles)
            ;

            $this->localCache['document'][$document->getId()] = $this->mergeMask($authorizations, $nodeMask);
        }

        return $this->localCache['document'][$document->getId()];
    }

    public function mergeMask(array $authorizations, $startingMask = 0)
    {
        $authorizationsByRoles = array();
        foreach ($authorizations as $a) {
            if (!isset($authorizationsByRoles[$a['role']])) {
                $authorizationsByRoles[$a['role']] = array(
                    'allow' => new DmsMaskBuilder(),
                    'deny' => new DmsMaskBuilder(),
                );
            }

            $authorizationsByRoles[$a['role']]['allow']->add((int) $a['allow']);
            $authorizationsByRoles[$a['role']]['deny']->add((int) $a['deny']);
        }

        //var_dump($authorizationsByRoles);

        $finalMask = new DmsMaskBuilder($startingMask);
        foreach ($authorizationsByRoles as $authorization) {
            $finalMask->add($authorization['allow']->get());
            $finalMask->remove($authorization['deny']->get());
        }

        return $finalMask->get();
    }

    public function sortAuthorizations($authorizations)
    {
        usort($authorizations, function ($auth1, $auth2) {
            if ($auth1['depth'] != $auth2['depth']) {
                return $auth1['depth'] - $auth2['depth'];
            } elseif ($auth1['deny'] != $auth2['deny']) {
                return ($auth1['deny'] - $auth2['deny']) * -1;
            } elseif ($auth1['allow'] != $auth2['allow']) {
                return ($auth1['allow'] - $auth2['allow']) * -1;
            } elseif ($auth1['role'] != $auth2['role']) {
                return strcmp($auth1['role'], $auth2['role']);
            } else {
                return 0;
            }
        });

        return $authorizations;
    }
}
