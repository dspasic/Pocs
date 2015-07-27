<?php

namespace Pocs\OpCache;

class Status extends \ArrayObject
{
    public function __construct()
    {
        parent::__construct(opcache_get_status());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return print_r($this, true);
    }
}
