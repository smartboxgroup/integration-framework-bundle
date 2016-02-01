<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Transformation;

use Smartbox\Integration\FrameworkBundle\Exceptions\ProcessingException;
use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Messages\Message;
use Smartbox\Integration\FrameworkBundle\Messages\MessageInterface;
use Smartbox\Integration\FrameworkBundle\Processors\Transformation\Transformer;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Serializables\Entity\SerializableSimpleEntity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class TransformerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Processors\Transformation
 */
class TransformerTest extends KernelTestCase
{
    /** @var Transformer */
    private $transformer;

    public function setUp()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcherMock */
        $eventDispatcherMock = $this->getMock(EventDispatcherInterface::class);

        $this->transformer = new Transformer();
        $this->transformer->setEventDispatcher($eventDispatcherMock);
        $this->transformer->setEvaluator($container->get('smartesb.util.evaluator'));
    }

    /**
     * @return array
     */
    public function dataProviderForValidTransformation()
    {
        $data = [];

        $entity = new SerializableSimpleEntity();
        $entity->setEntityGroup('common group');
        $entity->setAPIVersion('v1');
        $entity->setTitle('common title');
        $entity->setDescription('common description');
        $entity->setNote('common note');

        // use case for transforming DESCRIPTION of message body
        $inMessage1 = new Message(clone($entity));
        $transformedEntity1 = clone($entity);
        $transformedEntity1->setDescription('abc');
        $outMessage1 = new Message($transformedEntity1);
        $data[] = [$inMessage1, $outMessage1, 'msg.getBody().setDescription("abc")'];

        // use case for transforming TITLE and DESCRIPTION of message body
        $inMessage2 = new Message(clone($entity));
        $transformedEntity2 = clone($entity);
        $transformedEntity2->setTitle('transformed title');
        $transformedEntity2->setDescription('transformed description');
        $outMessage2 = new Message($transformedEntity2);
        $data[] = [$inMessage2, $outMessage2, 'msg.getBody().setTitle("transformed title") + msg.getBody().setDescription("transformed description")'];

        // use case for transforming TITLE and DESCRIPTION of message body
        $inMessage2 = new Message(clone($entity));
        $transformedEntity2 = clone($entity);
        $transformedEntity2->setTitle('title for v1');
        $outMessage2 = new Message($transformedEntity2);
        $data[] = [$inMessage2, $outMessage2, 'msg.getBody().getAPIVersion() == "v1" ? msg.getBody().setTitle("title for v1") : msg.getBody().setTitle("title for non v1")'];

        return $data;
    }

    /**
     * @dataProvider dataProviderForValidTransformation
     *
     * @param MessageInterface $inMessage
     * @param MessageInterface $outMessage
     * @param $expression
     */
    public function testItShouldChangeExchange(MessageInterface $inMessage, MessageInterface $outMessage, $expression)
    {
        $exchange = new Exchange($inMessage);

        $this->transformer->setExpression($expression);
        $this->transformer->process($exchange);

        $this->assertEquals($outMessage, $exchange->getResult(), 'Transformer should transform message according to given expression.');
    }

    /**
     * @return array
     */
    public function dataProviderForInvalidTransformation()
    {
        $data = [];

        $entity = new SerializableSimpleEntity();
        $entity->setEntityGroup('common group');
        $entity->setAPIVersion('v1');
        $entity->setTitle('common title');
        $entity->setDescription('common description');
        $entity->setNote('common note');

        // use case for invalid expression
        $inMessage1 = new Message(clone($entity));
        $data[] = [$inMessage1, 'this is invalid expression'];

        // use case for invocation of not existing method
        $inMessage2 = new Message(clone($entity));
        $data[] = [$inMessage2, 'msg.getBody().methodWhichNotExist("test")'];

        return $data;
    }

    /**
     * @dataProvider dataProviderForInvalidTransformation
     *
     * @param MessageInterface $inMessage
     * @param $expression
     * @throws \Exception
     */
    public function testItShouldThrowException(MessageInterface $inMessage, $expression)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $exchange = new Exchange($inMessage);

        $this->transformer->setExpression($expression);
        try{
            $this->transformer->process($exchange);
        }catch (ProcessingException $pe){
            throw $pe->getOriginalException();
        }
    }
}
