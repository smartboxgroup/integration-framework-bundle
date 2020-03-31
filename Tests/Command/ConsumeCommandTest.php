<?php

namespace Smartbox\FrameworkBundle\Tests\Command;

use Smartbox\Integration\FrameworkBundle\Command\ConsumeCommand;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ConsumeCommandTest
 * @group consume-command-test
 */
class ConsumeCommandTest extends KernelTestCase
{
    const NB_MESSAGES = 1;
    const URI = 'queue://main/api';

    protected $mockConsumer;

    public function setUp()
    {
        self::bootKernel();
    }

    public function setMockConsumer($expirationCount)
    {
        $this->mockConsumer = $this->createMock(ConsumerInterface::class);
        $this->mockConsumer->method('consume')->willReturn(true);
        $this->mockConsumer->method('setExpirationCount')->with($expirationCount);

        self::$kernel->getContainer()->set('smartesb.consumers.queue', $this->mockConsumer);
        self::$kernel->getContainer()->set('doctrine', $this->createMock(RegistryInterface::class));
    }

    public function testExecuteWithKillAfter()
    {
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
