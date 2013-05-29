<?php

namespace Erichard\DmsBundle\Iterator;

class GedmoTreeIterator extends \RecursiveArrayIterator
{
    public function getChildren()
    {
        $current = $this->current();
        $class = get_class($this);

        return new $class($current['__children']);
    }

    public function hasChildren()
    {
        $current = $this->current();

        return count($current['__children']) > 0;
    }
}
