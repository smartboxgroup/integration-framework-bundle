<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Connectors\ConnectorInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidMessageException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesConnectorsRouter;

/**
 * Class Endpoint
 * @package Smartbox\Integration\FrameworkBundle\Processors
 */
class Endpoint extends Processor
{
    const OPTION_RETRIES = 'retries';
    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';

    const CONTEXT_RESOLVED_URI = 'resolved_uri';
    const CONTEXT_OPTIONS = 'options';
    const CONTEXT_CONNECTOR = 'connector';

    use UsesConnectorsRouter;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"logs"})
     * @JMS\Expose
     */
    protected $uri;

    /**
     * @return string
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setURI($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @param Exchange $exchange
     * @return bool
     * @throws InvalidMessageException
     */
    protected function preProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $uri = self::resolveURI($exchange,$this->uri);
        $options = $this->resolveOptions($uri);
        /** @var ConnectorInterface $connector */
        $connector = $options[InternalRouter::KEY_CONNECTOR];

        $processingContext->set('resolved_uri', $uri);
        $processingContext->set('options',$options);
        $processingContext->set('connector', $connector);

        parent::preProcess($exchange,$processingContext);
    }

    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        /** @var ConnectorInterface $connector */
        $connector = $processingContext->get(self::CONTEXT_CONNECTOR);
        $connector->send($exchange,$processingContext->get(self::CONTEXT_OPTIONS));
    }

    protected function postProcess(Exchange $exchange, SerializableArray $processingContext){
        if($this->isInOnly($processingContext->get(self::CONTEXT_OPTIONS))){
            $exchange->setOut(null);
        }

        parent::postProcess($exchange,$processingContext);
    }
}
