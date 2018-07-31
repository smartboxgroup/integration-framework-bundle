<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Components\Db\Dbal;

use Smartbox\Integration\FrameworkBundle\Components\DB\Dbal\ConfigurableDbalProtocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RestConfigurableProducerTest.
 */
class ConfigurableDbalProtocolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurableDbalProtocol
     */
    private $dbalProtocol;

    /**
     * @var array
     */
    private $expectedOptions;

    protected function setUp()
    {
        $this->dbalProtocol = new ConfigurableDbalProtocol();
        $this->expectedOptions = [
            ConfigurableDbalProtocol::OPTION_METHOD,
            ConfigurableDbalProtocol::OPTION_STOP_ON_NO_RESULTS,
            ConfigurableDbalProtocol::OPTION_DB_CONNECTION_NAME,
            ConfigurableDbalProtocol::OPTION_SLEEP_TIME,
            ConfigurableDbalProtocol::OPTION_INACTIVITY_TRIGGER,
        ];
    }

    protected function tearDown()
    {
        $this->dbalProtocol = null;
        $this->expectedOptions = null;
    }

    /**
     * Method to test the options descriptions available for this protocol.
     */
    public function testGetOptionsDescriptions()
    {
        $options = $this->dbalProtocol->getOptionsDescriptions();

        foreach ($this->expectedOptions as $expectedOption) {
            $this->assertArrayHasKey($expectedOption, $options);
        }
    }

    /**
     * Method to test if the options resolver is configured with the expected options.
     */
    public function testConfigureOptionsResolver()
    {
        $resolver = new OptionsResolver();

        $this->dbalProtocol->configureOptionsResolver($resolver);

        foreach ($this->expectedOptions as $expectedOption) {
            $this->assertTrue($resolver->isDefined($expectedOption));
        }
    }
}
