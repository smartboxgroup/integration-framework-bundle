<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors\Routing;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Configurability\Routing\InternalRouter;
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

    /**
     * Delimiter used if the expression returned multiple endpoints.
     *
     * @var string
     */
    private $delimiter;

    /**
     * Expression where the endpoinds are defined.
     *
     * @var string
     */
    private $expression;

    /**
     * Recipient list strategy.
     *
     * @var string
     */
    private $aggregationStrategy;

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $expression
     */
    public function setExpression(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
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
     * @return string
     */
    public function getAggregationStrategy()
    {
        return $this->aggregationStrategy;
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
     * @inheritdoc
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
     * Method to execute the recipient list fire and forget strategy.
     *
     * It creates copies of the main exchange based on how many recipients the exchange has and triggers new exchange
     * event for each of the recipients without waiting the replies of these events.
     *
     * @param Exchange $mainExchange
     * @param string $uri
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
        $headers = $mainExchange->getHeaders();
        if (!empty($headers)) {
            $exchange->setHeaders($headers);
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
