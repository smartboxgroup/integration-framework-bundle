<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesDriverRegistry;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use JMS\Serializer\Annotation as JMS;

/**
 * Class QueueConnector
 * @package Smartbox\Integration\FrameworkBundle\Connectors
 */
class QueueConnector extends Connector
{
    use UsesSerializer;
    use UsesDriverRegistry;

    /**
     * @JMS\Exclude
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY];

    /** @var QueueDriverInterface */
    protected $queueDriver;

    const OPTION_PREFIX = 'prefix';
    const OPTION_PERSISTENT = 'persistent';
    const OPTION_TTL = 'ttl';
    const OPTION_TYPE = 'type';
    const OPTION_PRIORITY = 'priority';
    const OPTION_AUTO_DISCONNECT = 'auto-disconnect';
    const OPTION_QUEUE_NAME = 'queue';
    const OPTION_QUEUE_DRIVER = 'queue_driver';

    protected $defaultOptions = array(
        self::OPTION_RETRIES => 5,
        self::OPTION_TTL => 86400,
        self::OPTION_USERNAME => '',
        self::OPTION_PASSWORD => '',
        self::OPTION_PERSISTENT => true,
        self::OPTION_PRIORITY => 4,
        self::OPTION_AUTO_DISCONNECT => true,
        self::OPTION_EXCHANGE_PATTERN => self::EXCHANGE_PATTERN_IN_ONLY,
        self::OPTION_TRACK => true
    );

    protected $headersToPropagate = array(
        Message::HEADER_EXPIRES
    );

    /**
     * {@inheritDoc}
     */
    public function getDefaultOptions()
    {
        return array_merge(
            parent::getDefaultOptions(),
            $this->defaultOptions
        );
    }

    /**
     * {@inheritDoc}
     */
    public function send(Exchange $ex, array $options)
    {
        $msg = $ex->getIn();

        $driverOption = $options[self::OPTION_QUEUE_DRIVER];
        if(is_string($driverOption)){
            /** @var QueueDriverInterface $queueDriver */
            $queueDriver = $this->getDriverRegistry()->getDriver($driverOption);
        }else{
            /** @var QueueDriverInterface $queueDriver */
            $queueDriver = $driverOption;
        }

        if(empty($queueDriver) || !$queueDriver instanceof QueueDriverInterface){
            throw new InvalidOptionException(self::class,self::OPTION_QUEUE_DRIVER,'Expected QueueDriverInterface instance');
        }

        $queueName = (@$options[self::OPTION_PREFIX]).$options[self::OPTION_QUEUE_NAME];

        $queueMessage = $queueDriver->createQueueMessage();
        $queueMessage->setBody($msg);
        $queueMessage->setTTL($options[self::OPTION_TTL]);
        $queueMessage->setQueue($queueName);
        $queueMessage->setPersistent($options[self::OPTION_PERSISTENT]);
        $queueMessage->setPriority($options[self::OPTION_PRIORITY]);
        $queueMessage->setHeader(Message::HEADER_FROM, $options[InternalRouter::KEY_URI]);

        if ($type = @$options[self::OPTION_TYPE]) {
            $queueMessage->setMessageType($type);
        }

        // Take other headers from msg
        foreach($this->headersToPropagate as $header){
            if($msg->getHeader($header)){
                $queueMessage->setHeader($header,$msg->getHeader($header));
            }
        }

        // Send
        $wasConnected = $queueDriver->isConnected();

        if(!$wasConnected){
            $queueDriver->connect();
        }

        $success = $queueDriver->send($queueMessage);

        if(!$wasConnected && @$options[self::OPTION_AUTO_DISCONNECT]){
            $queueDriver->disconnect();
        }

        if(!$success){
            throw new \RuntimeException("The message could not be delivered to the queue");
        }
    }

    public function getAvailableOptions(){
        $options = array_merge(parent::getAvailableOptions(),array(
            self::OPTION_PREFIX => array('Prefix to prepend to the queue name', array()),
            self::OPTION_QUEUE_NAME => array('Name of the queue, e.g: /boxes/pending ', array()),
            self::OPTION_QUEUE_DRIVER => array('Queue driver that should be used to talk with tclehe queueing system', array()),
            self::OPTION_PRIORITY => array('Priority for the messages in this queue', array()),
            self::OPTION_TTL => array('Time to live in seconds, after which the messages will expire', array()),
            self::OPTION_PERSISTENT => array('Whether messages coming to this queue should be persisted in disk', array()),
        ));

        unset($options[self::OPTION_USERNAME]);
        unset($options[self::OPTION_PASSWORD]);
        unset($options[self::OPTION_EXCHANGE_PATTERN]);

        return $options;
    }
}
