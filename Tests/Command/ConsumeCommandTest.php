<?php

namespace Smartbox\FrameworkBundle\Tests\Command;

use Smartbox\Integration\FrameworkBundle\Command\ConsumeCommand;
use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeCommandTest extends KernelTestCase
{
    const NB_MESSAGES = 1;
    const URI = 'queue://main/api';

    protected $mockConsumer;

    /** @var EndpointFactory $endpointFactory */
    protected $endpointFactory;

    public function setMockConsumer($expirationCount)
    {
        self::bootKernel();

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
        $this->endpointFactory = self::$kernel->getContainer()->get('smartesb.endpoint_factory');
    }

    public function testExecuteWithKillAfter()
    {
        $this->setMockConsumer(self::NB_MESSAGES);

        $application = new Application(self::$kernel);
        $application->add(new ConsumeCommand($this->endpointFactory));

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
        $application->add(new ConsumeCommand($this->endpointFactory));

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
