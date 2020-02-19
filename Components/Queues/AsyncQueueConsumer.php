<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;

/**
 * Class PhpAmqpSignalConsumer
 *
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues
 */
class AsyncQueueConsumer extends AbstractAsyncConsumer
{
    use IsStopableConsumer;
    use UsesSmartesbHelper;
    use UsesSerializer;
    use UsesExceptionHandlerTrait;

    /**
     * Consumer identifier name
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
     * Set the driver to this class with the properties fulfilled
     *
     * @param $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * @inheritDoc
     */
    protected function initialize(EndpointInterface $endpoint)
    {
        if (!$this->driver->isConnected()) {
            $this->driver->connect();
        }
    }

    public function callback(EndpointInterface $endpoint)
    {
        return function(AMQPMessage $message) use($endpoint) {
            $this->process($endpoint, $message);
            $this->confirmMessage($endpoint, $message);
            $this->expirationCount--;
        };
    }

    /**
     * Returns the consumer name
     *
     * {@inheritdoc}
     */
    public function getName()
    {
        return sprintf(self::CONSUMER_TAG, gethostname(), getmypid());
    }

    /**
     * Returns the queue name properly treated with queue prefix
     *
     * @param EndpointInterface $endpoint
     * @return string
     */
    protected function getQueueName(EndpointInterface $endpoint): string
    {
        $options = $endpoint->getOptions();

        return "{$options[QueueProtocol::OPTION_PREFIX]}{$options[QueueProtocol::OPTION_QUEUE_NAME]}";
    }

    /**
     * @inheritDoc
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
        $this->driver->disconnect();
    }

    /**
     * @inheritDoc
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
            $this->driver->waitNonBlocking();
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