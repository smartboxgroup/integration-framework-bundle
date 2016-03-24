<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers;

use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessageInterface;

/**
 * Interface NoSQLDriverInterface.
 */
interface NoSQLDriverInterface
{
    /**
     * @param NoSQLMessageInterface $message
     *
     * @return string Id of the created record
     */
    public function create(NoSQLMessageInterface $message);

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessageInterface $message
     *
     * @return bool
     */
    public function update(NoSQLMessageInterface $message);

    /**
     * @param NoSQLMessageInterface $message
     *
     * @return bool
     */
    public function delete(NoSQLMessageInterface $message);

    /**
     * Returns One Serializable object from the queue.
     *
     * It requires to subscribe previously to a specific queue
     *
     * @param string $collection
     * @param array  $query
     * @param array  $options
     *
     * @return null|\Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessageInterface
     */
    public function read($collection, array $query = [], $options = []);

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\NoSQLMessageInterface
     */
    public function createMessage();
}
