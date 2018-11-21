<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\DB;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\Dbal\ConfigurableDbalProtocol;
use Smartbox\Integration\FrameworkBundle\Components\DB\DBConfigurableConsumer;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageFactory;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\SmartesbHelper;

class DBConfigurableConsumerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DBConfigurableConsumer
     */
    private $consumer;

    /**
     * @var ConfigurableStepsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stepProvider;

    /**
     * @var MessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageFactory;

    /**
     * @var SmartesbHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->stepProvider = $this->createMock(ConfigurableStepsProviderInterface::class);
        $this->helper = $this->createMock(SmartesbHelper::class);

        $this->consumer = new DBConfigurableConsumer();
        $this->consumer->setConfHelper(new ConfigurableServiceHelper());
        $this->consumer->setConfigurableStepsProvider($this->stepProvider);
        $this->consumer->setMessageFactory($this->messageFactory);
        $this->consumer->setSmartesbHelper($this->helper);
        $this->consumer->setExpirationCount(2);
        $this->consumer->setMethodsConfiguration([
            'myMethod' => [
                ConfigurableConsumerInterface::CONFIG_ON_CONSUME => [],
                ConfigurableConsumerInterface::CONFIG_QUERY_STEPS => [],
                ConfigurableConsumerInterface::CONFIG_QUERY_RESULT => $this->createMock(SerializableInterface::class),
            ],
        ]);

        $this->helper->expects($this->any())->method('getMessageFactory')->willReturn($this->messageFactory);
    }

    public function testGetConfigurableStepsProvider()
    {
        $this->assertSame($this->stepProvider, $this->consumer->getConfigurableStepsProvider());
    }

    /**
     * @group time-sensitive
     */
    public function testConsume()
    {
        $options = [
            ConfigurableDbalProtocol::OPTION_METHOD => 'myMethod',
            ConfigurableDbalProtocol::OPTION_STOP_ON_NO_RESULTS => true,
            ConfigurableDbalProtocol::OPTION_SLEEP_TIME => 10000,
            ConfigurableDbalProtocol::OPTION_INACTIVITY_TRIGGER => 1,
        ];

        /** @var EndpointInterface|\PHPUnit_Framework_MockObject_MockObject $endpoint */
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->expects($this->any())->method('getOptions')->willReturn($options);
        $endpoint->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function ($key) use ($options) {
                return $options[$key];
            });

        $this->messageFactory->expects($this->exactly(2))
            ->method('createMessage')
            ->willReturn($this->createMock(MessageInterface::class));

        $start = \microtime(true);
        $this->consumer->consume($endpoint);
        $this->assertLessThan(10, \microtime(true) - $start, 'Execution should no last more than 10s');
    }
}
