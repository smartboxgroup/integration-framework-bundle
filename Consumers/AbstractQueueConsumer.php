<?php
namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use CentralDesktop\Stomp\Exception as StompException;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessageInterface;

/**
 * Class AbstractQueueConsumer
 *
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
abstract class AbstractQueueConsumer implements QueueConsumerInterface {

    /** @var bool */
    protected $stop = false;

    /** @var  QueueDriverInterface */
    protected $queueDriver;

    /** @var int  */
    protected $expirationCount = -1;

    /**
     * @return QueueDriverInterface
     */
    public function getQueueDriver()
    {
        return $this->queueDriver;
    }

    /**
     * @param QueueDriverInterface $queueDriver
     */
    public function setQueueDriver($queueDriver)
    {
        $this->queueDriver = $queueDriver;
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
     * @return int
     */
    public function getExpirationCount(){
        return $this->expirationCount;
    }

    /**
     * Checks if it should stop at the current iteration
     *
     * @return bool
     */
    public function shouldStop(){
        return $this->stop || $this->expirationCount == 0;
    }

    /**
     * @param QueueMessageInterface $message
     *
     * @return mixed
     */
    protected abstract function process(QueueMessageInterface $message);

    /**
     * {@inheritDoc}
     */
    public function consume($queue){
        $this->getQueueDriver()->connect();
        $this->getQueueDriver()->subscribe($queue);

        while (!$this->shouldStop()) {
            try {
                // Receive
                $queueMessage = $this->getQueueDriver()->receive();

                // Process
                if($queueMessage){
                    $this->expirationCount--;

                    // Handle
                    $this->process($queueMessage);

                    // Ack
                    $this->getQueueDriver()->ack();
                }

            } catch (StompException $ex) {
                if (!$this->stop){
                    throw $ex;
                }
            }
        }

        $this->getQueueDriver()->unSubscribe();
    }
}
