<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Handler\AmqpQueueHandler;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Service;

class AsyncQueueConsumer extends Service implements ConsumerInterface, LoggerAwareInterface
{
    use IsStopableConsumer;
    use UsesSmartesbHelper;
    use UsesSerializer;
    use LoggerAwareTrait;

    /**
     * @var QueueManager
     */
    private $manager;

    /**
     * @var string
     */
    private $format;

    /**
     * AsyncQueueConsumer constructor.
     *
     * @param QueueManager $manager
     * @param string       $format
     */
    public function __construct(QueueManager $manager, string $format = 'json')
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

        if ($this->smartesbHelper) {
            $handler->setSmartesbHelper($this->smartesbHelper);
        }
        if ($this->logger) {
            $handler->setLogger($this->logger);
        }

        try {
            $this->manager->getQueue($this->getQueueName($endpoint))->consume($handler);
        } finally {
            $this->manager->disconnect();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'async_queue_consumer';
    }

    protected function getQueueName(EndpointInterface $endpoint): string
    {
        $options = $endpoint->getOptions();

        return "{$options[QueueProtocol::OPTION_PREFIX]}{$options[QueueProtocol::OPTION_QUEUE_NAME]}";
    }
}
