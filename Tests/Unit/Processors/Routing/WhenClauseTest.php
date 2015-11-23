<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;
use Smartbox\Integration\FrameworkBundle\Processors\Routing\WhenClause;

/**
 * Class WhenClauseTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Routing
 *
 * @coversDefaultClass Smartbox\Integration\FrameworkBundle\Processors\Routing\WhenClause
 */
class WhenClauseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Itinerary
     */
    private $itinerary;

    public function setUp()
    {
        $this->itinerary = new Itinerary();
    }

    /**
     * @return array
     */
    public function dataProviderForCreationOfWhenClause()
    {
        return [
            [null, null],
            [null, $this->itinerary],
            ['condition_1', null],
            ['condition_2', $this->itinerary],
            ['condition_3', $this->itinerary]
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getCondition
     * @covers ::getItinerary
     *
     * @dataProvider dataProviderForCreationOfWhenClause
     *
     * @param $condition
     * @param $itinerary
     */
    public function testCreationOfClass($condition, $itinerary)
    {
        $whenClause = new WhenClause($condition, $itinerary);

        $this->assertEquals($condition, $whenClause->getCondition());
        $this->assertSame($itinerary, $whenClause->getItinerary());
    }

    /**
     * @return array
     */
    public function dataProviderForCondition()
    {
        return [
            ['condition_1'],
            ['condition_2'],
        ];
    }

    /**
     *
     * @covers ::setCondition
     * @covers ::getCondition
     *
     * @dataProvider dataProviderForCondition
     *
     * @param $condition
     */
    public function testSetAndGetCondition($condition)
    {
        $whenClause = new WhenClause();
        $whenClause->setCondition($condition);

        $this->assertEquals($condition, $whenClause->getCondition());
    }

    /**
     * @return array
     */
    public function dataProviderForItinerary()
    {
        return [
            [new Itinerary()],
            [new Itinerary()],
        ];
    }

    /**
     * @covers ::setItinerary
     * @covers ::getItinerary
     *
     * @dataProvider dataProviderForItinerary
     *
     * @param Itinerary $itinerary
     */
    public function testSetAndGetItinerary(Itinerary $itinerary)
    {
        $whenClause = new WhenClause();
        $whenClause->setItinerary($itinerary);

        $this->assertSame($itinerary, $whenClause->getItinerary());
    }
}