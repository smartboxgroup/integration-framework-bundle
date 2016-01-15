<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Db;
use Smartbox\Integration\FrameworkBundle\Messages\Db\NoSQLMessageInterface;

/**
 * Interface NoSQLDriverInterface
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Db
 */
interface NoSQLDriverInterface
{
    /**
     * @param NoSQLMessageInterface $message
     * @return boolean
     */
    public function send(NoSQLMessageInterface $message);

    /**
     * Returns One Serializable object from the queue
     *
     * It requires to subscribe previously to a specific queue
     *
     * @param string $collection
     * @param array $query
     * @param array $options
     * @return null|NoSQLMessageInterface
     */
    public function receive($collection, array $query = [], $options = []);

    /**
     * @return NoSQLMessageInterface
     */
    public function createMessage();
}
