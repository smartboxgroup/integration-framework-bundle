<?php
namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Handlers\HandlerInterface;
use CentralDesktop\Stomp\Exception as StompException;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessage;

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

    public function shouldStop(){
        return $this->stop || $this->expirationCount == 0;
    }

    protected abstract function process(QueueMessage $message);

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