<?php

namespace Erichard\DmsBundle\Event;

class DmsEvents
{
    const NODE_ACCESS = 'dms.node.access';
    const NODE_ADD = 'dms.node.add';
    const NODE_CREATE = 'dms.node.create';
    const NODE_EDIT = 'dms.node.edit';
    const NODE_UPDATE = 'dms.node.update';
    const NODE_DELETE = 'dms.node.delete';
    const NODE_MANAGE = 'dms.node.manage';
    const NODE_RESET_PERMISSION = 'dms.node.reset_permission';
    const NODE_CHANGE_PERMISSION = 'dms.node.change_permission';

    const DOCUMENT_EDIT = 'dms.document.edit';
    const DOCUMENT_LINK = 'dms.document.link';
    const DOCUMENT_ADD = 'dms.document.add';
    const DOCUMENT_UPDATE = 'dms.document.update';
    const DOCUMENT_DOWNLOAD = 'dms.document.download';
    const DOCUMENT_DELETE = 'dms.document.delete';
    const DOCUMENT_REMOVE_THUMBNAIL = 'dms.document.remove_thumbnail';
}
