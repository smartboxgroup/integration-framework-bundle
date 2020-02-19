<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Handler\PhpAmqpHandler;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;
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
    private $format;

    /**
     * @var PhpAmqpHandler
     */
    private $handler;

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
        $this->handler = new PhpAmqpHandler($endpoint, (int)$this->expirationCount, $this->driver->getFormat(), $this->serializer);
        $this->handler->setExceptionHandler($this->getExceptionHandler());

        if ($this->smartesbHelper) {
            $this->handler->setSmartesbHelper($this->smartesbHelper);
        }

        if (!$this->driver->isConnected()) {
            $this->driver->connect();
        }
    }

    public function callback(EndpointInterface $endpoint)
    {
        return function(AMQPMessage $message) use($endpoint) {
            try {
                $queueMessage = $this->serializer->deserialize($message->getBody(), SerializableInterface::class, $this->format);
            } catch (\Exception $exception) {
                $this->getExceptionHandler()($exception, ['headers' => $message->getHeaders(), 'body' => $message->getBody()]);
            }

            $this->process($endpoint, $queueMessage);
            $this->confirmMessage($endpoint, $message);
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
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        // TODO: Implement confirmMessage() method.
    }

    public function asyncConsume(EndpointInterface $endpoint, callable $callback)
    {
        $channel = $this->driver->declareChannel();
        if ($channel instanceof AMQPChannel) {
            $this->handler->setChannel($channel);
        }
        $queueName = $this->getQueueName($endpoint);
        $this->driver->declareQueue($queueName);
        $this->driver->consume($this->getName(), $channel, $queueName, $callback);
    }
}