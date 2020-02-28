<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Drivers\Queue;

use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;

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

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver(): QueueDriverInterface
    {
        if (!static::$kernel) {
            self::bootKernel();
        }

        return static::$kernel->getContainer()->get('smartesb.drivers.queue.amqp');
    }
}
