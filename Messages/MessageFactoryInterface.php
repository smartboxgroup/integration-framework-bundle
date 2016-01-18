<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;


use Smartbox\CoreBundle\Type\SerializableInterface;

interface MessageFactoryInterface {

    /**
     * @param SerializableInterface $body
     * @param array $headers
     * @param Context $context
     * @return MessageInterface
     */
    public function createMessage(SerializableInterface $body = null, $headers = array(), Context $context = null);

}