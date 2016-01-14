<?php

namespace Smartbox\Integration\FrameworkBundle\Messages\Db;

use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;

/**
 * Interface DbMessageInterface
 * @package Smartbox\Integration\FrameworkBundle\Messages\Db
 */
interface DbMessageInterface extends MessageInterface
{
    /**
     * @param $id
     */
    public function setId($id);

    /**
     * @param $timestamp
     */
    public function setTimestamp($timestamp);

    /**
     * @param MessageInterface $message
     */
    public function setMessage(MessageInterface $message);
}
