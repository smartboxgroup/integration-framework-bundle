<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tests;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueManager;

/**
 * @group queue-manager
 *
 * @internal
 */
class QueueManagerTest extends TestCase
{
    public function testFailureToConnect()
    {
        if (!class_exists('AMQPConnection')) {
            $this->markTestSkipped('ext-amqp is missing');
        }

        $expected = <<<'EOL'
Unable to connect to any of the following hosts:
host-1: I'm connection #1 and I let you down :/
host-2: I'm connection #2 and I let you down :/
host-3: I'm connection #3 and I let you down :/
EOL;

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage($expected);
        $connections = [];
        for ($i = 1; $i < 4; ++$i) {
            $mock = $this->createMock('AMQPConnection');

            $mock->expects($this->once())
                ->method('connect')
                ->willThrowException(new \AMQPConnectionException("I'm connection #$i and I let you down :/"));

            $mock->expects($this->once())
                ->method('getHost')
                ->willReturn("host-$i");
            $connections[] = $mock;
        }

        $manager = new QueueManager($connections);
        $manager->connect();
    }
}
