<?php

namespace Erichard\DmsBundle;

interface DocumentNodeInterface
{
    public function getName();
    public function getParent();
    public function getPath();
    public function getNodes();
    public function getDocuments();

    public function addNode(DocumentNodeInterface $node);
    public function removeNode(DocumentNodeInterface $node);

    public function addDocument(DocumentInterface $document);
    public function removeDocument(DocumentInterface $document);
}
