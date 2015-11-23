<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Connectors;

use Smartbox\Integration\FrameworkBundle\Connectors\JsonFileLoaderConnector;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;
use Smartbox\Integration\FrameworkBundle\Messages\Message;

class JsonFileLoaderConnectorTest extends BaseTestCase
{
    /** @var  JsonFileLoaderConnector */
    protected $connector;

    public function setUp(){
        parent::setUp();
        $this->connector = new JsonFileLoaderConnector();
        $this->connector->setSerializer(self::getContainer()->get('serializer'));
    }

    public function testSendShouldWork()
    {
        $pathFixtures = realpath(__DIR__.'/../../Fixtures');
        $exchange = new Exchange(new Message(new EntityX(1)));
        $this->connector->send($exchange, array(
            JsonFileLoaderConnector::OPTION_BASE_PATH => $pathFixtures,
            JsonFileLoaderConnector::OPTION_FILENAME => 'entity_x.json'
        ));

        $sample = new EntityX();
        $sample->setVersion('v1');
        $sample->setX(100);

        $content = $exchange->getResult()->getBody();
        $this->assertEquals($sample, $content);
    }
}
