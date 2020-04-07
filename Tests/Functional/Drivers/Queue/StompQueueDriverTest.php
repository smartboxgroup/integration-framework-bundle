<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * @internal
 */
class StompQueueDriverTest extends AbstractQueueDriverTest
{
    /**
     * @dataProvider getMessages
     */
    public function testShouldSelect(MessageInterface $msg)
    {
        $queueMessage = $this->createQueueMessage($msg);
        $queueMessage->addHeader('test_header', '12345');
        $this->driver->send($this->queueName, serialize($queueMessage), $queueMessage->getHeaders());

        $this->driver->subscribe($this->queueName, 'test_header = 12345');

        $this->assertNotNull($this->driver->receive(), 'Message should be received');
        $this->driver->ack();
        $this->driver->unSubscribe();
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver(): QueueDriverInterface
    {
        return $this->getContainer()->get('smartesb.drivers.queue.main');
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testAfterNackShouldBeRetried($msg)
    {
        $queueMessage = $this->createQueueMessage($msg);

        $this->driver->subscribe($this->queueName);
        $this->driver->send($this->queueName, serialize($queueMessage), $queueMessage->getHeaders());

        $this->driver->receive();
        $this->driver->nack();

        \sleep(1);

        $msgOut = $this->driver->receive();

        $this->assertNotNull($msgOut, 'Message should be available');
        $this->driver->ack();

        $this->driver->unSubscribe();
    }

    /**
     * @dataProvider getMessages
     */
    public function testAfterTtlShouldDiscard(MessageInterface $msg)
    {
        $queueMessage = $this->createQueueMessage($msg);
        $queueMessage->setTTL(1);
        $this->driver->subscribe($this->queueName);
        $this->driver->send($this->queueName, serialize($queueMessage), $queueMessage->getHeaders());
        $this->driver->unSubscribe();

        // After > 2 seconds, the message is not there
        \sleep(2);
        $this->driver->subscribe($this->queueName);
        $msgOut = $this->driver->receive();
        if ($msgOut) {
            $this->driver->ack();
        }
        $this->assertFalse($msgOut);

        $this->driver->disconnect();
    }

    public function testItShouldGetManyMessagesAtOnceUsingPrefetchSize()
    {
        // populate the queue with some messages
        $numMessages = 3;
        $sentMessages = [];
        for ($i = 0; $i < $numMessages; ++$i) {
            $queueMessage = $this->createQueueMessage($this->createSimpleEntity('item'.$i));
            $this->driver->send($this->queueName, serialize($queueMessage), $queueMessage->getHeaders());
            $sentMessages[] = $queueMessage;
        }

        $this->driver->subscribe($this->queueName);

        $receivedMessages = [];
        while ($receivedMessage = $this->driver->receive()) {
            $this->driver->ack();
            $receivedMessages[] = unserialize($receivedMessage->getBody());
        }

        $this->assertSame(
            \array_map('self::mapTitle', $sentMessages),
            \array_map('self::mapTitle', $receivedMessages)
        );
    }

    public function testPrefetchSizeShouldWorkAlsoWhenNotReceivingAllThePrefetchedMessagesAtOnce()
    {
        // populate the queue
        $numMessagesSent = 10;
        $sentMessages = [];
        for ($i = 0; $i < $numMessagesSent; ++$i) {
            $queueMessage = $this->createQueueMessage($this->createSimpleEntity('item'.$i));
            $this->driver->send($this->queueName, serialize($queueMessage), $queueMessage->getHeaders());
            $sentMessages[] = $queueMessage;
        }

        $this->driver->subscribe($this->queueName);

        $receivedMessages = [];

        // receive just one message
        $receivedMessages[] = unserialize($this->driver->receive()->getBody());
        $this->driver->ack();

        // send an additional message to the queue
        $queueMessage = $this->createQueueMessage($this->createSimpleEntity('item'.$numMessagesSent));
        $this->driver->send($this->queueName, serialize($queueMessage), $queueMessage->getHeaders());
        $sentMessages[] = $queueMessage;

        // receives all the remaining messages in the queue
        while ($receivedMessage = $this->driver->receive()) {
            $this->driver->ack();
            $receivedMessages[] = unserialize($receivedMessage->getBody());
        }

        $this->assertSame(
            \array_map('self::mapTitle', $sentMessages),
            \array_map('self::mapTitle', $receivedMessages)
        );
    }
}
