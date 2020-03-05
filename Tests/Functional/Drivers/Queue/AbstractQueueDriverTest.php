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
        $this->driver->connect();
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
        if (!$this->driver->isConnected()) {
            $this->driver->connect();
        }
        $this->driver->send($this->createQueueMessage($msg));

        $this->assertSame(serialize($clone), serialize($msg));
        $this->driver->send($this->createQueueMessage($msg));
    }

    /**
     * @return array
     */
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

    /**
     * @param QueueMessage $message
     * @return mixed
     */
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
