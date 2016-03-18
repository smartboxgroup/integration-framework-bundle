<?php
namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Producers;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Producers\ConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Endpoints\ConfigurableWebserviceEndpoint;
use Smartbox\Integration\FrameworkBundle\Exceptions\ProducerRecoverableException;
use Smartbox\Integration\FrameworkBundle\Exceptions\EndpointUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;

class ConfigurableProducerTest extends BaseTestCase{

    /** @var  ConfigurableProducer|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurableProducer;

    protected $defaultOptions = [
        'x' => 1,
        'y' => 2,
        'z' => [1,2,3]
    ];

    protected $simpleMethodsConfig = [
          'methodA' => [
              ConfigurableProducer::KEY_DESCRIPTION => 'Description here',
              ConfigurableProducer::KEY_STEPS => [
                    [ConfigurableProducer::STEP_DEFINE => [
                      'x' => 'eval: 1 + 2',
                      'val' => 'eval: msg.getBody().get("value")'
                    ]],
                    [ConfigurableProducer::STEP_DEFINE => [
                      'result' => 'eval: x + val'
                    ]]
              ],
              ConfigurableProducer::KEY_VALIDATIONS => [
                  [
                      'rule' => 'eval: x == 3',
                      'message' => 'Define does not work!',
                      'recoverable' => true,
                  ],
                  [
                      'rule' => 'eval: val != 666',
                      'message' => 'Ugly number!!',
                      'recoverable' => true,
                  ],
                  [
                      'rule' => 'eval: val != 1313666',
                      'message' => 'Too ugly number!!',
                      'recoverable' => false,
                  ]
              ],
              ConfigurableProducer::KEY_RESPONSE => [
                  'result' => 'eval: 1 + 2 + msg.getBody().get(\'value\') + 10'
              ],
          ]
    ];

    public function setUp()
    {
        parent::setUp();

        $this->configurableProducer = $this->getMockBuilder(ConfigurableProducer::class)->setMethods(null)->getMock();

        $this->configurableProducer->setEvaluator($this->getContainer()->get('smartesb.util.evaluator'));
        $this->configurableProducer->setSerializer($this->getContainer()->get('serializer'));
        $this->configurableProducer->setOptions($this->defaultOptions);

        $this->configurableProducer->setMethodsConfiguration($this->simpleMethodsConfig);
    }

    public function testDefaultOptionsShouldBeSet()
    {
        $defaults = $this->configurableProducer->getOptions();

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

        $this->configurableProducer->executeStep('define', [
            'r1' => 'eval: x + y',
            'r2' => [
                'sub1' => [
                    'a' => 'eval: x*10',
                    'b' => 'eval: y*10'
                ],
                'sub2' => [
                    'a' => 'eval: x+10',
                    'b' => 'eval: y+10'
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

        $this->configurableProducer->send($exchange,[
            ConfigurableWebserviceEndpoint::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceEndpoint::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceEndpoint::EXCHANGE_PATTERN_IN_OUT
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

        $this->configurableProducer->send($exchange,[
            ConfigurableWebserviceEndpoint::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceEndpoint::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceEndpoint::EXCHANGE_PATTERN_IN_ONLY
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

        $this->setExpectedException(EndpointUnrecoverableException::class,"Too ugly number!!");

        $this->configurableProducer->send($exchange,[
            ConfigurableWebserviceEndpoint::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceEndpoint::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceEndpoint::EXCHANGE_PATTERN_IN_OUT
        ]);
    }

    public function testValidationWorksWithRecoverableException(){

        $exchange = new Exchange(
            new Message(new SerializableArray(['value' => 666]))
        );

        $this->setExpectedException(ProducerRecoverableException::class,"Ugly number!!");

        $this->configurableProducer->send($exchange,[
            ConfigurableWebserviceEndpoint::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceEndpoint::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceEndpoint::EXCHANGE_PATTERN_IN_OUT
        ]);
    }
}
