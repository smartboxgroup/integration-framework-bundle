<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AmqpLibDriver\AmqpLibDriver;

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
     * @var AmqpLibDriver
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
        $this->handler = new PhpAmqpHandler($endpoint, (int)$this->expirationCount, $this->driver->getFormat(), $this->serializer);
        $this->handler->setExceptionHandler($this->getExceptionHandler());

        if ($this->smartesbHelper) {
            $this->handler->setSmartesbHelper($this->smartesbHelper);
        }

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

    public function wait()
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
            $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
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
