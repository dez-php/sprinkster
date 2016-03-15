<?php

namespace Core\Acl;

class Resource implements \Core\Acl\Resource\ResourceInterface
{
    /**
     * Unique id of Resource
     *
     * @var string
     */
    protected $_resourceId;

    /**
     * Sets the Resource identifier
     *
     * @param  string $resourceId
     * @return void
     */
    public function __construct($resourceId)
    {
        $this->_resourceId = (string) $resourceId;
    }

    /**
     * Defined by \Core\Acl\Resource\ResourceInterface; returns the Resource identifier
     *
     * @return string
     */
    public function getResourceId()
    {
        return $this->_resourceId;
    }

    /**
     * Defined by \Core\Acl\Resource\ResourceInterface; returns the Resource identifier
     * Proxies to getResourceId()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getResourceId();
    }
}
