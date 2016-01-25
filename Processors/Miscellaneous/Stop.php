<?php

namespace Smartbox\Integration\FrameworkBundle\Processors\Miscellaneous;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;

/**
 * Class Stop
 * @package Smartbox\Integration\FrameworkBundle\Processors\Miscellaneous
 */
class Stop extends Processor
{
    /**
     * The current implementation assumes the existence of only one aggregation strategy which ignores the child
     * exchanges
     *
     * @param Exchange $mainExchange
     */
    protected function doProcess(Exchange $mainExchange, SerializableArray $processingContext)
    {
        $mainExchange->getItinerary()->setProcessors([]);
    }
}
