<?php
namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Consumers;

use Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableProtocol;
use Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Smartbox\Integration\FrameworkBundle\Core\Producers\AbstractConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableConsumer;

class CsvConfigurableConsumerTest extends BaseTestCase
{
    /** @var CsvConfigurableConsumer */
    protected $consumer;

    /** @var CsvConfigurableStepsProvider */
    protected $stepsProvider;

    /** @var CsvConfigurableProtocol */
    protected $protocol;

    /** @var SmartesbHelper */
    protected $configHelper;

    /** @var MessageFactory  */
    protected $messageFactory;

    public function setUp()
    {
        parent::setUp();

        $this->optionsResolver = new OptionsResolver();

        $this->protocol = new ConfigurableWebserviceProtocol();
        $this->protocol->configureOptionsResolver($this->optionsResolver);

        $this->stepsProvider = self::getContainer()->get('smartesb.steps_provider.csv_file');
        $this->configHelper = self::getContainer()->get('smartesb.configurable_service_helper');
        $this->messageFactory = self::getContainer()->get('smartesb.message_factory');
        $this->smartEsbHelper = self::getContainer()->get('smartesb.helper');
        $this->asyncHandler = self::getContainer()->get('smartesb.handlers.async');

        $this->consumer = new CsvConfigurableConsumer();
        $this->consumer->setConfigurableStepsProvider( $this->stepsProvider );
        $this->consumer->setConfHelper($this->configHelper);
        $this->consumer->setMessageFactory($this->messageFactory);
        $this->consumer->setSmartesbHelper($this->smartEsbHelper);


        //And setup some methods for the consumer to choose from
        $methodConfig = [
            'readHappy' => [
                'query_steps' => [
                        [ 'read_lines' => [
                            'result_name' => 'xxx',
                            'max_lines' => 3,
                        ]
                    ]
                ],
                'query_result' => [
                    'lines' => 'eval: results[\'xxx\']'
                ]
            ]
        ];
        $this->consumer->setMethodsConfiguration( $methodConfig );
    }

    public function testConsumerExists()
    {
        $this->assertInstanceOf('Smartbox\Integration\FrameworkBundle\Components\FileService\Csv\CsvConfigurableConsumer', $this->consumer);
    }

    // read from a file and stop consuming
    public function testReadAndStop()
    {
        //create a happy csv file
        $file_name = md5(microtime()) . '.hello.world';
        file_put_contents('/tmp/' . $file_name, "a1|b1|c1\na2|b2|c2\na3|b3|c3\na4|b4|c4\na5|b5|c5\na6|b6|c6\na7|b7|c7");

        //configure an endpoint
        $options = [
            'root_path' => '/tmp/',
            'default_path' => $file_name,
            'delimiter' => '|',
            'enclosure' => '"',
            'escape_char' => '\\',
            'stop_on_eof' => true,
            'method' => 'readHappy',
            'max_length' => 1000,
        ];

        $endpoint = new Endpoint('csv://happy/test', $options, $this->protocol, null, $this->consumer, $this->asyncHandler);

        //consume
        $this->consumer->consume($endpoint);
        
        //check our result is as we expected

    }

    // read from a file that does not exist
    // read from a file that has a funny encoding
    // read from a file with /r/n
    // read from a file with escaped characters
    // read from a file with no separators
    // check that we close all file handles on tare down

}
