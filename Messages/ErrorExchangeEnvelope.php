<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Messages\Traits\HasProcessingContext;

abstract class ErrorExchangeEnvelope extends ExchangeEnvelope
{
    use HasProcessingContext;

    const HEADER_CREATED_AT = 'created_at';
    const HEADER_ERROR_MESSAGE = 'error_message';
    const HEADER_ERROR_PROCESSOR_ID = 'error_processor_id';
    const HEADER_ERROR_PROCESSOR_DESCRIPTION = 'error_processor_description';
    const HEADER_ERROR_NUM_RETRY = 'error_num_retry';

    /**
     * FailedExchangeEnvelope constructor.
     * @param Exchange|null $exchange
     * @param SerializableArray|null $processingContext
     */
    public function __construct(Exchange $exchange = null, SerializableArray $processingContext = null)
    {
        parent::__construct($exchange);
        $this->processingContext = $processingContext;
    }
}
