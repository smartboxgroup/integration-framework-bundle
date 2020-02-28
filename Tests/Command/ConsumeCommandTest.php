<?php

namespace Smartbox\FrameworkBundle\Tests\Command;

use Smartbox\Integration\FrameworkBundle\Command\ConsumeCommand;
use Smartbox\Integration\FrameworkBundle\Components\Queues\Drivers\StompQueueDriver;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueConsumer;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessage;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeCommandTest extends KernelTestCase
{
    const NB_MESSAGES = 1;
    const URI = 'queue://main/api';

    protected $mockConsumer;

    public function setUp(): void
    {
        self::bootKernel();
    }

    public function setMockQueueDriver()
    {
        $msg = new QueueMessage(new Message());

        $this->mockQueueDriver = $this
            ->getMockBuilder(StompQueueDriver::class)
            ->setMethods(['connect', 'send'])
            ->getMock();
        $this->mockQueueDriver
            ->method('connect');
        $this->mockQueueDriver
            ->method('send')
            ->with($msg);

        self::$kernel->getContainer()->set(StompQueueDriver::class, $this->mockQueueDriver);
    }

    public function setMockConsumer($expirationCount)
    {
        $this->mockConsumer = $this
            ->getMockBuilder(QueueConsumer::class)
            ->setMethods(['consume', 'setExpirationCount'])
            ->getMock();
        $this->mockConsumer
            ->method('setExpirationCount')
            ->with($expirationCount);
        $this->mockConsumer
            ->method('consume')
            ->willReturn(true);

        self::$kernel->getContainer()->set('smartesb.consumers.queue', $this->mockConsumer);
    }

    public function testExecuteWithKillAfter()
    {
        $this->setMockQueueDriver();
        $this->setMockConsumer(self::NB_MESSAGES);

        self::$kernel->getContainer();
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
