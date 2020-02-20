<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
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
    private $format = QueueDriverInterface::FORMAT_JSON;

    /**
     * @var PhpAmqpLibDriver
     */
    private $driver;

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
    protected function confirmMessage(EndpointInterface $endpoint, $message)
    {
        $this->driver->ack($message->getDeliveryTag());
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

    protected function process(EndpointInterface $queueEndpoint, $message)
    {
        try {
            $message = $this->serializer->deserialize($message->getBody(), SerializableInterface::class, $this->format);
        } catch (\Exception $exception) {
            // TODO Verify "headers" are passed correctly. might need to access "data" key after get_properties
            $this->getExceptionHandler()($exception, ['headers' => $message->get_properties(), 'body' => $message->getBody()]);
        }

        // If we used a wrapper to queue the message, that the handler doesn't understand, unwrap it
        if ($message instanceof QueueMessageInterface && !($queueEndpoint->getHandler() instanceof QueueMessageHandlerInterface)) {
            $endpoint = $this->smartesbHelper->getEndpointFactory()->createEndpoint($message->getDestinationURI(), EndpointFactory::MODE_CONSUME);
            $queueEndpoint->getHandler()->handle($message->getBody(), $endpoint);
        } else {
            parent::process($queueEndpoint, $message);
        }
    }
}
