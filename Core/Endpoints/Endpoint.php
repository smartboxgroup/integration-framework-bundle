<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Endpoints;


use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\ProtocolInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Endpoint implements EndpointInterface
{
    use HasInternalType;

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

    /** @var  ProtocolInterface */
    protected $protocol = null;

    /** @var  ConsumerInterface */
    protected $consumer = null;

    /** @var  ProducerInterface */
    protected $producer = null;

    /** @var HandlerInterface */
    protected $handler = null;

    /**
     * @param $resolvedUri
     * @param array $options
     * @param ProtocolInterface $protocol
     * @param ProducerInterface $producer
     * @param ConsumerInterface $consumer
     * @param HandlerInterface $handler
     */
    public function __construct($resolvedUri, array &$options, ProtocolInterface $protocol, ProducerInterface $producer = null, ConsumerInterface $consumer = null, HandlerInterface $handler = null)
    {
        $this->uri = $resolvedUri;
        $this->consumer = $consumer;
        $this->producer = $producer;
        $this->handler = $handler;
        $this->protocol = $protocol;
        $this->options = $options;
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
     * @return ProtocolInterface
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return HandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
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
     * @param MessageInterface $message
     * @return MessageInterface
     */
    public function handle(MessageInterface $message){
        return $this->getHandler()->handle($message,$this);
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
        return $this->options[Protocol::OPTION_EXCHANGE_PATTERN];
    }

    public function isInOnly(){
        return $this->getExchangePattern() == Protocol::EXCHANGE_PATTERN_IN_ONLY;
    }

    public function shouldTrack(){
        return $this->options[Protocol::OPTION_TRACK];
    }
}