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

/**
 * Class AsyncQueueConsumer
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues
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
     * {@inheritdoc}
     */
    protected function initialize(EndpointInterface $endpoint)
    {
        if (!($driver = $this->getQueueDriver($endpoint))->isConnected()) {
            $driver->connect();
        }
    }

    /**
     * @param EndpointInterface $endpoint
     *
     * @return QueueDriverInterface
     */
    protected function getQueueDriver(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $queueDriverName = $options[QueueProtocol::OPTION_QUEUE_DRIVER];
        $queueDriver = $this->smartesbHelper->getQueueDriver($queueDriverName);

        if ($queueDriver instanceof QueueDriverInterface) {
            return $queueDriver;
        }

        throw new \RuntimeException(sprintf('[AsyncQueueConsumer] Driver "%s" does not implement QueueDriverInterface', $queueDriverName));
    }

    /**
     * Returns the consumer name.
     *
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return sprintf(self::CONSUMER_TAG, gethostname(), getmypid());
    }

    /**
     * Returns the queue name properly treated with queue prefix.
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
     * {@inheritdoc}
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
        $this->getQueueDriver($endpoint)->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function confirmMessage(EndpointInterface $endpoint, QueueMessageInterface $message)
    {
        $this->getQueueDriver($endpoint)->ack($message);
    }

    /**
     * {@inheritdoc}
     */
    public function asyncConsume(EndpointInterface $endpoint, callable $callback)
    {
        $queueName = $this->getQueueName($endpoint);
        $this->getQueueDriver($endpoint)->consume($this->getName(), $queueName, $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function waitNoBlock(EndpointInterface $endpoint)
    {
        $this->getQueueDriver($endpoint)->waitNoBlock();
    }

    /**
     * {@inheritdoc}
     */
    public function wait(EndpointInterface $endpoint)
    {
        $this->getQueueDriver($endpoint)->wait();
    }

    /**
     * {@inheritdoc}
     */
    protected function process(EndpointInterface $queueEndpoint, QueueMessageInterface $message)
    {
        // If we used a wrapper to queue the message, that the handler doesn't understand, unwrap it
        if ($message instanceof QueueMessageInterface && !($queueEndpoint->getHandler() instanceof QueueMessageHandlerInterface)) {
            $endpoint = $this->smartesbHelper->getEndpointFactory()->createEndpoint($message->getDestinationURI(), EndpointFactory::MODE_CONSUME);
            $queueEndpoint->getHandler()->handle($message->getBody(), $endpoint);
        } else {
            parent::process($queueEndpoint, $message);
        }
    }

    /**
     * Overrides the main callback function to convert the AMQPMessage from the queue into a QueueMessage.
     *
     * {@inheritdoc}
     */
    public function callback(EndpointInterface $endpoint): callable
    {
        return function (AMQPMessage $message) use ($endpoint) {
            try {
                $start = microtime(true);
                $queueMessage = $this->serializer->deserialize($message->getBody(), SerializableInterface::class, $this->getQueueDriver($endpoint)->getFormat());
                $this->consumptionDuration = (microtime(true) - $start) * 1000;

                $queueMessage->setMessageId($message->getDeliveryTag());
            } catch (\Exception $exception) {
                // TODO Verify "headers" are passed correctly. might need to access "data" key after get_properties
                $this->consumptionDuration = (microtime(true) - $start) * 1000;
                $this->getExceptionHandler()($exception, ['headers' => $message->get_properties(), 'body' => $message->getBody()]);
            }

            parent::callback($endpoint)($queueMessage);
        };
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(EndpointInterface $endpoint)
    {
        $this->getQueueDriver($endpoint)->destroy($this);
    }
}
