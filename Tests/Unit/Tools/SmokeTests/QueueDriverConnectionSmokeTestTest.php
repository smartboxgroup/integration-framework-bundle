<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\SmokeTests;

use PhpAmqpLib\Exception\AMQPProtocolException;
use PHPUnit\Framework\TestCase;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutput;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;
use Smartbox\Integration\FrameworkBundle\Tools\SmokeTests\QueueDriverConnectionSmokeTest;

/**
 * @group queue_driver_smoke-test
 */
class QueueDriverConnectionSmokeTestTest extends TestCase
{
    /**
     * Test that the smoke test can be test...ed
     */
    public function testHappyPath()
    {
        $queueDriver = $this->createMock(QueueDriverInterface::class);
        $queueDriver
            ->method('isConnected')
            ->willReturn(true);
        $queueDriver
            ->method('send')
            ->willReturn(true);

        $test = new QueueDriverConnectionSmokeTest($queueDriver);
        $result = $test->run();

        $this->assertInstanceOf(SmokeTestOutput::class, $result);
        $this->assertTrue($result->isOK());
    }

    /**
     * Test connection failures. Should catch anything.
     */
    public function testConnectionFailure()
    {
        $queueDriver = $this->createMock(QueueDriverInterface::class);
        $queueDriver->expects($this->once())
            ->method('connect')
            ->willThrowException(new class('LiteraryAnyException') extends \Exception {
            });

        $test = new QueueDriverConnectionSmokeTest($queueDriver);
        $result = $test->run();

        $this->assertInstanceOf(SmokeTestOutput::class, $result);
        $this->assertFalse($result->isOK());
    }

    /**
     * Test that the smoke test fails when failing to insert a message in the queue.
     */
    public function testInsertionFailure()
    {
        $queueDriver = $this->createMock(QueueDriverInterface::class);
        $queueDriver
            ->method('isConnected')
            ->willReturn(true);

        $queueDriver
            ->method('send')
            ->willReturn(false);

        $test = new QueueDriverConnectionSmokeTest($queueDriver);
        $result = $test->run();

        $this->assertInstanceOf(SmokeTestOutput::class, $result);
        $this->assertFalse($result->isOK());
        $this->assertEquals('Failed to insert message in queue.', $result->getMessages()[0]->getValue());
    }

    /**
     * Make sure that the TTL of the smoke test message is short lived to prevent it polluting the queue.
     */
    public function testTestMessageIsShortLived()
    {
        $queueDriver = $this->createMock(QueueDriverInterface::class);
        $queueDriver
            ->method('isConnected')
            ->willReturn(true);

        $queueDriver
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (QueueMessageInterface $message) {
                return $message->getTTL() <= 1;
            }));

        $test = new QueueDriverConnectionSmokeTest($queueDriver);
        $test->run();
    }
}
