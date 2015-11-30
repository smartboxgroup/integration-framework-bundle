<?php

namespace Smartbox\Integration\FrameworkBundle\Connectors;

use Smartbox\Integration\FrameworkBundle\Exceptions\InvalidOptionException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Processors\Endpoint;
use Smartbox\Integration\FrameworkBundle\Processors\Processor;
use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class Connector
 * @package Smartbox\Integration\FrameworkBundle\Connectors
 */
abstract class Connector extends Service implements ConnectorInterface
{
    public static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY, self::EXCHANGE_PATTERN_IN_OUT];

    const OPTION_RETRIES = 'retries';
    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';
    const OPTION_EXCHANGE_PATTERN = 'exchangePattern';
    const OPTION_TRACK = 'track';

    const EXCHANGE_PATTERN_IN_ONLY = 'inOnly';
    const EXCHANGE_PATTERN_IN_OUT = 'inOut';

    /**
     * Get the connector default options
     * @return array
     */
    public function getDefaultOptions() {
        return array(
            self::OPTION_RETRIES => 5,
            self::OPTION_USERNAME => '',
            self::OPTION_PASSWORD => '',
            self::OPTION_EXCHANGE_PATTERN => self::EXCHANGE_PATTERN_IN_OUT,
            self::OPTION_TRACK => false
        );
    }

    /** {@inheritDoc} */
    public abstract function send(Exchange $ex, array $options);

    /**
     * {@inheritDoc}
     */
    public static function validateOptions(array $options, $checkComplete = false)
    {
        // validate exchange pattern
        if (isset($options[self::OPTION_EXCHANGE_PATTERN])) {
            $exchangePattern = $options[self::OPTION_EXCHANGE_PATTERN];
            if (!in_array($exchangePattern, static::$SUPPORTED_EXCHANGE_PATTERNS)) {
                throw new InvalidOptionException(
                    self::class, self::OPTION_EXCHANGE_PATTERN,
                    sprintf('Invalid exchange pattern "%s", supported values: %s', $exchangePattern,
                        implode(', ', static::$SUPPORTED_EXCHANGE_PATTERNS)
                    )
                );
            }
        }elseif($checkComplete){
            throw new InvalidOptionException(self::class, self::OPTION_EXCHANGE_PATTERN,'Missing exchange pattern');
        }

        // validate retries
        if (isset($options[self::OPTION_RETRIES])) {
            $retries = $options[self::OPTION_RETRIES];
            if (!is_numeric($retries)) {
                throw new InvalidOptionException(self::class, 'Invalid retries, only numeric values allowed');
            }
        }

        // validate username
        if(isset($options[self::OPTION_USERNAME])) {
            $username = $options[self::OPTION_USERNAME];
            if (!is_string($username)) {
                throw new InvalidOptionException(self::class, 'Invalid username, only strings allowed');
            }
        }

        //validate password
        if(isset($options[self::OPTION_PASSWORD])) {
            $password = $options[self::OPTION_PASSWORD];
            if (!is_string($password)) {
                throw new InvalidOptionException(self::class, 'Invalid password, only strings allowed');
            }
        }

        return true;
    }

    public function getAvailableOptions(){
        return array(
            self::OPTION_EXCHANGE_PATTERN => array('Exchange pattern to communicate with this connector', array(
                self::EXCHANGE_PATTERN_IN_ONLY => 'The connector will not block the flow or modify the message',
                self::EXCHANGE_PATTERN_IN_OUT => 'The connector will block the flow and update the message'
            )),
            self::OPTION_RETRIES => array('Number of times that the messages should try to be redelivered to this connector', array()),
            self::OPTION_USERNAME => array('Username to authenticate in this connector', array()),
            self::OPTION_PASSWORD => array('Password to authenticate in this connector', array()),
            self::OPTION_TRACK => array('Whether to track the events this endpoint or not', array()),
        );
    }
}
