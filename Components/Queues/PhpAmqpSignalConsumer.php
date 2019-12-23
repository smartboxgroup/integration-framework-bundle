<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Handler\AmqpQueueHandler;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Handler\PhpAmqpHandler;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\IsStopableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Exceptions\Handler\UsesExceptionHandlerTrait;
use Smartbox\Integration\FrameworkBundle\Service;

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
     *
     */
    const CONSUMER_TAG = 'php-amqp-signal-consumer';

    /**
     * @var boolean
     */
    private $restart;

    /**
     * @var PhpAmqpLibQueueManager
     */
    private $manager;

    /**
     * @var string
     */
    private $format;

    private $shouldStop;

    /**
     * @var PhpAmqpHandler
     */
    private $handler;

    /**
     * PhpAmqpSignalConsumer constructor.
     */
    public function __construct($manager, string $format = 'json')
    {
        $this->manager = $manager;
        $this->format = $format;
        $this->shouldStop = false;

        if (extension_loaded('pcntl')) {
            define('AMQP_WITHOUT_SIGNALS', false);

            pcntl_signal(SIGTERM, [$this, 'signalHandler']);
            pcntl_signal(SIGHUP, [$this, 'signalHandler']);
            pcntl_signal(SIGINT, [$this, 'signalHandler']);
            pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
            pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
            pcntl_signal(SIGUSR2, [$this, 'signalHandler']);
            pcntl_signal(SIGALRM, [$this, 'alarmHandler']);
        } else {
            throw new AMQPRuntimeException('Unable to process signals. Miss configuration.');
        }
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
            case SIGHUP: // 1 : kill -s HUP
                $this->restart();
                break;
            case SIGUSR1: // 10 : kill -s USR1
                pcntl_alarm(1);
                break;
            case SIGUSR2: // 12 : kill -s USR2
                pcntl_alarm(10);
                break;
            default:
                break;
        }
    }

    /**
     * @return bool
     */
    public function start()
    {
        if ($this->restart) {
            echo 'Restarting consumer.' . PHP_EOL;
        } else {
            echo 'Starting consumer.' . PHP_EOL;
        }
    }

    /**
     *
     */
    public function restart()
    {
        $this->stopSoft();
        $this->restart = true;
    }

    /**
     *
     */
    public function stopHard()
    {
        echo 'Stopping consumer by closing connection.' . PHP_EOL;
        $this->manager->disconnect();
    }

    /**
     *
     */
    public function stopSoft()
    {
        echo 'Stopping consumer by closing channel.' . PHP_EOL;
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
        } catch (\Exception $exception) {
            return;
        }
    }

    /**
     * @return bool
     */
    public function shouldRestart()
    {
        return $this->restart;
    }

    /**
     * @param $signalNumber
     */
    public function alarmHandler($signalNumber)
    {
        echo 'Handling the alarm: #' . $signalNumber . PHP_EOL;
        echo memory_get_usage(true) . PHP_EOL;

        return;
    }

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

            $channel = $this->manager->declareChannel();
            if ($channel instanceof AMQPChannel) {
                $this->handler->setChannel($channel);
            }
            $queueName = $this->getQueueName($endpoint);
            $this->manager->declareQueue($queueName);
            $this->handler->consume(self::CONSUMER_TAG, $channel, $queueName);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
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
        return self::CONSUMER_TAG;
    }

    /**
     * @param EndpointInterface $endpoint
     * @return string
     */
    protected function getQueueName(EndpointInterface $endpoint): string
    {
        $options = $endpoint->getOptions();

        return "{$options[QueueProtocol::OPTION_PREFIX]}{$options[QueueProtocol::OPTION_QUEUE_NAME]}";
    }

    public function shouldStop(int $messagesReady)
    {
        return !boolval($messagesReady);
    }
}