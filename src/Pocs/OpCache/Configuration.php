<?php

namespace Pocs\OpCache;

class Configuration extends \ArrayObject implements \Serializable
{
    public function __construct()
    {
        parent::__construct(opcache_get_configuration());
    }

    public function serialize()
    {
        return json_encode($this->getArrayCopy());
    }

    public function unserialize($serialized)
    {
        $this->exchangeArray(json_decode($serialized, true));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return print_r($this, true);
    }
}
