<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Traits\HasProcessingContext;

/**
 * Class CallbackExchangeEnvelope.
 */
class CallbackExchangeEnvelope extends ExchangeEnvelope
{
    use HasProcessingContext;

    const HEADER_CREATED_AT = 'created_at';
    const HEADER_ERROR_MESSAGE = 'error_message';
    const HEADER_ERROR_PROCESSOR_ID = 'error_processor_id';
    const HEADER_ERROR_PROCESSOR_DESCRIPTION = 'error_processor_description';
    const HEADER_STATUS = 'status';
    const HEADER_STATUS_CODE = 'status_code';
    const HEADER_STATUS_FAILED = 'failure';
    const HEADER_STATUS_SUCCESS = 'success';

    /**
     * FailedExchangeEnvelope constructor.
     *
     * @param Exchange|null          $exchange
     * @param SerializableArray|null $processingContext
     */
    public function __construct(Exchange $exchange, SerializableArray $processingContext = null)
    {
        parent::__construct($exchange);
        $this->processingContext = $processingContext;
    }
}
