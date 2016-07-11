<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory;

use CentralDesktop\Stomp\ConnectionFactory\Simple;

/**
 * Class supporting ActiveMQ tcp transport
 * http://activemq.apache.org/tcp-transport-reference.html
 */
class SimpleConnectionStrategy extends Simple implements FactoryI
{
    /**
     * {@inheritdoc}
     */
    public function __construct($uri) {
        parent::__construct($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function getHostIterator() {
        return parent::getHostIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() {
        return parent::__toString();
    }

    public function notifyAboutSuccessfulConnection()
    {
        // nothing to do
    }
}
