<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Command;

use Smartbox\Integration\FrameworkBundle\Command\ConsumeCommand;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConsumerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ConsumeCommandTest.
 */
class ConsumeCommandTest extends KernelTestCase
{
    const NB_MESSAGES = 1;
    const URI = 'queue://main/api';

    protected function setUp(): void
    {
        self::bootKernel();
        self::$kernel->getContainer()->set('doctrine', $this->createMock(RegistryInterface::class));
    }

    public function setMockConsumer($expirationCount)
    {
        $mockConsumer = $this->createMock(ConsumerInterface::class);
        $mockConsumer->method('consume')->willReturn(true);
        $mockConsumer->method('setExpirationCount')->with($expirationCount);

        self::$kernel->getContainer()->set('smartesb.consumers.queue', $mockConsumer);
    }

    public function testExecuteWithKillAfter()
    {
        $this->setMockConsumer(self::NB_MESSAGES);

        $application = new Application(self::$kernel);
        $application->add(new ConsumeCommand());

        $command = $application->find('smartesb:consumer:start');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'uri' => self::URI, // argument
            '--killAfter' => self::NB_MESSAGES, // option
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('limited to', $output);
        $this->assertStringContainsString('Consumer was gracefully stopped', $output);
    }

    public function testExecuteWithoutKillAfter()
    {
        $this->setMockConsumer(0);

        $application = new Application(self::$kernel);
        $application->add(new ConsumeCommand());

        $command = $application->find('smartesb:consumer:start');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'uri' => self::URI, // argument
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('limited to', $output);
        $this->assertStringContainsString('Consumer was gracefully stopped', $output);
    }
}
