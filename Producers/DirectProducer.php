<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use Smartbox\Integration\FrameworkBundle\Endpoints\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\EndpointUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Routing\InternalRouter;
use Smartbox\Integration\FrameworkBundle\Traits\UsesItinerariesRouter;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DirectProducer
 * @package Smartbox\Integration\FrameworkBundle\Producers
 */
class DirectProducer extends Producer implements ConfigurableInterface
{
    use UsesItinerariesRouter;

    const OPTION_PATH = 'path';

    /** {@inheritDoc} */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        if(!$endpoint->getURI()){
            throw new EndpointUnrecoverableException("URI not found Endpoint");
        }

        $uri = $endpoint->getURI();
        $itineraryParams = $this->findItineraryParams($uri);
        $itinerary = $itineraryParams[InternalRouter::KEY_ITINERARY];
        $headersToPropagate = $this->filterItineraryParamsToPropagate($itineraryParams);

        // Update exchange
        $ex->getItinerary()->prepend($itinerary);
        foreach ($headersToPropagate as $key => $value) {
            $ex->setHeader($key,$value);
        }
    }

    /**
     *  Key-Value array with the option name as key and the details as value
     *
     *  [OptionName => [description, array of valid values],..]
     *
     * @return array
     */
    public function getOptionsDescriptions()
    {
        return [
            self::OPTION_PATH => ['Path representing the subroutine to execute',[]],
        ];
    }

    /**
     * With this method this class can configure an OptionsResolver that will be used to validate the options
     *
     * @param OptionsResolver $resolver
     * @return mixed
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        $resolver->setDefault(Endpoint::OPTION_EXCHANGE_PATTERN,Endpoint::EXCHANGE_PATTERN_IN_ONLY);
        $resolver->setAllowedValues(Endpoint::OPTION_EXCHANGE_PATTERN, [Endpoint::EXCHANGE_PATTERN_IN_ONLY]);
        $resolver->setRequired(self::OPTION_PATH);
        $resolver->setAllowedTypes(self::OPTION_PATH,['string']);
    }
}