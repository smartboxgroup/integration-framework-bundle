<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;

use JMS\Serializer\SerializerInterface;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Evaluator\ExpressionEvaluator;

interface ConfigurableConsumerInterface extends ConsumerInterface, ConfigurableInterface
{
    const CONFIG_ON_CONSUME = 'on_consume';
    const CONFIG_QUERY_STEPS = 'query_steps';
    const CONFIG_QUERY_RESULT = 'query_result';

    /**
     * @param array $configuration
     */
    public function setMethodsConfiguration(array $configuration);

    /**
     * @param array $mappings
     */
    public function setOptions(array $mappings);

    /**
     * @param ExpressionEvaluator $evaluator
     */
    public function setEvaluator(ExpressionEvaluator $evaluator);

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer);
}
