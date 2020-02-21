<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers;

/**
 * Interface QueueConnectionDriverInterface
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers
 */
interface QueueConnectionDriverInterface
{
    /**
     * Configures the driver.
     *
     * @param $host Host to connect to the queuing system
     * @param string $username Username to connect to the queuing system
     * @param string $password Password to connect to the queuing system
     * @param string $format Format used by serialize/deserialize the messages
     */
    public function configure($host, $username, $password, $format = self::FORMAT_JSON);

    /**
     * Opens a connection with a queuing system.
     */
    public function connect();

    /**
     * Destroys the connection with the queuing system.
     */
    public function disconnect();

    /**
     * Returns true if a connection already exists with the queing system, false otherwise.
     *
     * @return bool
     */
    public function isConnected();

}