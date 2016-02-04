<?php

namespace Smartbox\Integration\FrameworkBundle\Drivers\Db;
use Smartbox\Integration\FrameworkBundle\Messages\DB\NoSQLMessageInterface;

/**
 * Interface NoSQLDriverInterface
 * @package Smartbox\Integration\FrameworkBundle\Drivers\Db
 */
interface NoSQLDriverInterface
{
    /**
     * @param NoSQLMessageInterface $message
     * @return string Id of the created record
     */
    public function create(NoSQLMessageInterface $message);

    /**
     * @param NoSQLMessageInterface $message
     * @return boolean
     */
    public function update(NoSQLMessageInterface $message);

    /**
     * @param NoSQLMessageInterface $message
     * @return boolean
     */
    public function delete(NoSQLMessageInterface $message);

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
    public function read($collection, array $query = [], $options = []);

    /**
     * @return NoSQLMessageInterface
     */
    public function createMessage();
}
