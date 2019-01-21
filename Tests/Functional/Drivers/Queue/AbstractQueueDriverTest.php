<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PurgeableQueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;

/**
 * Base class for Queue drivers test.
 */
abstract class AbstractQueueDriverTest extends BaseTestCase
{
    const QUEUE_PREFIX = '/test/';

    protected $queueName;

    /**
     * @var QueueDriverInterface
     */
    protected $driver;

    protected function setUp()
    {
        parent::setUp();
        $this->driver = $this->createDriver();
        $this->queueName = static::QUEUE_PREFIX.(new \ReflectionClass($this->driver))->getShortName().md5(random_bytes(10));
    }

    protected function tearDown()
    {
        if ($this->driver instanceof PurgeableQueueDriverInterface) {
            $this->driver->purge($this->queueName);
        }

        $this->driver->disconnect();
        $this->driver = null;
        parent::tearDown();
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testSendShouldNotChangeMessage(MessageInterface $msg)
    {
        $clone = clone $msg;

        $this->driver->send($this->createQueueMessage($msg));

        $this->assertSame(serialize($clone), serialize($msg));
        $this->driver->send($this->createQueueMessage($msg));
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testShouldSendReceiveAndAckOnce(MessageInterface $msg)
    {
        $messageToSend = $this->createQueueMessage($msg);
        $this->driver->subscribe($this->queueName);
        $this->driver->send($messageToSend);

        $this->assertInstanceOf(MessageInterface::class, $this->driver->receive(5));

        $this->driver->ack();

        \sleep(1);

        $msgOut = $this->driver->receive();

        $this->assertNull($msgOut);

        $this->driver->unSubscribe();
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
//        $this->assertSame($msg, $msgOut->getBody());
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
        $queueMessage->setTTL(2);
        $this->driver->send($queueMessage);
        $this->driver->subscribe($this->queueName);

        // After 1 second, the message is still there
        \sleep(1);
        $this->assertNotNull($this->driver->receive(), 'Should receive a message');

        $this->driver->nack();
        $this->driver->unSubscribe();

        // After > 2 seconds, the message is not there
        \sleep(3);
        $this->driver->subscribe($this->queueName);
        $msgOut = $this->driver->receive();
        if ($msgOut) {
            $this->driver->nack();
        }
        $this->assertNull($msgOut);

        $this->driver->unSubscribe();
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

    public function getMessages()
    {
        $simple = $this->createSimpleEntity();

        $item = new Message(new SerializableArray([$simple, $simple]));

        $x = new EntityX(1);
        $x1 = new Message($x);

        $complex = new Message(new SerializableArray(['x' => $x1, 'item' => $item, 'item2' => $item, 'item3' => $item]));

        $complex->setHeader('tracking-test-id', \uniqid());
        $x1->setHeader('tracking-test-id', \uniqid());
        $item->setHeader('tracking-test-id', \uniqid());

        return [
            [$complex],
        ];
    }

    public static function mapTitle(QueueMessage $message)
    {
        /** @var Message $wrappedMessage */
        $wrappedMessage = $message->getBody();
        /** @var TestEntity $item */
        $item = $wrappedMessage->getBody();

        return $item->getTitle();
    }

    /**
     * Create an instance of the queue driver to be tested.
     *
     * @return QueueDriverInterface
     */
    abstract protected function createDriver(): QueueDriverInterface;

    /**
     * @param $message
     *
     * @return QueueMessage
     */
    protected function createQueueMessage($message)
    {
        $msg = $this->driver->createQueueMessage();
        $msg->setPersistent(false);
        $msg->setBody($message);
        $msg->setQueue($this->queueName);

        return $msg;
    }

    protected function createSimpleEntity($title = 'item', $description = 'a simple item')
    {
        $entity = new TestEntity();
        $entity->setDescription($description);
        $entity->setTitle($title);
        $entity->setNote('Note here');

        return new Message($entity);
    }
}
