<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;


use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactory;

trait MessageFactoryAware {

    /** @var  MessageFactory */
    protected $messageFactory;

    /**
     * @return MessageFactory
     */
    public function getMessageFactory()
    {
        return $this->messageFactory;
    }

    /**
     * @param MessageFactory $messageFactory
     */
    public function setMessageFactory($messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    public function getFlowsVersion(){
        return $this->messageFactory->getFlowsVersion();
    }
}