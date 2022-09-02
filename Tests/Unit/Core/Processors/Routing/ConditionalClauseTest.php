<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Core\Processors\Routing;

use Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary;
use Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ConditionalClause;

/**
 * Class ConditionalClauseTest.
 *
 * @coversDefaultClass \Smartbox\Integration\FrameworkBundle\Core\Processors\Routing\ConditionalClause
 */
class ConditionalClauseTest extends \PHPUnit\Framework\TestCase
{
    /** @var Itinerary */
    private $itinerary;

    protected function setUp(): void
    {
        $this->itinerary = new Itinerary();
    }

    protected function tearDown(): void
    {
        $this->itinerary = null;
    }

    /**
     * @return array
     */
    public function dataProviderForCreationOfConditionalClause()
    {
        return [
            [null, null],
            [null, $this->itinerary],
            ['condition_1', null],
            ['condition_2', $this->itinerary],
            ['condition_3', $this->itinerary],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getCondition
     * @covers ::getItinerary
     *
     * @dataProvider dataProviderForCreationOfConditionalClause
     *
     * @param $condition
     * @param $itinerary
     */
    public function testCreationOfClass($condition, $itinerary)
    {
        $conditionalClause = new ConditionalClause($condition, $itinerary);

        $this->assertEquals($condition, $conditionalClause->getCondition());
        $this->assertSame($itinerary, $conditionalClause->getItinerary());
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
     * @covers ::setCondition
     * @covers ::getCondition
     *
     * @dataProvider dataProviderForCondition
     *
     * @param $condition
     */
    public function testSetAndGetCondition($condition)
    {
        $conditionalClause = new ConditionalClause();
        $conditionalClause->setCondition($condition);

        $this->assertEquals($condition, $conditionalClause->getCondition());
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
     * @param \Smartbox\Integration\FrameworkBundle\Core\Itinerary\Itinerary $itinerary
     */
    public function testSetAndGetItinerary(Itinerary $itinerary)
    {
        $conditionalClause = new ConditionalClause();
        $conditionalClause->setItinerary($itinerary);

        $this->assertSame($itinerary, $conditionalClause->getItinerary());
    }
}
