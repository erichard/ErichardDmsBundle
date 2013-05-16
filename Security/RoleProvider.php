<?php

namespace Erichard\DmsBundle\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;

class RoleProvider
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getRoles()
    {
        if ($this->container->hasParameter('dms.permission.role_provider')) {
            $customRoleProvider = $this->container->get($this->container->getParameter('dms.permission.role_provider'));
            $roles = $customRoleProvider->getRoles();
        } else {
            $roles = $this->container->getParameter('dms.permission.roles');
        }

        return $roles;
    }
}
