<?php

namespace Erichard\DmsBundle;

interface DocumentInterface
{
    const TYPE_FILE = 'file';

    public function getNode();
    public function getName();
    public function getFilename();
    public function getMimeType();
    public function getType();
    public function getPath();


}
