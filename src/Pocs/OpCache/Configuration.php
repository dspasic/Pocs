<?php

namespace Pocs\OpCache;

class Configuration extends \ArrayObject
{
    public function __construct()
    {
        parent::__construct(opcache_get_configuration());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return print_r($this, true);
    }
}
