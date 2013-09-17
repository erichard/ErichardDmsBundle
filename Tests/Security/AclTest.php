<?php

namespace Erichard\DmsBundle\Tests\Security;

use Erichard\DmsBundle\Entity\Document;
use Erichard\DmsBundle\Entity\DocumentNode;
use Erichard\DmsBundle\Security\Acl\Acl;
use Mockery as m;
use Symfony\Component\Security\Core\Role\Role;

class AclTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->roleHierarchy = m::mock('Symfony\Component\Security\Core\Role\RoleHierarchyInterface');
        $this->em = m::mock('Doctrine\Common\Persistence\ObjectManager');

        $this->documentRepository = m::mock('Doctrine\ORM\EntityRepository');
        $this->em->shouldReceive('getRepository')->with('Erichard\DmsBundle\Entity\Document')->andReturn($this->documentRepository);

        $this->documentNodeRepository = m::mock('Doctrine\ORM\EntityRepository');
        $this->em->shouldReceive('getRepository')->with('Erichard\DmsBundle\Entity\DocumentNode')->andReturn($this->documentNodeRepository);

        $this->acl = new Acl($this->roleHierarchy, $this->em, array());

        $this->token = m::mock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $tokenRoles = array(new Role('ROLE_GROUP_TEST'));

        $this->token->shouldReceive('getRoles')->andReturn($tokenRoles);
        $this->roleHierarchy->shouldReceive('getReachableRoles')->with($tokenRoles)->andReturn($tokenRoles);

        $this->documentNode   = new DocumentNode();
        $this->document       = new Document($this->documentNode);
        $this->documentNode->setId(1);
        $this->document->setId(1);
    }

    public function test_granted_document()
    {
        $this->documentRepository
            ->shouldReceive('getDocumentAuthorizationsByRoles')
            ->with(1, array('ROLE_GROUP_TEST'))
            ->andReturn(array())
        ;

        $this->documentNodeRepository
            ->shouldReceive('getNodeAuthorizationsByRoles')
            ->with(1, array('ROLE_GROUP_TEST'))
            ->andReturn(array(
                array(
                    'role' => 'ROLE_GROUP_TEST',
                    'allow' => 1,
                    'deny' => 0,
                )
            ))
        ;

        $this->assertTrue(
            $this->acl->isGranted($this->token, $this->document, 1)
        );
    }

    public function test_denied_document()
    {
        $this->documentRepository
            ->shouldReceive('getDocumentAuthorizationsByRoles')
            ->with(1, array('ROLE_GROUP_TEST'))
            ->andReturn(array())
        ;

        $this->documentNodeRepository
            ->shouldReceive('getNodeAuthorizationsByRoles')
            ->with(1, array('ROLE_GROUP_TEST'))
            ->andReturn(array(
                array(
                    'role' => 'ROLE_GROUP_TEST',
                    'allow' => 0,
                    'deny' => 1,
                )
            ))
        ;

        $this->assertFalse(
            $this->acl->isGranted($this->token, $this->document, 1)
        );
    }

    public function test_granted_node()
    {
        $this->documentNodeRepository
            ->shouldReceive('getNodeAuthorizationsByRoles')
            ->with(1, array('ROLE_GROUP_TEST'))
            ->andReturn(array(
                array(
                    'role' => 'ROLE_GROUP_TEST',
                    'allow' => 1,
                    'deny' => 0,
                )
            ))
        ;

        $this->assertTrue(
            $this->acl->isGranted($this->token, $this->documentNode, 1)
        );
    }

    public function test_denied_node()
    {
        $this->documentNodeRepository
            ->shouldReceive('getNodeAuthorizationsByRoles')
            ->with(1, array('ROLE_GROUP_TEST'))
            ->andReturn(array(
                array(
                    'role' => 'ROLE_GROUP_TEST',
                    'allow' => 0,
                    'deny' => 1,
                )
            ))
        ;

        $this->assertFalse(
            $this->acl->isGranted($this->token, $this->documentNode, 1)
        );
    }

    /**
     * @dataProvider getAuthorizations
     */
    public function test_merge_mask($authorizations, $startingMask, $expectedMask)
    {
        $this->assertEquals(
            $this->acl->mergeMask($authorizations, $startingMask), $expectedMask
        );
    }

    public function getAuthorizations()
    {
        return array(
            array(
                [ $this->createAuthorization('ROLE_GROUP_TEST', 0, 0) ], 0,
                0
            ),
            array(
                [ $this->createAuthorization('ROLE_GROUP_TEST', 17, 0) ], 0,
                17
            ),
            array(
                [ $this->createAuthorization('ROLE_GROUP_TEST', 17, 1) ], 0,
                16
            ),
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST', 16, 0),
                    $this->createAuthorization('ROLE_GROUP_TEST', 1, 0),
                ], 0,
                17
            ),
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST', 16, 0),
                    $this->createAuthorization('ROLE_GROUP_TEST', 1, 0),
                    $this->createAuthorization('ROLE_GROUP_TEST2', 0, 1),
                ], 0,
                17
            ),
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST', 1, 0),
                    $this->createAuthorization('ROLE_GROUP_TEST2', 16, 0),
                ], 0,
                17
            ),

            /**
             * A same user has two roles that allows the access and one that denies it.
             *
             * We are intended to allow access to the resource.
             */
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST1', 17,  0),
                    $this->createAuthorization('ROLE_GROUP_TEST2',  0, 17),
                    $this->createAuthorization('ROLE_GROUP_TEST3', 17,  0),
                ], 0,
                17
            ),

            /**
             * A same user has two roles that denies the access and one that allows it.
             *
             * We are intended to allow access to the resource.
             */
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST1', 0,  17),
                    $this->createAuthorization('ROLE_GROUP_TEST2', 17,  0),
                    $this->createAuthorization('ROLE_GROUP_TEST3',  0, 17),
                ], 0,
                17
            ),
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST1', 17,  0),
                    $this->createAuthorization('ROLE_GROUP_TEST2', 17,  0),
                    $this->createAuthorization('ROLE_GROUP_TEST2',  0, 17),
                ], 0,
                17
            ),
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST2', 17,  0),
                    $this->createAuthorization('ROLE_GROUP_TEST2',  0, 17),
                ], 0,
                0
            ),
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST1', 1,  0, 1),
                    $this->createAuthorization('ROLE_GROUP_TEST1', 16,  0, 3),
                ], 0,
                17
            ),
            array(
                [
                    $this->createAuthorization('ROLE_GROUP_TEST1', 17,  0, 1),
                    $this->createAuthorization('ROLE_GROUP_TEST2', 0,  17, 3),
                ], 0,
                17
            ),
        );
    }

    public function createAuthorization($role, $allow, $deny, $depth = 0)
    {
        return array(
            'role'  => $role,
            'allow' => $allow,
            'deny'  => $deny,
            'depth' => $depth,
        );
    }
}
