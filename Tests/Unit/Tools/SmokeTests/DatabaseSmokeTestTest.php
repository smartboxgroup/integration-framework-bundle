<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Tools\SmokeTests;

use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;
use Smartbox\CoreBundle\Utils\SmokeTest\Output\SmokeTestOutputInterface;
use Smartbox\Integration\FrameworkBundle\Tools\SmokeTests\DatabaseSmokeTest;

/**
 * @group database_smoke-test
 */
class DatabaseSmokeTestTest extends TestCase
{
    /**
     * @var ConnectionRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var DatabaseSmokeTest
     */
    private $smokeTest;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->createMock(ConnectionRegistry::class);
        $this->smokeTest = new DatabaseSmokeTest($this->registry);
    }

    public function testSuccessfulRun()
    {
        $this->registry->expects($this->any())
            ->method('getConnections')
            ->willReturn(['foo' => $this->getConnection()]);

        $output = $this->smokeTest->run();

        $this->assertInstanceOf(SmokeTestOutputInterface::class, $output);
        $this->assertTrue($output->isOK(), 'Output should be OK.');

        $messages = $output->getMessages();
        $this->assertCount(1, $messages);
        $this->assertSame(
            '[success] Connection to "foo" database checked, found 3 table(s).',
            (string) $messages[0]
        );
    }

    public function testFailedRun()
    {
        $this->registry->expects($this->any())
            ->method('getConnections')
            ->willReturn([
                'foo' => $this->getConnection(),
                'bar' => $this->getConnection(true),
                'baz' => $this->getConnection(),
            ]);

        $output = $this->smokeTest->run();

        $this->assertInstanceOf(SmokeTestOutputInterface::class, $output);
        $this->assertFalse($output->isOK(), 'Output shouldn\'t be OK.');

        $messages = \array_map('strval', $output->getMessages());

        $this->assertCount(3, $messages);
        $this->assertEquals(
            [
                '[success] Connection to "foo" database checked, found 3 table(s).',
                '[failure] Connection to "bar" database failed: "Black Hawk 64 is going down, I repeat, Black Hawk 64 is going dow...".',
                '[success] Connection to "baz" database checked, found 3 table(s).',
            ],
            $messages
        );
    }

    /**
     * @param bool $throw
     *
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getConnection($throw = false)
    {
        $method = 'willThrowException';
        $expected = new Exception('Black Hawk 64 is going down, I repeat, Black Hawk 64 is going dow...');

        if (!$throw) {
            $method = 'willReturn';
            $expected = ['foo', 'bar', 'baz'];
        }

        $manager = $this->createMock(AbstractSchemaManager::class);
        $manager->expects($this->once())->method('listTables')->$method($expected);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('getSchemaManager')->willReturn($manager);

        return $connection;
    }

    public function testGetDescription()
    {
        $this->registry->expects($this->once())
            ->method('getConnectionNames')
            ->willReturn([
                'foo' => 'doctrine.dbal.foo',
                'bar' => 'doctrine.dbal.bar',
            ]);

        $this->assertSame('Test database connectivity for connections: foo, bar.', $this->smokeTest->getDescription());
    }
}
