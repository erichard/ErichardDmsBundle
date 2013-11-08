<?php

namespace Erichard\DmsBundle\Security\Acl;

use Doctrine\Common\Persistence\ObjectManager;
use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;
use Erichard\DmsBundle\Security\Acl\Permission\DmsMaskBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class Acl
{
    private $roleHierarchy;
    private $manager;
    private $options;
    private $session;

    private $localCache = array();

    public function __construct(RoleHierarchyInterface $roleHierarchy, ObjectManager $manager, SessionInterface $session = null , array $options = array())
    {
        $this->roleHierarchy = $roleHierarchy;
        $this->manager = $manager;
        $this->options = $options;
        $this->session = $session;
    }

    public function isGranted(TokenInterface $token, $object, $mask)
    {
        $roles = $this->roleHierarchy->getReachableRoles($token->getRoles());
        $roles = array_map(function ($role) { return $role->getRole(); }, $roles);

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

    public function getDocumentNodeAuthorizationMask(DocumentNodeInterface $node, array $roles)
    {
        $cacheKey = 'dms.node.mask.' . $node->getId();

        if (!$this->session->has($cacheKey)) {
            $authorizations = $this
                ->manager
                ->getRepository(get_class($node))
                ->getNodeAuthorizationsByRoles($node->getId(), $roles)
            ;

            $mask = $this->mergeMask($authorizations);
            $this->session->set($cacheKey, $mask);
        } else {
            $mask = $this->session->get($cacheKey);
        }

        return $mask;
    }

    public function getDocumentAuthorizationMask(DocumentInterface $document, array $roles)
    {
        $cacheKey = 'dms.document.mask.' . $document->getId();

        if (!$this->session->has($cacheKey)) {
            $nodeMask = $this
                ->getDocumentNodeAuthorizationMask($document->getNode(), $roles)
            ;

            $authorizations = $this
                ->manager
                ->getRepository(get_class($document))
                ->getDocumentAuthorizationsByRoles($document->getId(), $roles)
            ;

            $mask = $this->mergeMask($authorizations, $nodeMask);
            $this->session->set($cacheKey, $mask);
        } else {
            $mask = $this->session->get($cacheKey);
        }

        return $mask;
    }

    public function mergeMask(array $authorizations, $startingMask = 0)
    {
        $authorizationsByRoles = array();

        foreach ($authorizations as $a) {
            if ($a['reset_permission'] == 1) {
                $authorizationsByRoles = array();
            }

            if (!isset($authorizationsByRoles[$a['role']])) {
                $authorizationsByRoles[$a['role']] = new DmsMaskBuilder(0);
            }

            $authorizationsByRoles[$a['role']]->add((int) $a['allow']);
            $authorizationsByRoles[$a['role']]->remove((int) $a['deny']);
        }

        $finalMask = new DmsMaskBuilder($startingMask);

        // Cumulative permissions
        foreach ($authorizationsByRoles as $authorization) {
            $finalMask->add($authorization->get());
        }

        return $finalMask->get();
    }
}
