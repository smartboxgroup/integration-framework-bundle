<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\Queues\AsyncQueueConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractAsyncConsumer;

trait ConsumerMockFactory
{
    /**
     * @param TestCase $testCase
     * @param string $type Type of class to mock
     * @param object $message Message to pass to the callback
     * @param int $rounds Amount of messages to consume before stopping
     * @param array $methods Other methods to mock
     *
     * @return MockObject
     */
    protected function getConsumer(TestCase $testCase, string $type, $message, int $rounds = -1, array $methods = []): MockObject
    {
        if (!in_array($type, [AsyncQueueConsumer::class, AbstractAsyncConsumer::class])) {
            throw new \LogicException(sprintf('Invalid consumer class passed, %s is not mockable by this trait.', $type));
        }

        /** @var AsyncQueueConsumer|AbstractAsyncConsumer|MockObject $consumer */
        $consumer = $testCase->getMockBuilder($type)
            ->setMethods(array_merge(['asyncConsume', 'wait', 'waitNoBlock', 'initialize', 'cleanUp', 'confirmMessage'], $methods))
            ->getMock();

        /** @var \Closure $callback */
        $callback = null;
        $consumer->expects($testCase->once())
            ->method('asyncConsume')
            ->with(
                $testCase->anything(),
                // Steal the callback so we can call it manually and pretend we are "consuming"
                $testCase->callback(function ($stolenCallback) use (&$callback) {
                    return $callback = $stolenCallback;
                }));
        $consumer->expects(-1 === $rounds ? $testCase->any() : $testCase->exactly($rounds))
            ->method('waitNoBlock')
            ->willReturnCallback(function () use (&$callback, $consumer, $message) {
                $callback($message);
            });

        $consumer->setExpirationCount($rounds);

        return $consumer;
    }
}
