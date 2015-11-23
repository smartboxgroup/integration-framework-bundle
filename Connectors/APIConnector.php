<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\NotSupportedException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Processors\Endpoint;

/**
 * Class APIConnector
 * @package Smartbox\Integration\FrameworkBundle\Connectors
 */
abstract class APIConnector extends Connector
{
    const OPTION_TIMEOUT = 'timeout';
    public static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_OUT];

    protected $defaultOptions = array(
        self::OPTION_RETRIES => 5,
        self::OPTION_TIMEOUT => 500,        // ms
    );

    /**
     * {@inheritDoc}
     */
    public function getDefaultOptions()
    {
        return array_merge(
            parent::getDefaultOptions(),
            $this->defaultOptions
        );
    }

    /** {@inheritDoc} */
    public function send(Exchange $exchange, array $options)
    {
        $in = $this->translateFromCanonical($exchange->getIn()->getBody(),$options);
        $result = $this->execute($in,$options);
        $out = $this->translateToCanonical($result,$options);

        $exchange->getIn()->setBody($out);
    }

    /**
     * Executes the command in the message, returns the response as a message
     *
     * @param $entity
     * @param array $options
     * @return mixed
     */
    protected abstract function execute($entity, array $options);

    /**
     * Transform data from the canonical model to the model that this connector understands
     *
     * @param SerializableInterface $entity
     * @param array $options
     * @return mixed
     */
    protected abstract function translateFromCanonical(SerializableInterface $entity = null, array $options);

    /**
     * Transform data from the the model that this connector understands to the canonical model
     *
     * @param $data
     * @param array $options
     * @return mixed
     */
    protected abstract function translateToCanonical($data, array $options);
}
