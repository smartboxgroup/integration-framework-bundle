<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;

/**
 * Class AsyncQueueConsumer
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
        $this->getQueueDriver($endpoint)->connect();
    }

    /**
     * Get the driver responsible to establish a communication with the broker.
     *
     * @param EndpointInterface $endpoint
     * @return AsyncQueueDriverInterface
     */
    protected function getQueueDriver(EndpointInterface $endpoint): AsyncQueueDriverInterface
    {
        $options = $endpoint->getOptions();
        $queueDriverName = $options[QueueProtocol::OPTION_QUEUE_DRIVER];

        return $this->smartesbHelper->getQueueDriver($queueDriverName);
    }

    /**
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
        $this->getQueueDriver($endpoint)->destroy($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        /*
         * Verify first that we have a QueueMessageInterface. If not, pass null and pray that the driver is
         * keeping track of which messages need to be acked.
         */
        $this->getQueueDriver($endpoint)->ack($message instanceof QueueMessageInterface ? $message : null);
    }

    /**
     * {@inheritdoc}
     */
    protected function asyncConsume(EndpointInterface $endpoint, callable $callback)
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
    protected function process(EndpointInterface $queueEndpoint, MessageInterface $message)
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
     * @return callable
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
                $this->consumptionDuration = (microtime(true) - $start) * 1000;
                $this->getExceptionHandler()($exception, ['message' => $message]);
                return false;
            }

            parent::callback($endpoint)($queueMessage);
        };
    }
}
