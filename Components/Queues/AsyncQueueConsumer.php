<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;

/**
 * Class PhpAmqpSignalConsumer.
 */
class AsyncQueueConsumer extends AbstractAsyncConsumer
{
    use UsesSmartesbHelper;
    use UsesSerializer;
    use UsesExceptionHandlerTrait;

    /**
     * Consumer identifier name.
     */
    const CONSUMER_TAG = 'amqp-consumer-%s-%s';

    /**
     * @var string
     */
    protected $format = QueueDriverInterface::FORMAT_JSON;

    /**
     * @var PhpAmqpLibDriver
     */
    protected $driver;

    /**
     * Set the driver to this class with the properties fulfilled.
     *
     * @param $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(EndpointInterface $endpoint)
    {
        if (!$this->driver->isConnected()) {
            $this->driver->connect();
        }
    }

    /**
     * Returns the consumer name.
     *
     * {@inheritdoc}
     */
    public function getName()
    {
        return sprintf(self::CONSUMER_TAG, gethostname(), getmypid());
    }

    /**
     * Returns the queue name properly treated with queue prefix.
     */
    protected function getQueueName(EndpointInterface $endpoint): string
    {
        $options = $endpoint->getOptions();

        return "{$options[QueueProtocol::OPTION_PREFIX]}{$options[QueueProtocol::OPTION_QUEUE_NAME]}";
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
        $this->driver->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        $this->driver->ack($message->getMessageId());
    }

    public function asyncConsume(EndpointInterface $endpoint, callable $callback)
    {
        $this->driver->declareChannel();
        $queueName = $this->getQueueName($endpoint);

        $this->driver->declareQueue($queueName);
        $this->driver->consume($this->getName(), $queueName, $callback);
    }

    public function waitNoBlock()
    {
        if ($this->driver->isConsuming()) {
            $this->driver->waitNoBlock();
        }
    }

    public function wait()
    {
        if ($this->driver->isConsuming()) {
            $this->driver->wait();
        }
    }

    protected function process(EndpointInterface $queueEndpoint, QueueMessageInterface $message)
    {
        $endpoint = $this->smartesbHelper->getEndpointFactory()->createEndpoint($message->getDestinationURI(), EndpointFactory::MODE_CONSUME);
        $queueEndpoint->getHandler()->handle($message->getBody(), $endpoint);
    }

    /**
     * Overrides the main callback function to convert the AMQPMessage from the queue into a QueueMessage.
     *
     * {@inheritDoc}
     */
    public function callback(EndpointInterface $endpoint)
    {
        return function (AMQPMessage $message) use ($endpoint) {
            try {
                $start = microtime(true);
                $queueMessage = $this->serializer->deserialize($message->getBody(), SerializableInterface::class, $this->driver->getFormat());
                $this->consumptionDuration = (microtime(true) - $start) * 1000;

                $queueMessage->setMessageId($message->getDeliveryTag());
            } catch (\Exception $exception) {
                // TODO Verify "headers" are passed correctly. might need to access "data" key after get_properties
                $deserializationTime = (microtime(true) - $start) * 1000;
                $this->getExceptionHandler()($exception, ['headers' => $message->get_properties(), 'body' => $message->getBody()]);
            }

            parent::callback($endpoint)($queueMessage);
        };
    }

}
