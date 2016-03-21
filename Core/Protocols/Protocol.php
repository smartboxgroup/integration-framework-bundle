<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Protocols;

use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("all")
 */
class Protocol implements ProtocolInterface {
    /**
     * @JMS\Exclude
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY, self::EXCHANGE_PATTERN_IN_OUT];

    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';

    const OPTION_CONSUMER = '_consumer';
    const OPTION_PRODUCER = '_producer';
    const OPTION_HANDLER = '_handler';
    const OPTION_PROTOCOL = '_protocol';

    const OPTION_EXCHANGE_PATTERN = 'exchangePattern';
    const OPTION_TRACK = 'track';
    const OPTION_ENDPOINT_ROUTE = '_route';

    const EXCHANGE_PATTERN_IN_ONLY = 'inOnly';
    const EXCHANGE_PATTERN_IN_OUT = 'inOut';

    /**
     * @var HandlerInterface
     */
    protected $defaultHandler;

    /**
     * @var ConsumerInterface
     */
    protected $defaultConsumer;

    /**
     * @var ProducerInterface
     */
    protected $defaultProducer;

    /**
     * @param HandlerInterface $defaultHandler
     */
    public function setDefaultHandler(HandlerInterface $defaultHandler)
    {
        $this->defaultHandler = $defaultHandler;
    }

    /**
     * @param ConsumerInterface $defaultConsumer
     */
    public function setDefaultConsumer(ConsumerInterface $defaultConsumer)
    {
        $this->defaultConsumer = $defaultConsumer;
    }

    /**
     * @param ProducerInterface $defaultProducer
     */
    public function setDefaultProducer(ProducerInterface $defaultProducer)
    {
        $this->defaultProducer = $defaultProducer;
    }

    /**
     * @return HandlerInterface
     */
    public function getDefaultHandler()
    {
        return $this->defaultHandler;
    }

    /**
     * @return ConsumerInterface
     */
    public function getDefaultConsumer()
    {
        return $this->defaultConsumer;
    }

    /**
     * @return ProducerInterface
     */
    public function getDefaultProducer()
    {
        return $this->defaultProducer;
    }

    /**
     * Get static default options
     *
     * @return array Array with option name, description, and options (optional)
     */
    public function getOptionsDescriptions()
    {
        return [
            self::OPTION_ENDPOINT_ROUTE =>  array('Internal use only: Route that was applied to resolve the Endpoint URI', array()),
            self::OPTION_EXCHANGE_PATTERN => array(
                'Exchange pattern to communicate with this endpoint',
                array(
                    self::EXCHANGE_PATTERN_IN_ONLY => 'The endpoint will not block the flow or modify the message',
                    self::EXCHANGE_PATTERN_IN_OUT => 'The endpoint will block the flow and update the message'
                )
            ),
            self::OPTION_USERNAME => array('Username to authenticate in this endpoint', array()),
            self::OPTION_PASSWORD => array('Password to authenticate in this endpoint', array()),
            self::OPTION_TRACK => array('Whether to track the events this endpoint or not', array()),
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
        $resolver->setDefaults([
            self::OPTION_USERNAME => '',
            self::OPTION_PASSWORD => '',
            self::OPTION_EXCHANGE_PATTERN => self::EXCHANGE_PATTERN_IN_OUT,
            self::OPTION_TRACK => false,
            self::OPTION_ENDPOINT_ROUTE => ''
        ]);

        if($this->defaultConsumer){
            $resolver->setDefault(self::OPTION_CONSUMER,$this->defaultConsumer);
        }

        if($this->defaultHandler){
            $resolver->setDefault(self::OPTION_HANDLER,$this->defaultHandler);
        }

        if($this->defaultProducer){
            $resolver->setDefault(self::OPTION_PRODUCER,$this->defaultProducer);
        }

        $resolver->setRequired([
            self::OPTION_EXCHANGE_PATTERN, self::OPTION_TRACK, self::OPTION_ENDPOINT_ROUTE
        ]);

        $resolver->setAllowedTypes(self::OPTION_USERNAME,['string']);
        $resolver->setAllowedTypes(self::OPTION_PASSWORD,['string']);
        $resolver->setAllowedTypes(self::OPTION_EXCHANGE_PATTERN,['string']);
        $resolver->setAllowedValues(self::OPTION_EXCHANGE_PATTERN,self::$SUPPORTED_EXCHANGE_PATTERNS);
        $resolver->setAllowedTypes(self::OPTION_TRACK,['bool']);
        $resolver->setAllowedTypes(self::OPTION_ENDPOINT_ROUTE,['string']);
    }
}