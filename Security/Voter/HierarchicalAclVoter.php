<?php

namespace Erichard\DmsBundle\Security\Voter;

use Erichard\DmsBundle\DocumentInterface;
use Erichard\DmsBundle\DocumentNodeInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class HierarchicalAclVoter implements VoterInterface
{
    private $permissionMap;
    private $acl;

    public function __construct(PermissionMapInterface $permissionMap, $acl)
    {
        $this->permissionMap = $permissionMap;
        $this->acl = $acl;
    }

    public function supportsAttribute($attribute)
    {
        return $this->permissionMap->contains($attribute);
    }

    public function supportsClass($class)
    {
        return true;
    }

    public function supportsObject($object)
    {
        return $object instanceof DocumentNodeInterface ||
            $object instanceof DocumentInterface
        ;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$this->supportsObject($object)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

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
