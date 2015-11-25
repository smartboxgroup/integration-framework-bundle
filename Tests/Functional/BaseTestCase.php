<?php


namespace Smartbox\Integration\FrameworkBundle\Tests\Functional;

use Smartbox\Integration\FrameworkBundle\Handlers\MessageHandler;
use Smartbox\Integration\FrameworkBundle\Connectors\APIConnector;
use Smartbox\Integration\FrameworkBundle\Connectors\DirectConnector;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Processors\SpyProcessor;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\Traits\UsesEventDispatcher;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\Traits\UsesValidator;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

// @todo move cration methods to factory
abstract class BaseTestCase extends KernelTestCase
{
    public static function getKernelClass(){
        return AppKernel::class;
    }

    /**
     * @return Container
     */
    protected function getContainer()
    {
        return self::$kernel->getContainer();
    }

    public function setUp()
    {
        $this->bootKernel();
    }

    /**
     * @return APIConnector
     */
    public function createAPIConnector()
    {
        return $this->createBasicService(APIConnector::class);
    }

    /**
     * @return DirectConnector
     */
    public function createDirectConnector()
    {
        return $this->createBasicService(DirectConnector::class);
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
}