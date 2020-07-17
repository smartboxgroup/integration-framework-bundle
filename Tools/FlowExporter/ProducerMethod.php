<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tools\FlowExporter;

use Smartbox\Integration\FrameworkBundle\Core\Producers\ConfigurableProducerInterface;

class ProducerMethod
{
    /**
     * @var array
     */
    protected $definition;
    /**
     * @var string
     */
    protected $name;

    public function __construct(string $name, array $definition)
    {
        $this->definition = $definition;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getRequestStep(): array
    {
        foreach ($this->definition[ConfigurableProducerInterface::CONF_STEPS] as $step) {
            if (isset($step['request'])) {
                return $step['request'];
            }
        }

        throw new \LogicException('[Producer Method Parser] Method definition has no step named "request"');
    }

    public function getDefineSteps()
    {
        $steps = [];

        foreach ($this->definition[ConfigurableProducerInterface::CONF_STEPS] as $step) {
            if (isset($step['define'])) {
                $steps[] = $step['define'];
            }
        }

        return $steps ?? null;
    }
}
