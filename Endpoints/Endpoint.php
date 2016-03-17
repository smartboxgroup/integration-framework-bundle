<?php

namespace Smartbox\Integration\FrameworkBundle\Endpoints;


use Smartbox\Integration\FrameworkBundle\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Endpoint implements EndpointInterface
{
    /**
     * @JMS\Exclude
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY, self::EXCHANGE_PATTERN_IN_OUT];

    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';
    const OPTION_EXCHANGE_PATTERN = 'exchangePattern';
    const OPTION_TRACK = 'track';

    const EXCHANGE_PATTERN_IN_ONLY = 'inOnly';
    const EXCHANGE_PATTERN_IN_OUT = 'inOut';

    /** @var  array */
    protected $options = [];

    /** @var  string */
    protected $uri = null;

    /** @var  ConsumerInterface */
    protected $consumer = null;

    /** @var  ProducerInterface */
    protected $producer = null;

    /**
     * @param string $resolvedUri
     * @param array $resolvedEndpointOptions
     */
    public function __construct($resolvedUri, array $resolvedEndpointOptions)
    {
        $this->uri = $resolvedUri;
        $this->options = array_merge(self::getDefaultOptions(), $resolvedEndpointOptions);
        self::validateOptions($this->options,true);

        // Get Consumer
        if (array_key_exists(self::OPTION_CONSUMER, $this->options)) {
            $consumer = $this->options[self::OPTION_CONSUMER];
            if ($consumer instanceof ConsumerInterface){
                $this->consumer = $consumer;
                unset($this->options[self::OPTION_CONSUMER]);
            }else{
                throw new \RuntimeException(
                    "Consumers must implement ConsumerInterface. Found consumer class for endpoint with URI: "
                    .$this->getURI()
                    ." that does not implement ConsumerInterface."
                );
            }
        }

        // Get producer
        if (array_key_exists(self::OPTION_PRODUCER, $this->options)) {
            $producer = $this->options[self::OPTION_PRODUCER];
            if ($producer instanceof ProducerInterface) {
                $this->producer = $producer;
                unset($this->options[self::OPTION_PRODUCER]);
            } else {
                throw new \RuntimeException(
                    "Producers must implement ProducerInterface. Found producer class for endpoint with URI: "
                    .$this->getURI()
                    ." that does not implement ProducerInterface."
                );
            }
        }
    }

    /**
     * Returns the resolved URI
     * @return string
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * @return ConsumerInterface
     */
    public function getConsumer()
    {
        if (!$this->consumer) {
            throw new ResourceNotFoundException("Consumer not found for URI: ".$this->getURI());
        }

        return $this->consumer;
    }

    /**
     * @return ProducerInterface
     */
    public function getProducer()
    {
        if (!$this->producer) {
            throw new ResourceNotFoundException("Producer not found for URI: ".$this->getURI());
        }

        return $this->producer;
    }

    /**
     * @return MessageInterface
     */
    public function consume()
    {
        $this->getConsumer()->consume($this->getOptions());
    }

    /**
     * @return boolean
     */
    public function produce(Exchange $exchange)
    {
        $this->getProducer()->send($exchange, $this->getOptions());
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Validates the options passed to an endpoint
     *
     * @param array $options
     * @param bool $checkComplete
     * @return bool
     * @throws InvalidOptionException in case one of the options is not valid
     */
    public static function validateOptions(array $options, $checkComplete = false)
    {
        // validate exchange pattern
        if (isset($options[self::OPTION_EXCHANGE_PATTERN])) {
            $exchangePattern = $options[self::OPTION_EXCHANGE_PATTERN];
            if (!in_array($exchangePattern, static::$SUPPORTED_EXCHANGE_PATTERNS)) {
                throw new InvalidOptionException(
                    self::class, self::OPTION_EXCHANGE_PATTERN,
                    sprintf(
                        'Invalid exchange pattern "%s", supported values: %s',
                        $exchangePattern,
                        implode(', ', static::$SUPPORTED_EXCHANGE_PATTERNS)
                    )
                );
            }
        } elseif ($checkComplete) {
            throw new InvalidOptionException(self::class, self::OPTION_EXCHANGE_PATTERN, 'Missing exchange pattern');
        }

        // validate username
        if (isset($options[self::OPTION_USERNAME])) {
            $username = $options[self::OPTION_USERNAME];
            if (!is_string($username)) {
                throw new InvalidOptionException(self::class, 'Invalid username, only strings allowed');
            }
        }

        //validate password
        if (isset($options[self::OPTION_PASSWORD])) {
            $password = $options[self::OPTION_PASSWORD];
            if (!is_string($password)) {
                throw new InvalidOptionException(self::class, 'Invalid password, only strings allowed');
            }
        }

        return true;
    }

    /**
     * Get static default options
     *
     * @return array
     */
    public static function getDefaultOptions()
    {
        return array(
            self::OPTION_USERNAME => '',
            self::OPTION_PASSWORD => '',
            self::OPTION_EXCHANGE_PATTERN => self::EXCHANGE_PATTERN_IN_OUT,
            self::OPTION_TRACK => false
        );
    }

    /**
     * Get static default options
     *
     * @return array Array with option name, description, and options (optional)
     */
    public static function getAvailableOptions()
    {
        return array(
            self::OPTION_EXCHANGE_PATTERN => array(
                'Exchange pattern to communicate with this producer',
                array(
                    self::EXCHANGE_PATTERN_IN_ONLY => 'The producer will not block the flow or modify the message',
                    self::EXCHANGE_PATTERN_IN_OUT => 'The producer will block the flow and update the message'
                )
            ),
            self::OPTION_USERNAME => array('Username to authenticate in this producer', array()),
            self::OPTION_PASSWORD => array('Password to authenticate in this producer', array()),
            self::OPTION_TRACK => array('Whether to track the events this endpoint or not', array()),
        );
    }
}