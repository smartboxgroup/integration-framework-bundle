<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp;

use CentralDesktop\Stomp\Connection as BaseConnection;
use Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory\FactoryI;

class Connection extends BaseConnection
{
    /**
     * @var FactoryI
     */
    private $connectionFactory;

    /**
     * Connection constructor.
     *
     * @param FactoryI $cf
     */
    public function __construct(FactoryI $cf)
    {
        parent::__construct($cf);
        $this->connectionFactory = $cf;

        $hostIterator = $this->connectionFactory->getHostIterator();
        $attempts = $hostIterator->count();
        if ($attempts > 1) {
            // failover transport needs to have at least the same number ot attempts as nodes
            $this->_attempts = $attempts;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _makeConnection()
    {
        try {
            parent::_makeConnection();
            $this->connectionFactory->notifyAboutSuccessfulConnection();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
