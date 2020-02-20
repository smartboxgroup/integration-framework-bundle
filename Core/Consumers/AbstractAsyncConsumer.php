<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;

use PhpAmqpLib\Message\AMQPMessage;
use Smartbox\CoreBundle\Utils\Helper\DateTimeCreator;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesLogger;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Events\TimingEvent;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class AbstractConsumer.
 */
abstract class AbstractAsyncConsumer extends Service implements ConsumerInterface
{
    use IsStopableConsumer;
    use UsesLogger;
    use UsesSmartesbHelper;

    protected $sleep = true;

    /**
     * Initializes the consumer for a given endpoint.
     */
    abstract protected function initialize(EndpointInterface $endpoint);

    /**
     * @return mixed
     */
    abstract protected function cleanUp(EndpointInterface $endpoint);

    /**
     * After the execution of this method, it will be considered that the message was successfully handled,
     * therefore, if there was any problem, an exception must be thrown and not continue. This is important to ensure
     * the Message Delivery Guarantee.
     *
     * @param MessageInterface $message
     */
    protected function process(EndpointInterface $endpoint, AMQPMessage $message)
    {
        $endpoint->handle($message);
    }

    /**
     * This function is called to confirm that a message was successfully handled. Until this point, the message should
     * not be removed from the source Endpoint, this is very important to ensure the Message delivery guarantee.
     *
     * @param $message
     *
     * @return MessageInterface
     */
    abstract protected function confirmMessage(EndpointInterface $endpoint, $message);

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function consume(EndpointInterface $endpoint)
    {
        $this->initialize($endpoint);

        $callback = $this->callback($endpoint);
        $this->asyncConsume($endpoint, $callback);

        while (!$this->shouldStop() && $this->sleep = true) {
            try {
                $this->waitNoBlock();

                if ($this->sleep) {
                    usleep(250000);
                }
            } catch (\Exception $exception) {
                if (!$this->stop) {
                    throw $exception;
                }
            }
        }

        $this->cleanUp($endpoint);
    }

    public function callback(EndpointInterface $endpoint)
    {
        return function ($message) use ($endpoint) {
            $this->process($endpoint, $message);
            $this->confirmMessage($endpoint, $message);
            --$this->expirationCount;
            $this->sleep = false;
        };
    }

    abstract public function asyncConsume(EndpointInterface $endpoint, callable $callback);

    abstract public function wait();
    abstract public function waitNoBlock();

    /** {@inheritdoc} */
    public function getName()
    {
        $reflection = new \ReflectionClass(self::class);
        $name = $reflection->getShortName();

        return basename($name, 'Consumer');
    }

    /**
     * This function dispatchs a timing event with the amount of time it took to consume a message.
     *
     * @param $intervalMs int the timing interval that we would like to emanate
     *
     * @return mixed
     */
    protected function dispatchConsumerTimingEvent($intervalMs, MessageInterface $message)
    {
        $event = new TimingEvent(TimingEvent::CONSUMER_TIMING);
        $event->setIntervalMs($intervalMs);
        $event->setMessage($message);

        if (null !== ($dispatcher = $this->getEventDispatcher())) {
            $dispatcher->dispatch(TimingEvent::CONSUMER_TIMING, $event);
        }
    }

    /**
     * Log the moment a message was consumed.
     */
    protected function logConsumeMessage()
    {
        if ($this->logger) {
            $now = DateTimeCreator::getNowDateTime();

            $this->logger->info(
            'A message was consumed on {date}', [
            'date' => $now->format('Y-m-d H:i:s.u'),
            ]
        );
        }
    }
}
