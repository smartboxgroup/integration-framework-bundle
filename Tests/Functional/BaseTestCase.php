<?php


namespace Smartbox\Integration\FrameworkBundle\Tests\Functional;

use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Producers\DirectProducer;
use Smartbox\Integration\FrameworkBundle\Messages\Context;
use Smartbox\Integration\FrameworkBundle\Tests\BaseKernelTestCase;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\SpyProcessor;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Traits\UsesValidator;
use Smartbox\Integration\FrameworkBundle\Service;

abstract class BaseTestCase extends BaseKernelTestCase
{
    /**
     * @return DirectProducer
     */
    public function createDirectProducer()
    {
        return $this->createBasicService(DirectProducer::class);
    }

    function class_uses_deep($class, $autoload = true)
    {
        $traits = [];
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));
        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }

        return array_unique($traits);
    }

    /**
     * @return SpyProcessor
     */
    public function createSpy(){
        return $this->createBasicService(SpyProcessor::class);
    }

    /**
     * Creates a basic service definition for the given class, injecting the typical dependencies according with the
     * traits the class uses.
     *
     * @return Service
     */
    public function createBasicService($class)
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("$class is not a valid class name");
        }

        if (!is_subclass_of($class, 'Smartbox\Integration\FrameworkBundle\Service')) {
            throw new \InvalidArgumentException($class.' does not extend Smartbox\Integration\FrameworkBundle\Service');
        }

        /** @var Service $instance */
        $instance = new $class();
        $id = 'mock.service.'.uniqid();
        $instance->setId($id);

        $traits = $this->class_uses_deep($class);
        foreach ($traits as $trait) {
            switch ($trait) {
                case (UsesEvaluator::class):
                    $instance->setEvaluator($this->getContainer()->get('smartesb.util.evaluator'));
                    break;

                case (UsesSerializer::class):
                    $instance->setSerializer($this->getContainer()->get('serializer'));
                    break;

                case (UsesValidator::class):
                    $instance->setValidator($this->getContainer()->get('validator'));
                    break;

                case (UsesEventDispatcher::class):
                    $instance->setEventDispatcher($this->getContainer()->get('event_dispatcher'));
                    break;
            }
        }

        $this->getContainer()->set($id,$instance);

        return $instance;
    }

    /**
     * @param SerializableInterface $body
     * @param array $headers
     * @param Context $context
     * @return \Smartbox\Integration\FrameworkBundle\Messages\Message
     */
    protected function createMessage(SerializableInterface $body = null, $headers = array(), Context $context = null){
        return $this->getContainer()->get('smartesb.message_factory')->createMessage($body,$headers,$context);
    }
}