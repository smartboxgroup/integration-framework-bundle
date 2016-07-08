<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory;

class InfiniteIteratorWithAccessToLastAvailableHost extends \InfiniteIterator
{
    protected $lastAvailableHost = null;

    /**
     * @return null
     */
    public function getLastAvailableHost()
    {
        return $this->lastAvailableHost;
    }

    public function current()
    {
        $current = parent::current();
        $this->lastAvailableHost = $current;
        return $current;
    }
}