<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Stomp\Exception\ConnectionException;

/**
 * @internal
 *
 * @group stomp
 */
class StompQueueDriverTest extends AbstractQueueDriverTest
{
    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     * @group stomp
     */
    public function testShouldSelect(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);

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
     * Test that in case we catch an exception during send(), the connection gets dropped to avoid interfering
     * with the following messages.
     *
     * @runInSeparateProcess
     */
    public function testConnectionGetsDroppedOnException()
    {
        $this->expectException(ConnectionException::class);
        $this->overrideFwrite();

        $msg = $this->createQueueMessage($this->createSimpleEntity());
        $this->driver->send($msg);

        $this->assertFalse($this->driver->isConnected(), 'Driver still connected after exception.');
    }

    /*
     * Override native fwrite function. Test should be run in separate process (@runInSeparateProcess) to avoid
     * overriding it for every other test.
     */
    protected function overrideFwrite()
    {
        $function = '
                namespace Stomp\Network;

                function fwrite()
                {
                    return 0;
                }
            ';

        eval($function);
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testAfterNackShouldBeRetried($msg)
    {
        $this->driver->subscribe($this->queueName);
        $this->driver->send($this->createQueueMessage($msg));

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
     *
     * @param MessageInterface $msg
     */
    public function testAfterTtlShouldDiscard(MessageInterface $msg)
    {
        $queueMessage = $this->createQueueMessage($msg);
        $queueMessage->setTTL(1);
        $this->driver->subscribe($this->queueName);
        $this->driver->send($queueMessage);
        $this->driver->unSubscribe();

        // After > 2 seconds, the message is not there
        \sleep(2);
        $this->driver->subscribe($this->queueName);
        $msgOut = $this->driver->receive();
        if ($msgOut) {
            $this->driver->ack();
        }
        $this->assertNull($msgOut);

        $this->driver->disconnect();
    }

    public function testItShouldGetManyMessagesAtOnceUsingPrefetchSize()
    {
        // populate the queue with some messages
        $numMessages = 3;
        $sentMessages = [];
        for ($i = 0; $i < $numMessages; ++$i) {
            $message = $this->createQueueMessage($this->createSimpleEntity('item'.$i));
            $this->driver->send($message);
            $sentMessages[] = $message;
        }

        $this->driver->subscribe($this->queueName, null, $numMessages);

        $receivedMessages = [];
        while (null !== ($receivedMessage = $this->driver->receive())) {
            $this->driver->ack();
            $receivedMessages[] = $receivedMessage;
        }

        $this->assertSame(
            \array_map('self::mapTitle', $sentMessages),
            \array_map('self::mapTitle', $receivedMessages)
        );
    }

    public function testPrefetchSizeShouldWorkAlsoWhenNotReceivingAllThePrefetchedMessagesAtOnce()
    {
        // populate the queue
        $prefetchSize = 3;
        $numMessagesSent = 10;
        $sentMessages = [];
        for ($i = 0; $i < $numMessagesSent; ++$i) {
            $message = $this->createQueueMessage($this->createSimpleEntity('item'.$i));
            $this->driver->send($message);
            $sentMessages[] = $message;
        }

        $this->driver->subscribe($this->queueName, null, $prefetchSize);

        $receivedMessages = [];

        // receive just one message
        $receivedMessages[] = $this->driver->receive();
        $this->driver->ack();

        // send an additional message to the queue
        $message = $this->createQueueMessage($this->createSimpleEntity('item'.$numMessagesSent));
        $this->driver->send($message);
        $sentMessages[] = $message;

        // receives all the remaining messages in the queue
        while (null !== ($receivedMessage = $this->driver->receive())) {
            $this->driver->ack();
            $receivedMessages[] = $receivedMessage;
        }

        $this->assertSame(
            \array_map('self::mapTitle', $sentMessages),
            \array_map('self::mapTitle', $receivedMessages)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function createConsumer(): ConsumerInterface
    {
        return static::$kernel->getContainer()->get('smartesb.consumers.queue');
    }

}
