<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Smartbox\Integration\FrameworkBundle\Service;

/**
 * Class AbstractConsumer.
 */
abstract class AbstractConsumer extends Service implements ConsumerInterface
{
    use UsesSmartesbHelper;

    /** @var bool */
    protected $stop = false;

    /** @var int */
    protected $expirationCount = -1;

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->stop = true;
    }

    /**
     * {@inheritdoc}
     */
    public function setExpirationCount($count)
    {
        $this->expirationCount = $count;
    }

    /**
     * Checks if it should stop at the current iteration.
     *
     * @return bool
     */
    protected function shouldStop()
    {
        return $this->stop || $this->expirationCount == 0;
    }

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
                    --$this->expirationCount;

                    $this->process($endpoint, $message);

                    $this->confirmMessage($endpoint, $message);
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
}
