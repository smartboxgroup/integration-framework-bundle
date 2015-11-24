<?php
namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Helper;

use Smartbox\Integration\FrameworkBundle\Helper\EndpointsRegistry;

class EndpointRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function dataProviderForAsyncHandlersRegistry()
    {
        return [
            [[]],
            [
                ['uri_1' => 'handler_1', 'uri_2' => 'handler_2', 'uri_3' => 'handler_3']
            ],
        ];
    }

    /**
     * @dataProvider dataProviderForAsyncHandlersRegistry
     *
     * @param array $endpoints
     */
    public function testRegisterProcess(array $endpoints)
    {
        $registry = new EndpointsRegistry();

        foreach ($endpoints as $uri => $handler) {
            $registry->register($handler, $uri);
        }

        $this->assertEquals($endpoints, $registry->getRegisteredEndpoints());
        $this->assertEquals(array_keys($endpoints), $registry->getRegisteredEndpointsUris());
        $this->assertEquals(array_values($endpoints), $registry->getRegisteredEndpointsIds());
    }
}