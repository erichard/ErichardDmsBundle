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

            $start = microtime(true);
            for ($i=0; $i < 10000; $i++) {
                $this->localCache['document'][$document->getId()] = $this->mergeMask($authorizations, $nodeMask);
            }
            echo (microtime(true) - $start) * 1000 . "\n";
        }

        return $this->localCache['document'][$document->getId()];
    }

    public function mergeMask(array $authorizations, $startingMask = 0)
    {
        $authorizationsByRoles = array();
        foreach ($authorizations as $a) {
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
