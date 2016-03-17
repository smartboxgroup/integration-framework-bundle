<?php

namespace Smartbox\Integration\FrameworkBundle\Producers;

use JMS\Serializer\SerializerInterface;
use Smartbox\Integration\FrameworkBundle\Util\ExpressionEvaluator;
use Smartbox\Integration\FrameworkBundle\Util\MapperInterface;

interface ConfigurableProducerInterface extends ProducerInterface {

    /**
     * @param array $configuration
     * @return null
     */
    public function setMethodsConfiguration(array $configuration);

    /**
     * @param array $mappings
     * @return null
     */
    public function setDefaultOptions(array $mappings);

    /**
     * @param ExpressionEvaluator $evaluator
     * @return null
     */
    public function setEvaluator(ExpressionEvaluator $evaluator);

    /**
     * @param SerializerInterface $serializer
     * @return null
     */
    public function setSerializer(SerializerInterface $serializer);

}