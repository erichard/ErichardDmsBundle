<?php

namespace Erichard\DmsBundle\Security\Acl\Permission;

use Erichard\DmsBundle\Security\Acl\Permission\DmsMaskBuilder;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;

class DmsPermissionMap implements PermissionMapInterface
{
    const PERMISSION_VIEW               = 'VIEW';
    const PERMISSION_DOCUMENT_ADD       = 'DOCUMENT_ADD';
    const PERMISSION_DOCUMENT_EDIT      = 'DOCUMENT_EDIT';
    const PERMISSION_DOCUMENT_DELETE    = 'DOCUMENT_DELETE';
    const PERMISSION_DOCUMENT_DOWNLOAD  = 'DOCUMENT_DOWNLOAD';
    const PERMISSION_NODE_ADD           = 'NODE_ADD';
    const PERMISSION_NODE_EDIT          = 'NODE_EDIT';
    const PERMISSION_NODE_DELETE        = 'NODE_DELETE';
    const PERMISSION_NODE_DOWNLOAD      = 'NODE_DOWNLOAD';
    const PERMISSION_MANAGE             = 'MANAGE';

    private $map = array(
        self::PERMISSION_VIEW => array(
            DmsMaskBuilder::MASK_VIEW,
        ),
        self::PERMISSION_DOCUMENT_EDIT => array(
            DmsMaskBuilder::MASK_DOCUMENT_EDIT,
        ),
        self::PERMISSION_DOCUMENT_ADD => array(
            DmsMaskBuilder::MASK_DOCUMENT_ADD,
        ),
        self::PERMISSION_DOCUMENT_DELETE => array(
            DmsMaskBuilder::MASK_DOCUMENT_DELETE,
        ),
        self::PERMISSION_DOCUMENT_DOWNLOAD => array(
            DmsMaskBuilder::MASK_DOCUMENT_DOWNLOAD,
        ),
        self::PERMISSION_NODE_EDIT => array(
            DmsMaskBuilder::MASK_NODE_EDIT,
        ),
        self::PERMISSION_NODE_ADD => array(
            DmsMaskBuilder::MASK_NODE_ADD,
        ),
        self::PERMISSION_NODE_DELETE => array(
            DmsMaskBuilder::MASK_NODE_DELETE,
        ),
        self::PERMISSION_NODE_DOWNLOAD => array(
            DmsMaskBuilder::MASK_NODE_DOWNLOAD,
        ),
        self::PERMISSION_MANAGE => array(
            DmsMaskBuilder::MASK_MANAGE,
        ),
    );

    /**
     * {@inheritDoc}
     */
    public function getMasks($permission, $object)
    {
        if (!isset($this->map[$permission])) {
            return null;
        }

        return $this->map[$permission];
    }

    /**
     * {@inheritDoc}
     */
    public function contains($permission)
    {
        return isset($this->map[$permission]);
    }
}
