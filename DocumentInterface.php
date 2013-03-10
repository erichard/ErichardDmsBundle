<?php

namespace Erichard\DmsBundle;

interface DocumentInterface
{
    public function getContent();
    public function getParent();
    public function getPath();
}
