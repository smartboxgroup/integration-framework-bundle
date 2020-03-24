<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use PHPUnit\Framework\MockObject\MockObject;
use Smartbox\CoreBundle\Tests\Fixtures\Entity\TestEntity;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\FrameworkBundle\Tests\Command\ConsumeCommandTest;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
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

    const CONSUMER_TAG = 'consumer-test-%s-%s';

    /**
     * @var string
     */
    protected $queueName;

    /**
     * @var QueueDriverInterface
     */
    protected $driver;

    /**
     * @var MockObject
     */
    private $mockConsumer;

    protected function setUp()
    {
        parent::setUp();
        $this->driver = $this->createDriver();
        $this->driver->connect();
        $this->queueName = static::QUEUE_PREFIX.(new \ReflectionClass($this->driver))->getShortName().md5(random_bytes(10));
    }

    protected function tearDown()
    {
        $this->driver->disconnect();
        $this->driver = null;
        parent::tearDown();
    }

    /**
     * @dataProvider getMessages
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
     * Creates a QueueMessage object
     *
     * @param $message
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
     * Creates a simple entity for test purpose
     *
     * @param string $title
     * @param string $description
     * @return Message
     */
    protected function createSimpleEntity($title = 'item', $description = 'a simple item')
    {
        $entity = new TestEntity();
        $entity->setDescription($description);
        $entity->setTitle($title);
        $entity->setNote('Note here');

        return new Message($entity);
    }

    /**
     * Creates a mock based on the ConsumerInteface
     *
     * @return MockObject
     */
    public function setMockConsumer()
    {
        $this->mockConsumer = $this
            ->getMockBuilder(ConsumerInterface::class)
            ->setMethods(['stop', 'consume', 'setExpirationCount', 'setSmartesbHelper', 'getName', 'getId', 'getInternalType'])
            ->getMock();
        $this->mockConsumer
            ->method('stop');
        $this->mockConsumer
            ->method('consume')
            ->willReturn(true);
        $this->mockConsumer
            ->method('setExpirationCount')
            ->with(ConsumeCommandTest::NB_MESSAGES);
        $this->mockConsumer
            ->method('setSmartesbHelper');
        $this->mockConsumer
            ->method('getName')
            ->willReturn(sprintf(self::CONSUMER_TAG, gethostname(), getmypid()));
        $this->mockConsumer
            ->method('getId');
        $this->mockConsumer
            ->method('getInternalType');

        return $this->mockConsumer;
    }
}
