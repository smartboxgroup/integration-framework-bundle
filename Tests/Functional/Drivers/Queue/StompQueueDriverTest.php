<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;

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
}
