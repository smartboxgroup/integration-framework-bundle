<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

/**
 * @internal
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

        $msgOut = $this->driver->receive();
        if ($msgOut) {
            $this->driver->ack();
        }

        $this->assertSame(serialize($msg), serialize($msgOut->getBody()));
        $this->driver->unSubscribe();
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver(): QueueDriverInterface
    {
        return $this->getContainer()->get('smartesb.drivers.queue.main');
    }
}
