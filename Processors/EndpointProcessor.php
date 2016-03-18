<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEndpointFactory;

/**
 * Class EndpointProcessor
 * @package Smartbox\Integration\FrameworkBundle\Processors
 */
class EndpointProcessor extends Processor
{
    const CONTEXT_ENDPOINT_REQUEST_ID = 'endpoint_request_id';
    const CONTEXT_RESOLVED_URI = 'resolved_uri';
    const CONTEXT_OPTIONS = 'options';
    const CONTEXT_ENDPOINT = 'endpoint';

    use UsesEndpointFactory;

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
     */
    protected function preProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $resolvedUri = EndpointFactory::resolveURIParams($exchange,$this->uri);
        $endpoint = $this->getEndpointFactory()->createEndpoint($resolvedUri);

        $processingContext->set(self::CONTEXT_RESOLVED_URI, $resolvedUri);  // TODO: REMOVE FROM CONTEXT, IS IN ENDPOINT
        $processingContext->set(self::CONTEXT_OPTIONS,$endpoint->getOptions()); // TODO: REMOVE FROM CONTEXT, IS IN ENDPOINT
        $processingContext->set(self::CONTEXT_ENDPOINT, $endpoint);
        $processingContext->set(self::CONTEXT_ENDPOINT_REQUEST_ID, uniqid(null, true));

        parent::preProcess($exchange,$processingContext);
    }

    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        /** @var EndpointInterface $endpoint */
        $endpoint = $processingContext->get(self::CONTEXT_ENDPOINT);
        $endpoint->produce($exchange);
    }

    protected function postProcess(Exchange $exchange, SerializableArray $processingContext){
        parent::postProcess($exchange,$processingContext);
    }
}
