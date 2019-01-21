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
        if (version_compare(PHP_VERSION, '7.1.3', '<')) {
            $this->markTestSkipped('AMQP Driver need PHP version >= 7.1.3');
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
