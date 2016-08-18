<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Itinerary;

use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Processor;

/**
 * Class ItineraryTest.
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary
 */
class ItineraryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Itinerary */
    private $itinerary;

    protected function setUp()
    {
        $this->itinerary = new Itinerary();
    }

    protected function tearDown()
    {
        $this->itinerary = null;
    }

    /**
     * @return array
     */
    public function dataProviderForProcessors()
    {
        return [
            [['id_1']],
            [['id_1', 'id_2']],
            [['id_1', 'id_2', 'id_3']],
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
        $this->itinerary->addProcessor('previous_added_processor');
        $this->itinerary->setProcessors($processors);

        $this->assertEquals($processors, $this->itinerary->getProcessors());
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
        $processor1 = 'processor_1';
        $processor2 = 'processor_2';
        $processor3 = 'processor_3';
        $processor4 = 'processor_4';
        $processor5 = 'processor_5';

        // initialize itinerary 1
        $itinerary1 = new Itinerary();
        $itinerary1->setProcessors([
            $processor1,
            $processor2,
        ]);

        // initialize itinerary 2
        $itinerary2 = new Itinerary();
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
        $processor1 = 'processor_1';
        $processor2 = 'processor_2';
        $processor3 = 'processor_3';
        $processor4 = 'processor_4';
        $processor5 = 'processor_5';

        // initialize itinerary 1
        $itinerary1 = new Itinerary();
        $itinerary1->setProcessors([
            $processor1,
            $processor2,
        ]);

        // initialize itinerary 2
        $itinerary2 = new Itinerary();
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
