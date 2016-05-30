<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEndpointFactory;
use Smartbox\Integration\FrameworkBundle\Events\ProcessEvent;
use Smartbox\Integration\FrameworkBundle\Tools\Logs\LogsExchangeDetails;

/**
 * Class EndpointProcessor.
 */
class EndpointProcessor extends Processor implements LogsExchangeDetails
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
     * {@inheritdoc}
     */
    protected function onPreProcessEvent(ProcessEvent $event)
    {
        $processingContext = $event->getProcessingContext();

        $resolvedUri = EndpointFactory::resolveURIParams($event->getExchange(), $this->uri);
        $endpoint = $this->getEndpointFactory()->createEndpoint($resolvedUri);

        $processingContext->set(self::CONTEXT_RESOLVED_URI, $resolvedUri);  // TO CHECK: REMOVE FROM CONTEXT, IS IN ENDPOINT
        $processingContext->set(self::CONTEXT_OPTIONS, $endpoint->getOptions()); // TO CHECK: REMOVE FROM CONTEXT, IS IN ENDPOINT
        $processingContext->set(self::CONTEXT_ENDPOINT, $endpoint);
        $processingContext->set(self::CONTEXT_ENDPOINT_REQUEST_ID, uniqid(null, true));

        $event->setEventDetails('Calling endpoint: ' . $resolvedUri);
    }

    /**
     * {@inheritdoc}
     */
    protected function onPostProcessEvent(ProcessEvent $event)
    {
        $resolvedUri = $event->getProcessingContext()->get(self::CONTEXT_RESOLVED_URI);

        $event->setEventDetails('Returning from endpoint: ' . $resolvedUri);
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
}
