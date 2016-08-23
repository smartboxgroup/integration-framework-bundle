<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Miscellaneous;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Core\Processors\ProcessorInterface;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Tools\Logs\LogsExchangeDetails;

/**
 * Class Process.
 */
class Process extends Processor implements LogsExchangeDetails
{
    /**
     * @var ProcessorInterface
     */
    protected $processor;

    /**
     * @param ProcessorInterface $processor
     */
    public function setProcessor(ProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     *
     * @return bool
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        try {
            return $this->processor->process($exchange);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Processor could not process exchange: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function onPreProcessEvent(ProcessEvent $event)
    {
        $event->setEventDetails('About to process exchange.');
    }

    /**
     * {@inheritdoc}
     */
    protected function onPostProcessEvent(ProcessEvent $event)
    {
        $event->setEventDetails('Exchange processed successfully.');
    }
}
