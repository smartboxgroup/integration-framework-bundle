<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Miscellaneous;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

/**
 * Class Stop.
 */
class Stop extends Processor
{
    /**
     * The current implementation assumes the existence of only one aggregation strategy which ignores the child
     * exchanges.
     *
     * @param Exchange          $mainExchange
     * @param SerializableArray $processingContext
     */
    protected function doProcess(Exchange $mainExchange, SerializableArray $processingContext)
    {
        $mainExchange->getItinerary()->setProcessorIds([]);
    }
}
