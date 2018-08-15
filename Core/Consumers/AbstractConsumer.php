<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesLogger;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Events\TimingEvent;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class AbstractConsumer.
 */
abstract class AbstractConsumer extends Service implements ConsumerInterface
{
    use IsStopableConsumer;
    use UsesLogger;
    use UsesSmartesbHelper;

    /**
     * Initializes the consumer for a given endpoint.
     *
     * @param EndpointInterface $endpoint
     */
    abstract protected function initialize(EndpointInterface $endpoint);

    /**
     * @param EndpointInterface $endpoint
     *
     * @return mixed
     */
    abstract protected function cleanUp(EndpointInterface $endpoint);

    /**
     * This function is called to read and usually lock a message from the source Endpoint. The message should not be
     * removed from the source Endpoint, this is important to ensure the Message Delivery Guarantee.
     *
     * Additionally, if the source Endpoint can be consumed by competing consumers, the consumption of this message
     * should be locked in the source Endpoint, to avoid processing a message twice.
     *
     * If it was not possible to read a message, or there are no more messages in the Endpoint right now, this method
     * must return null to indicate that.
     *
     * @return MessageInterface
     *
     * @param EndpointInterface $endpoint
     *
     * @return MessageInterface | null
     */
    abstract protected function readMessage(EndpointInterface $endpoint);

    /**
     * After the execution of this method, it will be considered that the message was successfully handled,
     * therefore, if there was any problem, an exception must be thrown and not continue. This is important to ensure
     * the Message Delivery Guarantee.
     *
     * @param EndpointInterface $endpoint
     * @param MessageInterface  $message
     */
    protected function process(EndpointInterface $endpoint, MessageInterface $message)
    {
        $endpoint->handle($message);
    }

    /**
     * This function is called to confirm that a message was successfully handled. Until this point, the message should
     * not be removed from the source Endpoint, this is very important to ensure the Message delivery guarantee.
     *
     * @param EndpointInterface $endpoint
     * @param MessageInterface  $message
     *
     * @return MessageInterface
     */
    abstract protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message);

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function consume(EndpointInterface $endpoint)
    {
        $this->initialize($endpoint);

        while (!$this->shouldStop()) {
            try {
                // Receive
                $message = $this->readMessage($endpoint);

                // Process
                if ($message) {
                    $startConsumeTime = microtime(true);

                    --$this->expirationCount;

                    $this->process($endpoint, $message);

                    if ($this->logger) {
                        // Please refer to http://php.net/manual/en/datetime.createfromformat.php#119362 to understand why we number_format
                        $microTime = number_format(microtime(true), 6, '.', '');

                        $now = \DateTime::createFromFormat('U.u', $microTime);
                        $this->logger->info('A message was consumed on '.$now->format('Y-m-d H:i:s.u'));
                    }

                    $this->confirmMessage($endpoint, $message);

                    $endConsumeTime = microtime(true);
                    $this->dispatchConsumerTimingEvent((int) (($endConsumeTime - $startConsumeTime) * 1000), $message);
                }
            } catch (\Exception $ex) {
                if (!$this->stop) {
                    throw $ex;
                }
            }
        }

        $this->cleanUp($endpoint);
    }

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
     * @param MessageInterface $message
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
}
