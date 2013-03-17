<?php

namespace Erichard\DmsBundle;

interface DocumentInterface
{
    public function getParent();
    public function getPath();
    public function getFilename();
    public function getName();
    public function getMimeType();
}
