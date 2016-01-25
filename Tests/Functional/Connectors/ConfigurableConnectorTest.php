<?php
namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Connectors;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Connectors\ConfigurableConnector;
use Smartbox\Integration\FrameworkBundle\Connectors\Connector;
use Smartbox\Integration\FrameworkBundle\Exceptions\ConnectorRecoverableException;
use Smartbox\Integration\FrameworkBundle\Exceptions\ConnectorUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;

class ConfigurableConnectorTest extends BaseTestCase{

    /** @var  ConfigurableConnector|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurableConnector;

    protected $defaultOptions = [
        'x' => 1,
        'y' => 2,
        'z' => [1,2,3]
    ];

    protected $simpleMethodsConfig = [
          'methodA' => [
              ConfigurableConnector::KEY_DESCRIPTION => 'Description here',
              ConfigurableConnector::KEY_STEPS => [
                    [ConfigurableConnector::STEP_DEFINE => [
                      'x' => '1 + 2',
                      'val' => 'msg.getBody().get("value")'
                    ]],
                      [ConfigurableConnector::STEP_REQUEST => [
                          'name' => 'test'
                      ]],
                    [ConfigurableConnector::STEP_DEFINE => [
                      'result' => 'x + val + responses["test"]'
                    ]]
              ],
              ConfigurableConnector::KEY_VALIDATIONS => [
                  [
                      'rule' => 'x == 3',
                      'message' => 'Define does not work!',
                      'recoverable' => true,
                  ],
                  [
                      'rule' => 'val != 666',
                      'message' => 'Ugly number!!',
                      'recoverable' => true,
                  ],
                  [
                      'rule' => 'val != 1313666',
                      'message' => 'Too ugly number!!',
                      'recoverable' => false,
                  ]
              ],
              ConfigurableConnector::KEY_RESPONSE => [
                  'result' => 'result'
              ],       // Should be (1+2) + msg.get('value') + 10
          ]
    ];

    public function dummyRequestMethod(array $stepActionParams, array $connectorOptions, array &$context){
        if (!is_array($stepActionParams)) {
            throw new \InvalidArgumentException(
                "Step 'request' in ConfigurableConnector expected an array as configuration"
            );
        }

        if(!array_key_exists('name',$stepActionParams)){
            throw new \InvalidArgumentException("Expected key name under configuration passed to ConfigurableConnector");
        }

        $context['responses'][$stepActionParams['name']] = 10;
    }

    public function setUp()
    {
        parent::setUp();

        $this->configurableConnector = $this->getMockBuilder(ConfigurableConnector::class)->setMethods([
            'request',
        ])->getMock();

        $this->configurableConnector->setEvaluator($this->getContainer()->get('smartesb.util.evaluator'));
        $this->configurableConnector->setSerializer($this->getContainer()->get('serializer'));
        $this->configurableConnector->setDefaultOptions($this->defaultOptions);

        $this->configurableConnector->method('request')->willReturnCallback(array($this,'dummyRequestMethod'));
        $this->configurableConnector->setMethodsConfiguration($this->simpleMethodsConfig);
    }

    public function testDefaultOptionsShouldBeSet()
    {
        $defaults = $this->configurableConnector->getDefaultOptions();

        foreach($this->defaultOptions as $defaultKey => $defaultValue){
            $this->assertArrayHasKey($defaultKey,$defaults);
            $this->assertEquals($defaults[$defaultKey],$defaultValue);
        }
    }

    public function testExecuteStepDefine(){
        $context = [
            'x' => 1,
            'y' => 2
        ];

        $this->configurableConnector->executeStep('define', [
            'r1' => 'x + y',
            'r2' => [
                'sub1' => [
                    'a' => 'x*10',
                    'b' => 'y*10'
                ],
                'sub2' => [
                    'a' => 'x+10',
                    'b' => 'y+10'
                ]
            ]
        ], [],$context);

        $this->assertEquals(3,$context['vars']['r1']);

        $this->assertEquals([
            'sub1' => [
                'a' => '10',
                'b' => '20'
            ],
            'sub2' => [
                'a' => '11',
                'b' => '12'
            ]
        ],$context['vars']['r2']);
    }

    public function testSendWorks(){

        $exchange = new Exchange(
            new Message(new SerializableArray(['value' => 5]))
        );

        $this->configurableConnector->send($exchange,[
            ConfigurableConnector::OPTION_METHOD => 'methodA',
            ConfigurableConnector::OPTION_EXCHANGE_PATTERN => Connector::EXCHANGE_PATTERN_IN_OUT
        ]);

        $this->assertInstanceOf(SerializableArray::class,$exchange->getResult()->getBody());

        $this->assertEquals(
            (3+5+10),
            $exchange->getResult()->getBody()->get('result')
        );
    }

    public function testSendWithExchangePatternInOnlyRespectsMessage(){

        $in = new Message(new SerializableArray(['value' => 5]));

        $exchange = new Exchange($in);

        $this->configurableConnector->send($exchange,[
            ConfigurableConnector::OPTION_METHOD => 'methodA',
            ConfigurableConnector::OPTION_EXCHANGE_PATTERN => Connector::EXCHANGE_PATTERN_IN_ONLY
        ]);

        $this->assertEquals(
            $in,
            $exchange->getResult()
        );
    }

    public function testValidationWorksWithUnrecoverableException(){

        $exchange = new Exchange(
            new Message(new SerializableArray(['value' => 1313666]))
        );

        $this->setExpectedException(ConnectorUnrecoverableException::class,"Too ugly number!!");

        $this->configurableConnector->send($exchange,[
            ConfigurableConnector::OPTION_METHOD => 'methodA',
            ConfigurableConnector::OPTION_EXCHANGE_PATTERN => Connector::EXCHANGE_PATTERN_IN_OUT
        ]);
    }

    public function testValidationWorksWithRecoverableException(){

        $exchange = new Exchange(
            new Message(new SerializableArray(['value' => 666]))
        );

        $this->setExpectedException(ConnectorRecoverableException::class,"Ugly number!!");

        $this->configurableConnector->send($exchange,[
            ConfigurableConnector::OPTION_METHOD => 'methodA',
            ConfigurableConnector::OPTION_EXCHANGE_PATTERN => Connector::EXCHANGE_PATTERN_IN_OUT
        ]);
    }
}