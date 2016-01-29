<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;

class SpyProcessor extends Processor{

    protected $receivedExchanges = [];
    protected $reached = false;

    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $this->reached = true;
        $this->receivedExchanges[] = $exchange;
    }

    /**
     * @return array
     */
    public function getReceivedExchanges()
    {
        return $this->receivedExchanges;
    }

    /**
     * @param array $receivedExchanges
     */
    public function setReceivedExchanges($receivedExchanges)
    {
        $this->receivedExchanges = $receivedExchanges;
    }

    /**
     * Check if the processor was reached during the last flow execution
     * @return bool
     */
    public function wasReached()
    {
        return $this->reached;
    }
}
