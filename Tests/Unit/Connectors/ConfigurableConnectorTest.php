<?php
namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Connectors;


use Smartbox\Integration\FrameworkBundle\Connectors\ConfigurableConnector;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;

class ConfigurableConnectorTest extends BaseTestCase{

    /** @var  ConfigurableConnector */
    protected $configurableConnector;

    protected $defaultOptions = [
        'x' => 1,
        'y' => 2,
        'z' => [1,2,3]
    ];

    public function setUp()
    {
        $this->configurableConnector = $this->getMockBuilder(ConfigurableConnector::class)->setMethods([
            'request',
        ])->getMock();

        $this->configurableConnector->method('request')->willReturnCallback(
            function(array $stepActionParams, array $connectorOptions, array &$context){
                if (!is_array($stepActionParams)) {
                    throw new \InvalidArgumentException(
                        "Step 'request' in ConfigurableConnector expected an array as configuration"
                    );
                }

                if(!array_key_exists('name',$stepActionParams)){
                    throw new \InvalidArgumentException("Expected key name under configuration passed to ConfigurableConnector");
                }

                $context['responses'][$stepActionParams['name']] = 'XXX';
            }
        );

        $this->configurableConnector->setEvaluator($this->getContainer()->get('smartesb.util.evaluator'));
        $this->configurableConnector->setSerializer($this->getContainer()->get('serializer'));
        $this->configurableConnector->setDefaultOptions($this->defaultOptions);
    }

    public function testDefaultOptionsShouldBeSet()
    {
        $defaults = $this->configurableConnector->getDefaultOptions();

        foreach($this->defaultOptions as $defaultKey => $defaultValue){
            $this->assertContains($defaultKey,$defaults);
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

}