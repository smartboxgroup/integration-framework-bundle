<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Handler\PhpAmqpHandler;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;

/**
 * Class PhpAmqpSignalConsumer
 * @package Smartbox\Integration\FrameworkBundle\Components\Queues
 */
class PhpAmqpSignalConsumer extends Service implements ConsumerInterface, LoggerAwareInterface
{
    use IsStopableConsumer;
    use UsesSmartesbHelper;
    use UsesSerializer;
    use LoggerAwareTrait;
    use UsesExceptionHandlerTrait;

    /**
     * Consumer identifier name
     */
    const CONSUMER_TAG = 'php-amqp-signal-consumer';

    /**
     * @var PhpAmqpLibConnectionManager
     */
    private $manager;

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
     * PhpAmqpSignalConsumer constructor.
     */
    public function __construct(PhpAmqpLibConnectionManager $manager, string $format = 'json')
    {
        parent::__construct();
        $this->manager = $manager;
        $this->format = $format;
        $this->driver = new PhpAmqpLibDriver($this->manager, $this->format);

        if (!extension_loaded('pcntl')) {
            throw new AMQPRuntimeException('Unable to process signals. Miss configuration.');
        }

        define('AMQP_WITHOUT_SIGNALS', false);

        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
    }

    /**
     * @param $signalHandler
     */
    public function signalHandler($signalNumber)
    {
        echo 'Handling signal: #' . $signalNumber . PHP_EOL;

        switch ($signalNumber) {
            case SIGTERM: // 15 : supervisor default stop
            case SIGQUIT: // 3  : kill -s QUIT
                $this->stopHard();
                break;
            case SIGINT:  // 2 : ctrl + c (manual stop)
                $this->stop();
                break;
            default:
                break;
        }
    }

    /**
     * Stop the consumer not gracefully closing the connection
     */
    public function stopHard()
    {
        echo 'Stopping consumer by closing connection.' . PHP_EOL;
        $this->manager->shutdown();
    }

    /**
     * Tell the server you are going to stop consuming
     * It will finish up the last message and not send you any more
     */
    public function stop()
    {
        try {
            echo 'Stopping consumer by cancel command.' . PHP_EOL;
            if ($this->manager->isConnected()) {
                $this->handler->stopConsume(self::CONSUMER_TAG);
            }
            return;
        } catch (Exception $exception) {
            return;
        }
    }

    /**
     * Start the consume flow
     * @param EndpointInterface $endpoint
     * @return bool
     * @throws Exception
     */
    public function consume(EndpointInterface $endpoint)
    {
        try {
            $this->handler = new PhpAmqpHandler($endpoint, (int) $this->expirationCount, $this->format, $this->serializer);
            $this->handler->setExceptionHandler($this->getExceptionHandler());

            if ($this->smartesbHelper) {
                $this->handler->setSmartesbHelper($this->smartesbHelper);
            }
            if ($this->logger) {
                $this->handler->setLogger($this->logger);
            }

            $channel = $this->driver->declareChannel();
            if ($channel instanceof AMQPChannel) {
                $this->handler->setChannel($channel);
            }
            $queueName = $this->getQueueName($endpoint);
            $this->driver->declareQueue($queueName);
            $this->handler->consume(self::CONSUMER_TAG, $channel, $queueName);
        } catch (Exception $exception) {
            echo $exception->getMessage();
        } finally {
            $this->manager->shutdown();
        }

        return true;
    }

    /**
     * Returns the consumer name
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::CONSUMER_TAG;
    }

    /**
     * Returns the queue name properly treated with queue prefix
     * @param EndpointInterface $endpoint
     * @return string
     */
    protected function getQueueName(EndpointInterface $endpoint): string
    {
        $options = $endpoint->getOptions();

        return "{$options[QueueProtocol::OPTION_PREFIX]}{$options[QueueProtocol::OPTION_QUEUE_NAME]}";
    }

}