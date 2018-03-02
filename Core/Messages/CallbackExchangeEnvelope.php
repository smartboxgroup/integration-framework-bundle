<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Messages;

/**
 * Class CallbackExchangeEnvelope.
 */
class CallbackExchangeEnvelope extends ErrorExchangeEnvelope
{
    /**
     * A header to carry the status code of the of the exception.
     */
    const HEADER_STATUS_CODE = 'status_code';
}
