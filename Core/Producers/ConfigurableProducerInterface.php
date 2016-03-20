<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Producers;

use JMS\Serializer\SerializerInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;

interface ConfigurableProducerInterface extends ProducerInterface, ConfigurableInterface {

    /**
     * @param array $configuration
     * @return null
     */
    public function setMethodsConfiguration(array $configuration);

    /**
     * @param array $mappings
     * @return null
     */
    public function setOptions(array $mappings);

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