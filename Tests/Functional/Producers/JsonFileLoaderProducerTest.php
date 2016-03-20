<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Functional\Producers;

use Smartbox\Integration\FrameworkBundle\Components\JsonFileLoader\JsonFileLoaderProducer;
use Smartbox\Integration\FrameworkBundle\Components\JsonFileLoader\JsonFileLoaderProtocol;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Tests\EntityX;
use Smartbox\Integration\FrameworkBundle\Tests\Functional\BaseTestCase;

class JsonFileLoaderProducerTest extends BaseTestCase
{
    /** @var  JsonFileLoaderProducer */
    protected $producer;

    public function setUp(){
        parent::setUp();
        $this->producer = new \Smartbox\Integration\FrameworkBundle\Components\JsonFileLoader\JsonFileLoaderProducer();
        $this->producer->setSerializer(self::getContainer()->get('serializer'));
    }

    public function testSendShouldWork()
    {
        $pathFixtures = realpath(__DIR__.'/../../Fixtures');
        $exchange = new Exchange($this->createMessage(new EntityX(1)));

        $opts = [
            JsonFileLoaderProducer::OPTION_BASE_PATH => $pathFixtures,
            JsonFileLoaderProducer::OPTION_FILENAME => 'entity_x.json'
        ];

        $this->producer->send($exchange,new Endpoint("xxx",$opts,new JsonFileLoaderProtocol()));

        $sample = new EntityX();
        $sample->setAPIVersion('v1');
        $sample->setX(100);

        $content = $exchange->getResult()->getBody();
        $this->assertEquals($sample, $content);
    }
}
