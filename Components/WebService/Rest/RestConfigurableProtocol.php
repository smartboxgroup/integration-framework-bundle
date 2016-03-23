<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;

class RestConfigurableProtocol extends ConfigurableWebserviceProtocol
{
    const OPTION_HEADERS        = 'headers';
    const OPTION_AUTH           = 'auth';
    const OPTION_BASE_URI       = 'base_uri';
    const OPTION_ENCODING       = 'encoding';

    const AUTH_BASIC            = 'basic';
}
