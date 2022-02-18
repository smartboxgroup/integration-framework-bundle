<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\ControlFlow;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

class DelayInterceptor extends Processor
{
    const CACHE_PREFIX = 'delay_interceptor_';

    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
    }
}
