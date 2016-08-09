<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Producers;

use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerRecoverableException;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerUnrecoverableException;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurableProducerTest extends BaseTestCase
{
    /** @var  ConfigurableProducer|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurableProducer;

    /** @var  OptionsResolver */
    protected $optionsResolver;

    /** @var  ConfigurableWebserviceProtocol */
    protected $protocol;

    protected $defaultOptions = [
        'x' => 1,
        'y' => 2,
        'z' => [1,2,3],
    ];

    protected $simpleMethodsConfig = [
          'methodA' => [
              ConfigurableServiceHelper::KEY_DESCRIPTION => 'Description here',
              ConfigurableServiceHelper::KEY_STEPS => [
                    [ConfigurableServiceHelper::STEP_DEFINE => [
                      'x' => 'eval: 1 + 2',
                      'val' => 'eval: msg.getBody().get("value")',
                    ]],
                    [ConfigurableServiceHelper::STEP_DEFINE => [
                      'result' => 'eval: x + val',
                    ]],
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
                  ],
              ],
              ConfigurableProducer::KEY_RESPONSE => [
                  'result' => 'eval: 1 + 2 + msg.getBody().get(\'value\') + 10',
              ],
          ],
    ];

    public function setUp()
    {
        parent::setUp();

        $this->configurableProducer = $this->getMockBuilder(ConfigurableProducer::class)->setMethods(null)->getMock();

        $confHelper = new ConfigurableServiceHelper();
        $confHelper->setSerializer($this->getContainer()->get('serializer'));
        $confHelper->setEvaluator($this->getContainer()->get('smartesb.util.evaluator'));

        $this->configurableProducer->setConfHelper($confHelper);
        $this->configurableProducer->setEvaluator($this->getContainer()->get('smartesb.util.evaluator'));
        $this->configurableProducer->setSerializer($this->getContainer()->get('serializer'));
        $this->configurableProducer->setOptions($this->defaultOptions);

        $this->configurableProducer->setMethodsConfiguration($this->simpleMethodsConfig);

        $this->optionsResolver = new OptionsResolver();
        $this->protocol = new ConfigurableWebserviceProtocol();
        $this->protocol->configureOptionsResolver($this->optionsResolver);
    }

    public function testDefaultOptionsShouldBeSet()
    {
        $defaults = $this->configurableProducer->getOptions();

        foreach ($this->defaultOptions as $defaultKey => $defaultValue) {
            $this->assertArrayHasKey($defaultKey, $defaults);
            $this->assertEquals($defaults[$defaultKey], $defaultValue);
        }
    }

    public function testExecuteStepDefine()
    {
        $context = [
            'x' => 1,
            'y' => 2,
        ];

        $actionParams = [
            'r1' => 'eval: x + y',
            'r2' => [
                'sub1' => [
                    'a' => 'eval: x*10',
                    'b' => 'eval: y*10',
                ],
                'sub2' => [
                    'a' => 'eval: x+10',
                    'b' => 'eval: y+10',
                ],
            ],
        ];

        $options = [];

        $this->configurableProducer->executeStep('define', $actionParams, $options, $context);

        $this->assertEquals(3, $context['vars']['r1']);

        $this->assertEquals([
            'sub1' => [
                'a' => '10',
                'b' => '20',
            ],
            'sub2' => [
                'a' => '11',
                'b' => '12',
            ],
        ], $context['vars']['r2']);
    }

    public function testSendWorks()
    {
        $exchange = new Exchange(
            new Message(new SerializableArray(['value' => 5]))
        );

        $opts = $this->optionsResolver->resolve([
            ConfigurableWebserviceProtocol::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceProtocol::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceProtocol::EXCHANGE_PATTERN_IN_OUT,
        ]);

        $endpoint = new Endpoint('xxx', $opts, $this->protocol);
        $this->configurableProducer->send($exchange, $endpoint);

        $this->assertInstanceOf(SerializableArray::class, $exchange->getResult()->getBody());

        $this->assertEquals(
            (3 + 5 + 10),
            $exchange->getResult()->getBody()->get('result')
        );
    }

    public function testSendWithExchangePatternInOnlyRespectsMessage()
    {
        $in = new Message(new SerializableArray(['value' => 5]));

        $exchange = new Exchange($in);

        $opts = $this->optionsResolver->resolve([
            ConfigurableWebserviceProtocol::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceProtocol::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceProtocol::EXCHANGE_PATTERN_IN_ONLY,
        ]);

        $endpoint = new Endpoint('xxx', $opts, $this->protocol);
        $this->configurableProducer->send($exchange, $endpoint);

        $this->assertEquals(
            $in,
            $exchange->getResult()
        );
    }

    public function testValidationWorksWithUnrecoverableException()
    {
        $exchange = new Exchange(
            new Message(new SerializableArray(['value' => 1313666]))
        );

        $this->setExpectedException(ProducerUnrecoverableException::class, 'Too ugly number!!');

        $opts = $this->optionsResolver->resolve([
            ConfigurableWebserviceProtocol::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceProtocol::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceProtocol::EXCHANGE_PATTERN_IN_OUT,
        ]);

        $endpoint = new Endpoint('xxx', $opts, $this->protocol);

        $this->configurableProducer->send($exchange, $endpoint);
    }

    public function testValidationWorksWithRecoverableException()
    {
        $exchange = new Exchange(
            new Message(new SerializableArray(['value' => 666]))
        );

        $this->setExpectedException(ProducerRecoverableException::class, 'Ugly number!!');

        $opts = $this->optionsResolver->resolve([
            ConfigurableWebserviceProtocol::OPTION_METHOD => 'methodA',
            ConfigurableWebserviceProtocol::OPTION_EXCHANGE_PATTERN => ConfigurableWebserviceProtocol::EXCHANGE_PATTERN_IN_OUT,
        ]);

        $endpoint = new Endpoint('xxx', $opts, $this->protocol);
        $this->configurableProducer->send($exchange, $endpoint);
    }
}
