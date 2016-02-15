<?php

namespace Smartbox\Integration\FrameworkBundle\Messages\Queues;

use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;

/**
 * Interface QueueMessageInterface
 * @package Smartbox\Integration\FrameworkBundle\Messages\Queues
 */
interface QueueMessageInterface extends MessageInterface
{
    /**
     * @param string $queue
     */
    public function setQueue($queue);

    /**
     * @param int $expires
     */
    public function setExpires($expires);

    /**
     * @param int $ttl
     */
    public function setTTL($ttl);

    /**
     * @param string $type
     */
    public function setMessageType($type);

    /**
     * @param string $priority
     */
    public function setPriority($priority);

    /**
     * @param bool $persistent
     */
    public function setPersistent($persistent);

    /**
     * @param string $reason
     */
    public function setReasonForFailure($reason);

    /**
     * @return string
     */
    public function getQueue();

    /**
     * @return int
     */
    public function getExpires();

    /**
     * @return int
     */
    public function getTTL();

    /**
     * @return string
     */
    public function getVersion();

    /**
     * @return string
     */
    public function getMessageType();

    /**
     * @return int
     */
    public function getPriority();

    /**
     * @return boolean
     */
    public function getPersistent();

    /**
     * @return string
     */
    public function getReasonForFailure();

    /**
     * @return MessageInterface
     */
    public function getBody();

    /**
     * @param string $uri
     */
    public function setDestinationURI($uri);

    /**
     * @return null|string
     */
    public function getDestinationURI();

}
