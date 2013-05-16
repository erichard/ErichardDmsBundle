<?php

namespace Erichard\DmsBundle\Tests\Security\Voter;

use Erichard\DmsBundle\Security\Voter\HierarchicalAclVoter;
use Mockery as m;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class HierarchicalAclVoterTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->acl = m::mock('Erichard\DmsBundle\Security\Acl\Acl');
        $this->permissionMap = m::mock('Symfony\Component\Security\Acl\Permission\PermissionMapInterface');

        $this->voter = new HierarchicalAclVoter($this->permissionMap, $this->acl);
    }

    public function test_supports_attribute()
    {
        $this->permissionMap->shouldReceive('contains')->with('SUPPORTED')->andReturn(true);
        $this->assertTrue($this->voter->supportsAttribute('SUPPORTED'));

        $this->permissionMap->shouldReceive('contains')->with('NOT_SUPPORTED')->andReturn(false);
        $this->assertFalse($this->voter->supportsAttribute('NOT_SUPPORTED'));
    }

    public function test_support_object()
    {
        $documentNode = m::mock('Erichard\DmsBundle\DocumentNodeInterface');
        $document = m::mock('Erichard\DmsBundle\DocumentInterface');

        $this->assertTrue($this->voter->supportsObject($documentNode));
        $this->assertTrue($this->voter->supportsObject($document));
        $this->assertFalse($this->voter->supportsObject(new \StdClass()));
    }

    public function test_vote_with_unknown_permission()
    {
        $attributes = array('PERMISSION_NOT_SUPPORTED');
        $token      = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $document   = m::mock('Erichard\DmsBundle\DocumentInterface');

        $this->permissionMap->shouldReceive('contains')->with('PERMISSION_NOT_SUPPORTED')->andReturn(false);

        $this->assertEquals($this->voter->vote($token, $document, $attributes), VoterInterface::ACCESS_ABSTAIN);
    }

    public function test_vote_with_no_mask()
    {
        $attributes = array('PERMISSION_NOT_SUPPORTED');
        $token      = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $document   = m::mock('Erichard\DmsBundle\DocumentInterface');

        $this->permissionMap->shouldReceive('contains')->with('PERMISSION_NOT_SUPPORTED')->andReturn(true);
        $this->permissionMap->shouldReceive('getMasks')->with('PERMISSION_NOT_SUPPORTED', $document)->andReturn(null);

        $this->assertEquals($this->voter->vote($token, $document, $attributes), VoterInterface::ACCESS_ABSTAIN);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_vote_with_more_than_one_mask()
    {
        $attributes = array('PERMISSION_SUPPORTED');
        $token      = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $document   = m::mock('Erichard\DmsBundle\DocumentInterface');

        $this->permissionMap->shouldReceive('contains')->with('PERMISSION_SUPPORTED')->andReturn(true);
        $this->permissionMap->shouldReceive('getMasks')->with('PERMISSION_SUPPORTED', $document)->andReturn(array(1,2,4));

        $this->voter->vote($token, $document, $attributes);
    }

    public function test_vote_granted_access()
    {
        $attributes = array('PERMISSION_SUPPORTED');
        $token      = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $document   = m::mock('Erichard\DmsBundle\DocumentInterface');

        $this->permissionMap->shouldReceive('contains')->with('PERMISSION_SUPPORTED')->andReturn(true);
        $this->permissionMap->shouldReceive('getMasks')->with('PERMISSION_SUPPORTED', $document)->andReturn(array(1));

        $this->acl->shouldReceive('isGranted')->with($token, $document, 1)->andReturn(true);

        $this->assertEquals($this->voter->vote($token, $document, $attributes), VoterInterface::ACCESS_GRANTED);
    }

    public function test_vote_denied_access()
    {
        $attributes = array('PERMISSION_SUPPORTED');
        $token      = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $document   = m::mock('Erichard\DmsBundle\DocumentInterface');

        $this->permissionMap->shouldReceive('contains')->with('PERMISSION_SUPPORTED')->andReturn(true);
        $this->permissionMap->shouldReceive('getMasks')->with('PERMISSION_SUPPORTED', $document)->andReturn(array(1));

        $this->acl->shouldReceive('isGranted')->with($token, $document, 1)->andReturn(false);

        $this->assertEquals($this->voter->vote($token, $document, $attributes), VoterInterface::ACCESS_DENIED);
    }
}
