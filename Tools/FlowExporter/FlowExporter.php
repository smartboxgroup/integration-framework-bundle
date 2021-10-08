<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tools\FlowExporter;

use Smartbox\Integration\FrameworkBundle\Components\WebService\AbstractWebServiceProducer;
use Smartbox\Integration\FrameworkBundle\Components\WebService\Rest\RestConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FlowExporter implements ContainerAwareInterface
{
    use UsesEvaluator;
    use UsesConfigurableServiceHelper;

    /**
     * @var array
     */
    protected $producers;

    /**
     * @var array
     */
    protected $mappings;
    /**
     * @var ContainerInterface|null
     *
     * @TODO replace with service locator
     */
    protected $container;

    public function addProducers(array $producers)
    {
        $this->producers = $producers;
    }

    public function addMappings(array $mappings)
    {
        $this->mappings = $mappings;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getAvailableProducers()
    {
        return $this->producers;
    }

    public function getProducerMappings(string $producer = null)
    {
        $this->injectUnmapper();

        if (false === array_search($producer, $this->producers, true)) {
            throw new \LogicException(sprintf('[FlowExporter] No producer named %s available', $producer));
        }

        return $this->buildProducer($this->container->get($producer));
    }

    protected function buildProducer(AbstractWebServiceProducer $producer): ProducerMapping
    {
        $mapping = new ProducerMapping($producer->getName());

        foreach ($producer->getMethodsConfiguration() as $name => $definition) {
            $method = new ProducerMethod($name, $definition);

            $defineSteps = $method->getDefineSteps();
            $flattened = [];
            array_walk_recursive($defineSteps, function ($a, $b) use (&$flattened) {
                $flattened[$b] = $a;
            });

            $request = $method->getRequestStep();

            // @TODO on soap is "Parameters"
            $body = $request[RestConfigurableProducer::REQUEST_BODY];
            $mapping->addMethod($method->getName(), $this->getConfHelper()->evaluateStringOrExpression(!is_array($body) ? $body : reset($body), $flattened));
        }

        return $mapping;
    }

    protected function injectUnmapper()
    {
        $unmapper = new Unmapper();
        $unmapper->addMappings($this->mappings);
        $this->evaluator->setMapper($unmapper);
    }
}
