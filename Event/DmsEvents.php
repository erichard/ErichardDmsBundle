<?php

namespace Erichard\DmsBundle\Event;

class DmsEvents
{
    const DOCUMENT_CREATE = 'dms.document.create';
    const DOCUMENT_UPDATE = 'dms.document.update';
    const DOCUMENT_DELETE = 'dms.document.delete';

    const NODE_CREATE     = 'dms.node.create';
    const NODE_UPDATE     = 'dms.node.update';
    const NODE_DELETE     = 'dms.node.delete';
}
