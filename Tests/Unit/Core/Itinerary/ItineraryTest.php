<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Itinerary;

use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;

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
    public function dataProviderForProcessorIds()
    {
        return [
            [['id_1']],
            [['id_1', 'id_2']],
            [['id_1', 'id_2', 'id_3']],
        ];
    }

    /**
     * @covers ::addProcessorId
     * @covers ::getProcessorIds
     *
     * @dataProvider dataProviderForProcessorIds
     *
     * @param array $processorIds
     */
    public function testAddProcessorId(array $processorIds)
    {
        foreach ($processorIds as $processorId) {
            $this->itinerary->addProcessorId($processorId);
        }

        $this->assertEquals($processorIds, $this->itinerary->getProcessorIds());
    }

    public function testAddProcessorIdWhenItIsNotString()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->itinerary->addProcessorId(new \StdClass());
    }

    /**
     * @covers ::setProcessorIds
     * @covers ::getProcessorIds
     *
     * @dataProvider dataProviderForProcessorIds
     *
     * @param array $processorIds
     */
    public function testSetProcessorIds(array $processorIds)
    {
        $this->itinerary->addProcessorId('previous_added_processor');
        $this->itinerary->setProcessorIds($processorIds);

        $this->assertEquals($processorIds, $this->itinerary->getProcessorIds());
    }

    /**
     * @covers ::setProcessorIds
     * @covers ::getProcessorIds
     *
     * @dataProvider dataProviderForProcessorIds
     *
     * @param array $processorIds
     */
    public function testGetProcessorIds(array $processorIds)
    {
        $this->itinerary->setProcessorIds($processorIds);

        $this->assertEquals($processorIds, $this->itinerary->getProcessorIds());
    }

    /**
     * @covers ::append
     * @covers ::setProcessorIds
     * @covers ::getProcessorIds
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
        $itinerary1->setProcessorIds([
            $processor1,
            $processor2,
        ]);

        // initialize itinerary 2
        $itinerary2 = new Itinerary();
        $itinerary2->setProcessorIds([
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
            $itinerary1->getProcessorIds()
        );
    }

    /**
     * @covers ::prepend
     * @covers ::setProcessorIds
     * @covers ::getProcessorIds
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
        $itinerary1->setProcessorIds([
            $processor1,
            $processor2,
        ]);

        // initialize itinerary 2
        $itinerary2 = new Itinerary();
        $itinerary2->setProcessorIds([
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
            $itinerary1->getProcessorIds()
        );
    }

    /**
     * @covers ::setProcessorIds
     * @covers ::shiftProcessorId
     *
     * @dataProvider dataProviderForProcessorIds
     *
     * @param array $processorIds
     */
    public function testShiftProcessor(array $processorIds)
    {
        $this->itinerary->setProcessorIds($processorIds);

        $this->assertEquals($processorIds[0], $this->itinerary->shiftProcessorId());
        $this->assertSame(array_slice($processorIds, 1, count($processorIds) - 1), $this->itinerary->getProcessorIds());
    }

    /**
     * @covers ::setProcessorIds
     * @covers ::unShiftProcessorId
     *
     * @dataProvider dataProviderForProcessorIds
     *
     * @param array $processorIds
     */
    public function testUnShiftProcessorId(array $processorIds)
    {
        $this->itinerary->setProcessorIds($processorIds);

        $this->itinerary->unShiftProcessorId('new_processor');
        $this->assertSame(array_merge(['new_processor'], $processorIds), $this->itinerary->getProcessorIds());
    }

    public function testUnShiftProcessorIdWhenItIsNotString()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->itinerary->unShiftProcessorId(new \StdClass());
    }
}
