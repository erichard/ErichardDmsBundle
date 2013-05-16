<?php

namespace Erichard\DmsBundle\Tests\Security;

use Erichard\DmsBundle\Security\RoleProvider;
use Mockery as m;

class RoleProviderTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->container = m::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->roleProvider = new RoleProvider($this->container);
    }

    public function test_get_roles()
    {
        $this
            ->container
            ->shouldReceive('hasParameter')
            ->with('dms.permission.role_provider')
            ->andReturn(false)
        ;

        $this
            ->container
            ->shouldReceive('getParameter')
            ->with('dms.permission.roles')
            ->andReturn(array('ROLE_TEST'))
        ;

        $roles = $this->roleProvider->getRoles();

        $this->assertEquals(array('ROLE_TEST'), $roles);
    }

    public function test_get_role_provider()
    {
        $this
            ->container
            ->shouldReceive('hasParameter')
            ->with('dms.permission.role_provider')
            ->andReturn(true)
        ;

        $this
            ->container
            ->shouldReceive('getParameter')
            ->with('dms.permission.role_provider')
            ->andReturn('role_provider_fake_service')
        ;

        $dummyRoleProvider = m::mock('Erichard\DmsBundle\Security\RoleProviderInterface');
        $dummyRoleProvider
            ->shouldReceive('getRoles')
            ->andReturn(array('ROLE_TEST'))
        ;

        $this
            ->container
            ->shouldReceive('get')
            ->with('role_provider_fake_service')
            ->andReturn($dummyRoleProvider)
        ;

        $roles = $this->roleProvider->getRoles();

        $this->assertEquals(array('ROLE_TEST'), $roles);
    }
}
