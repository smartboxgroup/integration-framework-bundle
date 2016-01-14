<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;


use Smartbox\CoreBundle\Type\SerializableInterface;

class MessageFactory implements MessageFactoryInterface  {

    protected $flowsVersion;

    /**
     * @return mixed
     */
    public function getFlowsVersion()
    {
        return $this->flowsVersion;
    }

    /**
     * @param mixed $flowsVersion
     */
    public function setFlowsVersion($flowsVersion)
    {
        $this->flowsVersion = $flowsVersion;
    }

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