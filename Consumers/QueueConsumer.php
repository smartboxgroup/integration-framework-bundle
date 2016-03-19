<?php
namespace Smartbox\Integration\FrameworkBundle\Consumers;

use Smartbox\Integration\FrameworkBundle\Drivers\Queue\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Endpoints\QueueEndpoint;
use Smartbox\Integration\FrameworkBundle\Handlers\QueueMessageHandlerInterface;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Messages\Queues\QueueMessageInterface;
/**
 * Class QueueConsumer
 *
 * @package Smartbox\Integration\FrameworkBundle\Consumers
 */
class QueueConsumer extends AbstractConsumer implements ConsumerInterface {
    /**
     * {@inheritdoc}
     */
    protected function initialize(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $queuePrefix = $options[QueueEndpoint::OPTION_PREFIX];
        $queueName = $options[QueueEndpoint::OPTION_QUEUE_NAME];
        $queuePath = $queuePrefix.$queueName;

        $driver = $this->getQueueDriver($endpoint);
        $driver->connect();
        $driver->subscribe($queuePath);

    }

    /**
     * @param EndpointInterface $endpoint
     * @return QueueDriverInterface
     */
    protected function getQueueDriver(EndpointInterface $endpoint){
        $options = $endpoint->getOptions();
        $queueDriverName = $options[QueueEndpoint::OPTION_QUEUE_DRIVER];
        $queueDriver = $this->helper->getQueueDriver($queueDriverName);

        if($queueDriver instanceof QueueDriverInterface){
            return $queueDriver;
        }

        throw new \RuntimeException("Error in QueueConsumer, the driver with name $queueDriverName does not implement the interface QueueDriverInterface");
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
        $driver = $this->getQueueDriver($endpoint);
        $driver->unSubscribe();
    }

    /**
     * {@inheritdoc}
     */
    protected function readMessage(EndpointInterface $endpoint)
    {
        $driver = $this->getQueueDriver($endpoint);
        return $driver->receive();
    }

    /**
     * {@inheritdoc}
     */
    protected function process(EndpointInterface $endpoint, MessageInterface $message){
        // If we used a wrapper to queue the message, that the handler doesn't understand, unwrap it
        if($message instanceof QueueMessageInterface && !($this->handler instanceof QueueMessageHandlerInterface)){
            $endpoint = $this->helper->getEndpointFactory()->createEndpoint($message->getDestinationURI());
            $this->handler->handle($message->getBody(),$endpoint);
        }else {
            parent::process($endpoint,$message);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        $driver = $this->getQueueDriver($endpoint);
        $driver->ack();
    }
}
