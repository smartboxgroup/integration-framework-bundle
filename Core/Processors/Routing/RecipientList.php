<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Routing;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesItineraryResolver;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Events\NewExchangeEvent;

class RecipientList extends Processor
{
    use UsesItineraryResolver;
    use UsesEvaluator;

    const AGGREGATION_STRATEGY_FIRE_AND_FORGET = 'fireAndForget';

    private $delimiter;
    private $expression;
    private $aggregationStrategy;

    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function setExpression(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @param string $aggregationStrategy
     */
    public function setAggregationStrategy(string $aggregationStrategy)
    {
        if (!in_array($aggregationStrategy, $this->getAvailableAggregationStrategies())) {
            throw new \InvalidArgumentException("Unsupported aggregation strategy: '$aggregationStrategy '");
        }
        $this->aggregationStrategy = $aggregationStrategy;
    }

    /**
     * Method returns array of available aggregation strategies.
     *
     * @return array
     */
    private function getAvailableAggregationStrategies()
    {
        return [
            self::AGGREGATION_STRATEGY_FIRE_AND_FORGET,
        ];
    }

    /**
     *
     * @param Exchange $mainExchange
     */
    protected function doProcess(Exchange $mainExchange, SerializableArray $processingContext)
    {
        $evaluator = $this->getEvaluator();

        try {
            $recipientList = $evaluator->evaluateWithExchange($this->expression, $mainExchange);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Recipient list could not evaluate expression: "%s" %s',
                    $this->expression,
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        $uris = explode($this->delimiter, $recipientList);

        foreach ($uris as $uri) {
            switch ($this->aggregationStrategy) {
                case self::AGGREGATION_STRATEGY_FIRE_AND_FORGET:
                    $this->executeFireAndForgetStrategy($mainExchange, $uri);
                    break;
            }
        }
    }

    /**
     * @param Exchange $mainExchange
     */
    private function executeFireAndForgetStrategy(Exchange $mainExchange, string $uri)
    {
        $exchange = new Exchange();

        // Set Itinerary
        $version = $mainExchange->getIn()->getContext()->get(Context::FLOWS_VERSION);

        $itineraryParams = $this->itineraryResolver->getItineraryParams($uri, $version);
        $itinerary = $itineraryParams[InternalRouter::KEY_ITINERARY];

        $exchange->getItinerary()->prepend($itinerary);
        $exchange->getItinerary()->setName('Recipient list from "'.$mainExchange->getItinerary()->getName().'"');

        // Set Headers
        if (!empty($mainExchange->getHeaders())) {
            $exchange->setHeaders($mainExchange->getHeaders());
        }

        $exchange->setHeader(Exchange::HEADER_PARENT_EXCHANGE, $mainExchange->getId());

        $headersToPropagate = $this->itineraryResolver->filterItineraryParamsToPropagate($itineraryParams);

        foreach ($headersToPropagate as $key => $value) {
            $exchange->setHeader($key, $value);
        }

        // Set Message
        $msgCopy = unserialize(serialize($mainExchange->getIn()));
        $exchange->setIn($msgCopy);

        $event = new NewExchangeEvent($exchange);
        $event->setTimestampToCurrent();
        $this->eventDispatcher->dispatch(NewExchangeEvent::TYPE_NEW_EXCHANGE_EVENT, $event);
    }
}
