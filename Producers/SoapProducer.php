<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use BeSimple\SoapClient\SoapClient;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\Entity;
use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;

/**
 * Class SoapProducer
 * @package Smartbox\Integration\FrameworkBundle\Producers
 */
class SoapProducer extends APIProducer
{
    const OPTION_METHOD = 'method';
    const OPTION_SOAP_CLIENT = 'soap_client';

    protected function execute($params, array $options = array())
    {
        /** @var SoapClient $soapClient */
        $soapClient = $options[self::OPTION_SOAP_CLIENT];
        return $soapClient->__call($options[self::OPTION_METHOD], $params);
    }

    protected function translateFromCanonical(SerializableInterface $entity = null, array $options = array())
    {
        if ($entity instanceof SerializableArray) {
            return $entity->toArray();
        } else {
            throw new \Exception(
                "The default SoapProducer can only process a SerializableArray. To override this please override the translateFromCanonical method."
            );
        }
    }

    protected function translateToCanonical($data, array $options = array())
    {
        return new SerializableArray($data);
    }

    /**
     * {@inheritDoc}
     */
    public static function validateOptions(array $options, $checkComplete = false)
    {
        parent::validateOptions($options, $checkComplete);

        //validate soap client
        if(isset($options[self::OPTION_SOAP_CLIENT])) {
            $client = $options[self::OPTION_SOAP_CLIENT];
            if (! $client instanceof \SoapClient) {
                throw new InvalidOptionException(self::class, 'Invalid soap client, it must be a service extending \SoapClient');
            }
        }

        return true;
    }

    public function getAvailableOptions(){
        $availableOptions = array_merge(
            parent::getAvailableOptions(),
            [
                self::OPTION_SOAP_CLIENT => array('Soap client service extending \SoapClient', array()),
                self::OPTION_METHOD => array('Soap method to be called.', array()),
            ]
        );

        unset($availableOptions[self::OPTION_TIMEOUT]);
        unset($availableOptions[self::OPTION_RETRIES]);
        return $availableOptions;
    }
}
