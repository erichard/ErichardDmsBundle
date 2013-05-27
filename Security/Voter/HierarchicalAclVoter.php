<?php

namespace Erichard\DmsBundle\Security\Voter;

use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class HierarchicalAclVoter implements VoterInterface
{
    private $permissionMap;
    private $acl;
    private $options;

    public function __construct(PermissionMapInterface $permissionMap, $acl, array $options = array())
    {
        $this->permissionMap = $permissionMap;
        $this->acl = $acl;

        $defaultOptions = array(
            'permission_enabled' => true
        );

        $this->options = array_merge($defaultOptions, $options);
    }

    public function supportsAttribute($attribute)
    {
        return $this->permissionMap->contains($attribute);
    }

    public function supportsClass($class)
    {
        return true;
    }

    /**
     * Only support DocumentInterface and DocumentNodeInterface
     */
    public function supportsObject($object)
    {
        return $object instanceof DocumentNodeInterface ||
            $object instanceof DocumentInterface
        ;
    }

    /**
     * Vote for the access of the document.
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        // Watch the object type
        if (!$this->supportsObject($object)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // Enabling permission management obviously denied access to anonymous users
        elseif ($token instanceof AnonymousToken && true === $this->options['permission_enabled']) {
            return VoterInterface::ACCESS_DENIED;
        }
        // Disabling permission management will grant access to all users
        elseif (false === $this->options['permission_enabled']) {
            return VoterInterface::ACCESS_GRANTED;
        }

        // Finally use the ACL algorithm to grant access
        foreach ($attributes as $key => $attribute) {

            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            if (null === $masks = $this->permissionMap->getMasks($attribute, $object)) {
                continue;
            }

            if (count($masks) > 1) {
                throw new \InvalidArgumentException('Setting multiple mask for one permission is invalid when dealing with the HierarchicalAclVoter');
            }

            $mask = current($masks);

            if ($this->acl->isGranted($token, $object, $mask)) {
                return VoterInterface::ACCESS_GRANTED;
            } else {
                return VoterInterface::ACCESS_DENIED;
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
