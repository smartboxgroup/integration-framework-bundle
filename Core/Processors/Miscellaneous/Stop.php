<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Miscellaneous;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

/**
 * Class Stop
 * @package Smartbox\Integration\FrameworkBundle\Core\Processors\Miscellaneous
 */
class Stop extends Processor
{
    /**
     * The current implementation assumes the existence of only one aggregation strategy which ignores the child
     * exchanges
     *
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $mainExchange
     */
    protected function doProcess(Exchange $mainExchange, SerializableArray $processingContext)
    {
        $mainExchange->getItinerary()->setProcessors([]);
    }
}
