<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;

use Symfony\Component\Validator\Constraints as Assert;

class FailedExchangeEnvelope extends ExchangeEnvelope
{
    const HEADER_ERROR_MESSAGE = 'error_message';
    const HEADER_CREATED_AT = 'created_at';
}