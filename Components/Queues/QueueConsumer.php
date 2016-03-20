<?php
namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * Class QueueConsumer
 *
 * @package Smartbox\Integration\FrameworkBundle\Core\Consumers
 */
class QueueConsumer extends AbstractConsumer implements ConsumerInterface {
    /**
     * {@inheritdoc}
     */
    protected function initialize(EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();
        $queuePrefix = $options[QueueProtocol::OPTION_PREFIX];
        $queueName = $options[QueueProtocol::OPTION_QUEUE_NAME];
        $queuePath = $queuePrefix.$queueName;

        $driver = $this->getQueueDriver($endpoint);
        $driver->connect();
        $driver->subscribe($queuePath);

    }

    /**
     * @param EndpointInterface $endpoint
     * @return \Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface
     */
    protected function getQueueDriver(EndpointInterface $endpoint){
        $options = $endpoint->getOptions();
        $queueDriverName = $options[QueueProtocol::OPTION_QUEUE_DRIVER];
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
        if($message instanceof QueueMessageInterface && !($endpoint->getHandler() instanceof QueueMessageHandlerInterface)){
            $endpoint = $this->helper->getEndpointFactory()->createEndpoint($message->getDestinationURI());
            $endpoint->handle($message->getBody());
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
