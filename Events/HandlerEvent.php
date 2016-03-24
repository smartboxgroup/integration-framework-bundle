<?php

namespace Smartbox\Integration\FrameworkBundle\Events;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class HandlerEvent.
 */
class HandlerEvent extends Event
{
    const BEFORE_HANDLE_EVENT_NAME = 'smartesb.handler.before_handle';
    const AFTER_HANDLE_EVENT_NAME = 'smartesb.handler.after_handle';

    /**
     * @Assert\Type(type="Smartbox\Integration\FrameworkBundle\Core\Exchange")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("Smartbox\Integration\FrameworkBundle\Core\Exchange")
     *
     * @var \Smartbox\Integration\FrameworkBundle\Core\Exchange
     */
    protected $exchange;

    /**
     * @return \Smartbox\Integration\FrameworkBundle\Core\Exchange
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $exchange
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }
}
