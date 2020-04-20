<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;

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
abstract class AbstractConsumer extends Service implements ConsumerInterface
{
    use IsStopableConsumer;
    use UsesLogger;
    use UsesSmartesbHelper;

    /**
     * Holds the amount of time it took to consume a message.
     *
     * @var int
     */
    protected $consumptionDuration = 0;

    /**
     * Initializes the consumer for a given endpoint.
     */
    abstract protected function initialize(EndpointInterface $endpoint);

    /**
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
     * @return MessageInterface | null
     */
    abstract protected function readMessage(EndpointInterface $endpoint);

    /**
     * After the execution of this method, it will be considered that the message was successfully handled,
     * therefore, if there was any problem, an exception must be thrown and not continue. This is important to ensure
     * the Message Delivery Guarantee.
     */
    protected function process(EndpointInterface $endpoint, MessageInterface $message)
    {
        $endpoint->handle($message);
    }

    /**
     * This function is called to confirm that a message was successfully handled. Until this point, the message should
     * not be removed from the source Endpoint, this is very important to ensure the Message delivery guarantee.
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
                $this->consumptionDuration = 0;

                // Receive
                $message = $this->readMessage($endpoint);

                // Process
                if ($message) {
                    $startConsumeTime = microtime(true);

                    --$this->expirationCount;

                    $this->process($endpoint, $message);

                    $this->logConsumeMessage();

                    $this->confirmMessage($endpoint, $message);

                    $this->consumptionDuration += (int) ((microtime(true) - $startConsumeTime) * 1000);
                    $this->dispatchConsumerTimingEvent($message);
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
     * Dispatches a timing event with the amount of time it took to process a message.
     */
    protected function dispatchConsumerTimingEvent(MessageInterface $message)
    {
        $event = new TimingEvent(TimingEvent::CONSUMER_TIMING);
        $event->setIntervalMs($this->consumptionDuration);
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
                'A message was consumed on {date}',
                ['date' => $now->format('Y-m-d H:i:s.u')]
            );
        }
    }
}
