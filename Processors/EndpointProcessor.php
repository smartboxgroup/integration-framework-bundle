<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidMessageException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEndpointRouter;

/**
 * Class EndpointProcessor
 * @package Smartbox\Integration\FrameworkBundle\Processors
 */
class EndpointProcessor extends Processor
{
    const OPTION_RETRIES = 'retries';
    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';

    const CONTEXT_ENDPOINT_REQUEST_ID = 'endpoint_request_id';
    const CONTEXT_RESOLVED_URI = 'resolved_uri';
    const CONTEXT_OPTIONS = 'options';
    const CONTEXT_producer = 'producer';

    use UsesEndpointRouter;

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
        $uri = self::resolveURIParams($exchange,$this->uri);
        $options = $this->resolveOptions($uri);
        /** @var ProducerInterface $producer */
        $producer = $options[InternalRouter::KEY_producer];

        $processingContext->set(self::CONTEXT_RESOLVED_URI, $uri);
        $processingContext->set(self::CONTEXT_OPTIONS,$options);
        $processingContext->set(self::CONTEXT_producer, $producer);
        $processingContext->set(self::CONTEXT_ENDPOINT_REQUEST_ID, uniqid(null, true));

        parent::preProcess($exchange,$processingContext);
    }

    protected function doProcess(Exchange $exchange, SerializableArray $processingContext)
    {
        /** @var ProducerInterface $producer */
        $producer = $processingContext->get(self::CONTEXT_producer);
        $producer->send($exchange,$processingContext->get(self::CONTEXT_OPTIONS));
    }

    protected function postProcess(Exchange $exchange, SerializableArray $processingContext){
        if($this->isInOnly($processingContext->get(self::CONTEXT_OPTIONS))){
            $exchange->setOut(null);
        }

        parent::postProcess($exchange,$processingContext);
    }
}
