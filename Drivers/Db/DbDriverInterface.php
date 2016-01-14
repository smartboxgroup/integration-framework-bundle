<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Db;
use Smartbox\Integration\FrameworkBundle\Messages\Db\DbMessageInterface;

/**
 * Interface DbDriverInterface
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Db
 */
interface DbDriverInterface
{
    /**
     * @param DbMessageInterface $message
     * @return boolean
     */
    public function send(DbMessageInterface $message);

    /**
     * Returns One Serializable object from the queue
     *
     * It requires to subscribe previously to a specific queue
     *
     * @return DbMessageInterface|null
     * @throws \Exception
     */
    public function receive();

    /**
     * @return DbMessageInterface
     */
    public function createMessage();
}
