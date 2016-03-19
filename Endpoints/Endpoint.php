<?php

namespace Smartbox\Integration\FrameworkBundle\Endpoints;


use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Producers\ProducerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use JMS\Serializer\Annotation as JMS;

class Endpoint implements EndpointInterface
{
    use HasInternalType;

    /**
     * @JMS\Exclude
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY, self::EXCHANGE_PATTERN_IN_OUT];

    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';


    /**
     * @var array
     */
    protected $options = null;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     * @var SerializableArray
     */
    protected $uri = null;

    /** @var  ConsumerInterface */
    protected $consumer = null;

    /** @var  ProducerInterface */
    protected $producer = null;


    /**
     * @param string $resolvedUri
     * @param array $options
     */
    public function __construct($resolvedUri, array $options)
    {
        $optionsResolver = new OptionsResolver();
        $this->uri = $resolvedUri;
        $this->configureOptionsResolver($optionsResolver);

        // Get Consumer
        if (array_key_exists(self::OPTION_CONSUMER, $options)) {
            $consumer = $options[self::OPTION_CONSUMER];
            if ($consumer instanceof ConsumerInterface){
                $this->consumer = $consumer;
                if($consumer instanceof ConfigurableInterface){
                    $consumer->configureOptionsResolver($optionsResolver);
                }
                unset($options[self::OPTION_CONSUMER]);
            }else{
                throw new \RuntimeException(
                    "Consumers must implement ConsumerInterface. Found consumer class for endpoint with URI: "
                    .$this->getURI()
                    ." that does not implement ConsumerInterface."
                );
            }
        }

        // Get producer
        if (array_key_exists(self::OPTION_PRODUCER, $options)) {
            $producer = $options[self::OPTION_PRODUCER];
            if ($producer instanceof ProducerInterface) {
                $this->producer = $producer;
                if($producer instanceof ConfigurableInterface){
                    $producer->configureOptionsResolver($optionsResolver);
                }
                unset($options[self::OPTION_PRODUCER]);
            } else {
                throw new \RuntimeException(
                    "Producers must implement ProducerInterface. Found producer class for endpoint with URI: "
                    .$this->getURI()
                    ." that does not implement ProducerInterface."
                );
            }
        }

        $this->options = $optionsResolver->resolve($options);
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
    public function consume($maxAmount = 0)
    {
        if($maxAmount > 0){
            $this->getConsumer()->setExpirationCount($maxAmount);
        }

        $this->getConsumer()->consume($this);
    }

    /**
     * @return boolean
     */
    public function produce(Exchange $exchange)
    {
        $this->getProducer()->send($exchange, $this);
        if($this->isInOnly()){
            $exchange->setOut(null);
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($optionName){
        if(!array_key_exists($optionName,$this->options)){
            throw new \InvalidArgumentException("The option $optionName does not exist in this Endpoint");
        }

        return $this->options[$optionName];
    }

    public function getExchangePattern(){
        return $this->options[self::OPTION_EXCHANGE_PATTERN];
    }

    public function isInOnly(){
        return $this->getExchangePattern() == self::EXCHANGE_PATTERN_IN_ONLY;
    }

    public function shouldTrack(){
        return $this->options[self::OPTION_TRACK];
    }

    /**
     * Get static default options
     *
     * @return array Array with option name, description, and options (optional)
     */
    public function getOptionsDescriptions()
    {
        $mainArray = array(
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
        );

        $consumerArray = [];
        $producerArray = [];

        if($this->producer instanceof ConfigurableInterface){
            $producerArray = $this->producer->getOptionsDescriptions();
        }

        if($this->consumer instanceof ConfigurableInterface){
            $consumerArray = $this->consumer->getOptionsDescriptions();
        }

        return array_merge($mainArray,$producerArray,$consumerArray);
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