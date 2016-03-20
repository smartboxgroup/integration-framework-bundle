<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors;

use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\FakeProcessor;

/**
 * Class ItineraryTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary
 */
class ItineraryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary
     */
    private $itinerary;

    public function setUp()
    {
        $this->itinerary = new \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary();
    }

    /**
     * @return array
     */
    public function dataProviderForProcessors()
    {
        return [
            [[new FakeProcessor('id_1')]],
            [[new FakeProcessor('id_1'), new FakeProcessor('id_2')]],
            [[new FakeProcessor('id_1'), new FakeProcessor('id_2'), new FakeProcessor('id_3')]],
        ];
    }

    /**
     * @covers ::addProcessor
     * @covers ::getProcessors
     * @dataProvider dataProviderForProcessors
     *
     * @param Processor[] $processors
     */
    public function testAddProcessor(array $processors)
    {
        foreach ($processors as $processor) {
            $this->itinerary->addProcessor($processor);
        }

        $this->assertEquals($processors, $this->itinerary->getProcessors());
    }

    /**
     * @covers ::setProcessors
     * @covers ::getProcessors
     * @dataProvider dataProviderForProcessors
     *
     * @param Processor[] $processors
     */
    public function testAddProcessors(array $processors)
    {
        $this->itinerary->addProcessor(new FakeProcessor('previous_added_processor'));
        $this->itinerary->setProcessors($processors);

        $this->assertEquals($processors, $this->itinerary->getProcessors());
    }

    /**
     * @covers ::setProcessors
     * @covers ::getProcessorIds
     */
    public function testGetProcessorIds()
    {
        $processors = [
            new FakeProcessor('id_1'),
            new FakeProcessor('id_2'),
            new FakeProcessor('id_3'),
            new FakeProcessor('id_4'),
        ];
        $processorIds = [
            'id_1',
            'id_2',
            'id_3',
            'id_4',
        ];

        $this->itinerary->setProcessors($processors);

        $this->assertEquals($processorIds, $this->itinerary->getProcessorIds());
    }

    /**
     * @covers ::setProcessors
     * @covers ::getProcessors
     *
     * @dataProvider dataProviderForProcessors
     *
     * @param Processor[] $processors
     */
    public function testGetProcessors(array $processors)
    {
        $this->itinerary->setProcessors($processors);

        $this->assertEquals($processors, $this->itinerary->getProcessors());
    }

    /**
     * @covers ::append
     * @covers ::setProcessors
     * @covers ::getProcessors
     */
    public function testAppend()
    {
        $processor1 = new FakeProcessor('processor_1');
        $processor2 = new FakeProcessor('processor_2');
        $processor3 = new FakeProcessor('processor_3');
        $processor4 = new FakeProcessor('processor_4');
        $processor5 = new FakeProcessor('processor_5');

        // initialize itinerary 1
        $itinerary1 = new \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary();
        $itinerary1->setProcessors([
            $processor1,
            $processor2,
        ]);

        // initialize itinerary 2
        $itinerary2 = new \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary();
        $itinerary2->setProcessors([
            $processor3,
            $processor4,
            $processor5,
        ]);

        $itinerary1->append($itinerary2);

        $this->assertEquals(
            [
                $processor1,
                $processor2,
                $processor3,
                $processor4,
                $processor5,
            ],
            $itinerary1->getProcessors()
        );
    }

    /**
     * @covers ::prepend
     * @covers ::setProcessors
     * @covers ::getProcessors
     */
    public function testPrepend()
    {
        $processor1 = new FakeProcessor('processor_1');
        $processor2 = new FakeProcessor('processor_2');
        $processor3 = new FakeProcessor('processor_3');
        $processor4 = new FakeProcessor('processor_4');
        $processor5 = new FakeProcessor('processor_5');

        // initialize itinerary 1
        $itinerary1 = new \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary();
        $itinerary1->setProcessors([
            $processor1,
            $processor2,
        ]);

        // initialize itinerary 2
        $itinerary2 = new \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary();
        $itinerary2->setProcessors([
            $processor3,
            $processor4,
            $processor5,
        ]);

        $itinerary1->prepend($itinerary2);

        $this->assertEquals(
            [
                $processor3,
                $processor4,
                $processor5,
                $processor1,
                $processor2,
            ],
            $itinerary1->getProcessors()
        );
    }

    /**
     * @covers ::setProcessors
     * @covers ::shiftProcessor
     *
     * @dataProvider dataProviderForProcessors
     *
     * @param Processor[] $processors
     */
    public function testShiftProcessor(array $processors)
    {
        $this->itinerary->setProcessors($processors);

        $this->assertEquals($processors[0], $this->itinerary->shiftProcessor());
        $this->assertSame(array_slice($processors, 1, count($processors) - 1), $this->itinerary->getProcessors());
    }
}