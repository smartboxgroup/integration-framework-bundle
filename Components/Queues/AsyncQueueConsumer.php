<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\AsyncQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables\Entity\SerializableSimpleEntity;

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

        if ($queueDriver instanceof AsyncQueueDriverInterface) {
            return $queueDriver;
        }

        throw new \RuntimeException(sprintf('[AsyncQueueConsumer] Driver "%s" does not implement AsyncQueueDriverInterface', $queueDriverName));
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
    protected function process(EndpointInterface $queueEndpoint, MessageInterface $message)
    {
        // If we used a wrapper to queue the message, that the handler doesn't understand, unwrap it
        if ($message instanceof MessageInterface && !($queueEndpoint->getHandler() instanceof QueueMessageHandlerInterface)) {
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
                $this->consumptionDuration = (microtime(true) - $start) * 1000;
                $this->getExceptionHandler()($exception, [
                    'headers' => $message->get('application_headers')->getNativeData(),
                    'body' => $message->getBody(),
                    'messageId' => $message->delivery_info['delivery_tag']]
                );
                return false;
            }

            parent::callback($endpoint)($queueMessage);
        };
    }
}
