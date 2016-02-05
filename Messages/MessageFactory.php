<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;


use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Traits\FlowsVersionAware;

class MessageFactory implements MessageFactoryInterface  {

    use FlowsVersionAware;

    /**
     * @param SerializableInterface $body
     * @param array $headers
     * @param Context $context
     * @return Message
     */
    public function createMessage(SerializableInterface $body = null, $headers = array(), Context $context = null){

        if(!$context){
            $context = new Context([
                Context::VERSION => $this->getFlowsVersion()
            ]);
        }

        return new Message($body,$headers,$context);
    }

}