<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointFactory;

/**
 * Class EndpointProcessor.
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
     * @param Exchange          $exchange
     * @param SerializableArray $processingContext
     *
     * @return bool
     *
     * @throws EndpointUnrecoverableException
     */
    protected function preProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        $resolvedUri = EndpointFactory::resolveURIParams($exchange, $this->uri);
        $endpoint = $this->getEndpointFactory()->createEndpoint($resolvedUri);

        $processingContext->set(self::CONTEXT_RESOLVED_URI, $resolvedUri);  // TODO: REMOVE FROM CONTEXT, IS IN ENDPOINT
        $processingContext->set(self::CONTEXT_OPTIONS, $endpoint->getOptions()); // TODO: REMOVE FROM CONTEXT, IS IN ENDPOINT
        $processingContext->set(self::CONTEXT_ENDPOINT, $endpoint);
        $processingContext->set(self::CONTEXT_ENDPOINT_REQUEST_ID, uniqid(null, true));

        parent::preProcess($exchange, $processingContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        /** @var EndpointInterface $endpoint */
        $endpoint = $processingContext->get(self::CONTEXT_ENDPOINT);
        $endpoint->produce($exchange);
    }

    /**
     * {@inheritdoc}
     */
    protected function postProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        parent::postProcess($exchange, $processingContext);
    }
}
