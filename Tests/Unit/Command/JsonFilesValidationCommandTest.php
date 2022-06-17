<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Command;

use Smartbox\Integration\FrameworkBundle\Command\JsonFilesValidationCommand;
use Smartbox\Integration\FrameworkBundle\Tests\BaseKernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class JsonFilesValidationCommandTest.
 */
class JsonFilesValidationCommandTest extends BaseKernelTestCase
{
    public function testExecuteForSuccessOutput()
    {
        $application = new Application(self::$kernel);
        $application->add(new JsonFilesValidationCommand());

        $command = $application->find('smartbox:validate:json');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'path' => '@SmartboxIntegrationFrameworkBundle/Tests/Unit/Command/fixtures/success',
        ]);

        $this->assertMatchesRegularExpression('/Everything is OK./', $commandTester->getDisplay());
    }

    public function testExecuteForFailureOutput()
    {
        $application = new Application(self::$kernel);
        $application->add(new JsonFilesValidationCommand());

        $path = '@SmartboxIntegrationFrameworkBundle/Tests/Unit/Command/fixtures/failure';

        $command = $application->find('smartbox:validate:json');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'path' => $path,
        ]);

        $this->assertMatchesRegularExpression(
            \sprintf(
                '/Some fixture files in "%s" directory have invalid format./',
                '@SmartboxIntegrationFrameworkBundle\/Tests\/Unit\/Command\/fixtures\/failure'
            ),
            $commandTester->getDisplay()
        );
    }
}
