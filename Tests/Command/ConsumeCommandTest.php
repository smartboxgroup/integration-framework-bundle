<?php

namespace Smartbox\FrameworkBundle\Tests\Command;

use Smartbox\Integration\FrameworkBundle\Command\ConsumeCommand;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\QueueDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\MockObject\MockObject;

class ConsumeCommandTest extends KernelTestCase
{
    const NB_MESSAGES = 1;
    const URI = 'queue://main/api';

    protected $mockConsumer;

    /**
     * @var MockObject
     */
    private $mockQueueDriver;

    public function setUp()
    {
        self::bootKernel();
    }

    public function setMockQueueDriver()
    {
        $msg = new QueueMessage(new Message());

        $this->mockQueueDriver = $this
            ->getMockBuilder(QueueDriverInterface::class)
            ->setMethods([
                'configure',
                'connect',
                'disconnect',
                'isConnected',
                'send',
                'ack',
                'nack',
                'createQueueMessage',
                'setFormat',
                'getFormat',
                'getInternalType'
            ])
            ->getMock();
        $this->mockQueueDriver
            ->method('configure');
        $this->mockQueueDriver
            ->method('connect');
        $this->mockQueueDriver
            ->method('disconnect');
        $this->mockQueueDriver
            ->method('isConnected');
        $this->mockQueueDriver
            ->method('send')
            ->with($msg);
        $this->mockQueueDriver
            ->method('ack');
        $this->mockQueueDriver
            ->method('nack');
        $this->mockQueueDriver
            ->method('createQueueMessage');
        $this->mockQueueDriver
            ->method('setFormat');
        $this->mockQueueDriver
            ->method('getFormat');
        $this->mockQueueDriver
            ->method('getInternalType');

        self::$kernel->getContainer()->set(QueueDriverInterface::class, $this->mockQueueDriver);
        self::$kernel->getContainer()->set('doctrine', $this->createMock(RegistryInterface::class));
    }

    public function setMockConsumer($expirationCount)
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
            ->with($expirationCount);
        $this->mockConsumer
            ->method('setSmartesbHelper');
        $this->mockConsumer
            ->method('getName');
        $this->mockConsumer
            ->method('getId');
        $this->mockConsumer
            ->method('getInternalType');

        self::$kernel->getContainer()->set('smartesb.consumers.queue', $this->mockConsumer);
    }

    public function testExecuteWithKillAfter()
    {
        $this->setMockQueueDriver();
        $this->setMockConsumer(self::NB_MESSAGES);

        $application = new Application(self::$kernel);
        $application->add(new ConsumeCommand());

        $command = $application->find('smartesb:consumer:start');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'uri' => self::URI, // argument
            '--killAfter' => self::NB_MESSAGES, // option
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains('limited to', $output);
        $this->assertContains('Consumer was gracefully stopped', $output);
    }

    public function testExecuteWithoutKillAfter()
    {
        $this->setMockConsumer(0);

        $application = new Application(self::$kernel);
        $application->add(new ConsumeCommand());

        $command = $application->find('smartesb:consumer:start');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'uri' => self::URI, // argument
        ));

        $output = $commandTester->getDisplay();
        $this->assertNotContains('limited to', $output);
        $this->assertContains('Consumer was gracefully stopped', $output);
    }
}
