<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\StompQueueDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;

/**
 * Class StompProducerTest.
 */
class StompQueueDriverTest extends BaseTestCase
{
    const QUEUE_PREFIX = '/test/';

    private $queueName;
    private static $testIndex = 0;

    /** @var StompQueueDriver */
    protected $driver;

    public function setUp()
    {
        parent::setUp();
        $this->driver = $this->createDriver();
        ++self::$testIndex;
        $this->queueName = self::QUEUE_PREFIX.(new \ReflectionClass($this))->getShortName().self::$testIndex;
    }

    public function tearDown()
    {
        $this->driver->disconnect();
        $this->driver = null;
        parent::tearDown();
    }

    /**
     * @return StompQueueDriver
     */
    protected function createDriver()
    {
        $host = $this->getContainer()->getParameter('hostname');
        $username = $this->getContainer()->getParameter('rabbitmq.username');
        $password = $this->getContainer()->getParameter('rabbitmq.password');
        
        /* @var StompQueueDriver $processor */
        $driver = new StompQueueDriver();
        $driver->setMessageFactory($this->getContainer()->get('smartesb.message_factory'));
        $driver->setSerializer($this->getContainer()->get('serializer'));
        $driver->configure('tcp://'.$host, $username, $password);
        $driver->connect();

        return $driver;
    }

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

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testSendShouldNotChangeMessage(MessageInterface $msg)
    {
        $clone = unserialize(serialize($msg));

        $this->driver->send($this->createQueueMessage($msg));

        $this->assertEquals($clone, $msg);
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testShouldSendReceiveAndAckOnce(MessageInterface $msg)
    {
        $messageToSend = $this->createQueueMessage($msg);
        $this->driver->send($messageToSend);

        $this->driver->subscribe($this->queueName);

        $msgOut = $this->driver->receive();

        $this->assertEquals($msg, $msgOut->getBody());

        $this->driver->ack();

        sleep(1);

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
        $this->driver->send($this->createQueueMessage($msg));

        $this->driver->subscribe($this->queueName);

        $msgOut = $this->driver->receive();
        $this->assertEquals($msg, $msgOut->getBody());

        $this->driver->nack();

        sleep(1);

        $msgOut = $this->driver->receive();
        $this->assertEquals($msg, $msgOut->getBody());
        $this->driver->ack();

        $this->driver->unSubscribe();
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testAfterRetriesShouldDiscard($msg)
    {
        $this->driver->send($this->createQueueMessage($msg));

        $this->driver->subscribe($this->queueName);

        $msgOut = $this->driver->receive();
        $this->assertEquals($msg, $msgOut->getBody());

        // It should be discarded after exactly 5 nacks (4 retries)
        for ($i = 0; $i < 5; ++$i) {
            $this->assertNotNull($msgOut);
            if ($msgOut) {
                $this->driver->nack();
            }
            $msgOut = $this->driver->receive();
            sleep(1);
        }

        $msgOut = $this->driver->receive();
        $this->assertNull($msgOut);
        if ($msgOut) {
            $this->driver->nack();
        }

        $this->driver->unSubscribe();
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testAfterExpiresShouldDiscard(MessageInterface $msg)
    {
        $ttlSeconds = 2;

        $msgIn = $this->createQueueMessage($msg);
        $msgIn->setExpires((time() + $ttlSeconds) * 1000);

        $this->driver->send($msgIn);
        $this->driver->subscribe($this->queueName);

        // After 1 second, the message is still there
        sleep(1);
        $msgOut = $this->driver->receive();
        $this->assertEquals($msg, $msgOut->getBody());
        if ($msgOut) {
            $this->driver->nack();
        }
        $this->driver->unSubscribe();

        // After >2 seconds, the message is not there
        sleep(3);
        $this->driver->subscribe($this->queueName);
        $msgOut = $this->driver->receive();
        $this->assertNull($msgOut);
        if ($msgOut) {
            $this->driver->nack();
        }

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
        sleep(1);
        $msgOut = $this->driver->receive();
        $this->assertNotNull($msgOut);

        if ($msgOut) {
            $this->assertEquals($msg, $msgOut->getBody());
            $this->driver->nack();
        }
        $this->driver->unSubscribe();

        // After > 2 seconds, the message is not there
        sleep(3);
        $this->driver->subscribe($this->queueName);
        $msgOut = $this->driver->receive();
        $this->assertNull($msgOut);
        if ($msgOut) {
            $this->driver->nack();
        }

        $this->driver->unSubscribe();
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testShouldSelect(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);

        $this->driver->subscribe($this->queueName, 'test_header = 12345');

        $msgOut = $this->driver->receive();
        if ($msgOut) {
            $this->driver->ack();
        }

        $this->assertEquals($msg, $msgOut->getBody());
        $this->driver->unSubscribe();
    }

    /**
     * @dataProvider getMessages
     *
     * @param MessageInterface $msg
     */
    public function testShouldNotSelect(MessageInterface $msg)
    {
        $msgIn = $this->createQueueMessage($msg);
        $msgIn->addHeader('test_header', '12345');
        $this->driver->send($msgIn);

        $this->driver->subscribe($this->queueName, 'test_header = 6666');

        $msgOut = $this->driver->receive();

        $this->assertNull($msgOut);
        if ($msgOut) {
            $this->driver->ack();
        }
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

        $this->assertEquals(
            array_map('self::mapTitle', $sentMessages),
            array_map('self::mapTitle', $receivedMessages)
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

        $this->assertEquals(
            array_map('self::mapTitle', $sentMessages),
            array_map('self::mapTitle', $receivedMessages)
        );
    }

    public function getMessages()
    {
        $simple = $this->createSimpleEntity();

        $item = new Message(new SerializableArray([$simple, $simple]));

        $x = new EntityX(1);
        $x1 = new Message($x);

        $complex = new Message(new SerializableArray(['x' => $x1, 'item' => $item, 'item2' => $item, 'item3' => $item]));

        $complex->setHeader('tracking-test-id', uniqid());
        $x1->setHeader('tracking-test-id', uniqid());
        $item->setHeader('tracking-test-id', uniqid());

        return [
            [$complex],
        ];
    }

    private function createSimpleEntity($title = 'item', $description = 'a simple item')
    {
        $entity = new TestEntity();
        $entity->setDescription($description);
        $entity->setTitle($title);
        $entity->setNote('Note here');

        return new Message($entity);
    }

    public static function mapTitle(QueueMessage $message)
    {
        /** @var Message $wrappedMessage */
        $wrappedMessage = $message->getBody();
        /** @var TestEntity $item */
        $item = $wrappedMessage->getBody();

        return $item->getTitle();
    }
}
