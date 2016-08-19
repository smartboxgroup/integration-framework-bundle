<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableInterface;

/**
 * Interface MessageFactoryInterface.
 */
interface MessageFactoryInterface
{
    /**
     * @param SerializableInterface $body
     * @param array                 $headers
     * @param Context               $context
     *
     * @return MessageInterface
     */
    public function createMessage(SerializableInterface $body = null, $headers = [], Context $context = null);
}
