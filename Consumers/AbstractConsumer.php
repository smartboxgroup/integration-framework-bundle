<?php
namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Handlers\HandlerInterface;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSmartesbHelper;

abstract class AbstractConsumer implements ConsumerInterface{
    use UsesSmartesbHelper;

    /** @var  HandlerInterface */
    protected $handler;

    /** @var bool */
    protected $stop = false;

    /** @var int  */
    protected $expirationCount = -1;

    /**
     * @param HandlerInterface $handler
     */
    public function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Signal the consumer to stop before processing the next message
     */
    public function stop()
    {
        $this->stop = true;
    }

    /**
     * @param $count
     */
    public function setExpirationCount($count){
        $this->expirationCount = $count;
    }

    /**
     * Checks if it should stop at the current iteration
     *
     * @return bool
     */
    protected function shouldStop(){
        return $this->stop || $this->expirationCount == 0;
    }

    /**
     * @param EndpointInterface $endpoint
     * Initializes the consumer for this endpoint
     */
    protected abstract function initialize(EndpointInterface $endpoint);

    /**
     * @param EndpointInterface $endpoint
     * @return mixed
     */
    protected abstract function cleanUp(EndpointInterface $endpoint);

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
     * @param EndpointInterface $endpoint
     * @return MessageInterface | null
     */
    protected abstract function readMessage(EndpointInterface $endpoint);

    /**
     * After the execution of this method, it will be considered that the message was successfully handled,
     * therefore, if there was any problem, an exception must be thrown and not continue. This is important to ensure
     * the Message Delivery Guarantee.
     *
     * @param EndpointInterface $endpoint
     * @param MessageInterface $message
     */
    protected function process(EndpointInterface $endpoint, MessageInterface $message){
        $this->handler->handle($message, $endpoint);
    }

    /**
     * This function is called to confirm that a message was successfully handled. Until this point, the message should
     * not be removed from the source Endpoint, this is very important to ensure the Message delivery guarantee.
     *
     * @return MessageInterface
     */
    protected abstract function confirmMessage(EndpointInterface $endpoint, MessageInterface $message);

    /**
     * @param EndpointInterface $endpoint
     * @return bool|void
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
                if($message){
                    $this->expirationCount--;

                    $this->process($endpoint,$message);

                    $this->confirmMessage($endpoint,$message);
                }

            } catch (\Exception $ex) {
                if (!$this->stop){
                    throw $ex;
                }
            }
        }

        $this->cleanUp($endpoint);
    }
}