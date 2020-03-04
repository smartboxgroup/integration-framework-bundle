<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\FrameworkBundle\Tests\Command\ConsumeCommandTest;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\PhpAmqpLibDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;

/**
 * @internal
 */
class AmqpQueueDriverTest extends AbstractQueueDriverTest
{
    protected function setUp()
    {
        if (!\extension_loaded('amqp')) {
            $this->markTestSkipped('AMQP extension is need for that test');
        }

        if (!static::$kernel) {
            self::bootKernel();
        }

        parent::setUp();
    }

    public function setMockConsumer($expirationCount)
    {
        self::bootKernel();

        $this->mockConsumer = $this
            ->getMockBuilder(AsyncQueueConsumer::class)
            ->setMethods(['consume', 'setExpirationCount'])
            ->getMock();
        $this->mockConsumer
            ->method('setExpirationCount')
            ->with($expirationCount);
        $this->mockConsumer
            ->method('consume')
            ->willReturn(true);

        static::$kernel->getContainer()->set('smartesb.async_consumers.queue', $this->mockConsumer);
        static::$kernel->getContainer()->get('smartesb.protocols.queue')->setDefaultConsumer($this->mockConsumer);
    }


    /**
     * {@inheritdoc}
     */
    protected function createDriver(): QueueDriverInterface
    {
        $this->setMockConsumer(ConsumeCommandTest::NB_MESSAGES);
        return static::$kernel->getContainer()->get('smartesb.drivers.queue.amqp');
    }
}
