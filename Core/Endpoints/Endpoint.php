<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Endpoints;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\ProtocolInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @JMS\ExclusionPolicy("all")
 */
class Endpoint implements EndpointInterface
{
    use HasInternalType;

    protected $options = null;

    /**
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\Type("string")
     *
     * @var SerializableArray
     */
    protected $uri = null;

    /** @var ProtocolInterface */
    protected $protocol = null;

    /** @var ConsumerInterface */
    protected $consumer = null;

    /** @var ProducerInterface */
    protected $producer = null;

    /** @var HandlerInterface */
    protected $handler = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $resolvedUri,
        array &$options,
        ProtocolInterface $protocol,
        ProducerInterface $producer = null,
        ConsumerInterface $consumer = null,
        HandlerInterface $handler = null
    ) {
        $this->uri = $resolvedUri;
        $this->consumer = $consumer;
        $this->producer = $producer;
        $this->handler = $handler;
        $this->protocol = $protocol;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler()
    {
        if (!$this->handler) {
            throw new ResourceNotFoundException('Handler not found for URI: '.$this->getURI());
        }

        return $this->handler;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumer()
    {
        if (!$this->consumer) {
            throw new ResourceNotFoundException('Consumer not found for URI: '.$this->getURI());
        }

        return $this->consumer;
    }

    /**
     * {@inheritdoc}
     */
    public function getProducer()
    {
        if (!$this->producer) {
            throw new ResourceNotFoundException('Producer not found for URI: '.$this->getURI());
        }

        return $this->producer;
    }

    /**
     * {@inheritdoc}
     */
    public function consume($maxAmount = 0)
    {
        try {
            if ($maxAmount > 0) {
                $this->getConsumer()->setExpirationCount($maxAmount);
            }
            $this->getConsumer()->consume($this);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function produce(Exchange $exchange)
    {
        $this->getProducer()->send($exchange, $this);
        if ($this->isInOnly()) {
            $exchange->setOut(null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MessageInterface $message)
    {
        return $this->getHandler()->handle($message, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($optionName)
    {
        if (!array_key_exists($optionName, $this->options)) {
            throw new \InvalidArgumentException("The option $optionName does not exist in this Endpoint");
        }

        return $this->options[$optionName];
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangePattern()
    {
        return $this->options[Protocol::OPTION_EXCHANGE_PATTERN];
    }

    /**
     * {@inheritdoc}
     */
    public function isInOnly()
    {
        return Protocol::EXCHANGE_PATTERN_IN_ONLY == $this->getExchangePattern();
    }

    /**
     * {@inheritdoc}
     */
    public function shouldTrack()
    {
        return $this->options[Protocol::OPTION_TRACK];
    }
}
