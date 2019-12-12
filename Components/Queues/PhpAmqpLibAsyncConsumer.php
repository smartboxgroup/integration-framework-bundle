<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use PhpAmqpLib\Exception\AMQPRuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Handler\AmqpQueueHandler;
use Smartbox\Integration\FrameworkBundle\Components\Queues\PhpAmqpLibQueueManager;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;

class PhpAmqpLibAsyncConsumer extends Service implements ConsumerInterface, LoggerAwareInterface
{
    use IsStopableConsumer;
    use UsesSmartesbHelper;
    use UsesSerializer;
    use LoggerAwareTrait;
    use UsesExceptionHandlerTrait;

    const CONSUMER_TAG = 'php_amqp_lib_async_queue_consumer';

    /**
     * @var PhpAmqpLibQueueManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $format;

    /**
     * AsyncQueueConsumer constructor.
     *
     * @param QueueManager $manager
     * @param string       $format
     */
    public function __construct($manager, string $format = 'json')
    {
        parent::__construct();
        $this->manager = $manager;
        $this->format = $format;
    }

    /**
     * Consumes messages from the given $endpoint until either the expirationCount reaches 0 or ::stop() is called.
     *
     * @param EndpointInterface $endpoint
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function consume(EndpointInterface $endpoint)
    {
        $handler = new AmqpQueueHandler($endpoint, (int) $this->expirationCount, $this->format, $this->serializer);
        $handler->setExceptionHandler($this->getExceptionHandler());

        if ($this->smartesbHelper) {
            $handler->setSmartesbHelper($this->smartesbHelper);
        }
        if ($this->logger) {
            $handler->setLogger($this->logger);
        }

        try {
            $message = $this->manager->consume($this->getQueueName($endpoint));
        } finally {
            $this->manager->shutdown();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'php_amqp_lib_async_queue_consumer';
    }

    protected function getQueueName(EndpointInterface $endpoint): string
    {
        $options = $endpoint->getOptions();

        return "{$options[QueueProtocol::OPTION_PREFIX]}{$options[QueueProtocol::OPTION_QUEUE_NAME]}";
    }
}