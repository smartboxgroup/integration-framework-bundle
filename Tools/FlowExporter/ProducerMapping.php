<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Tools\FlowExporter;

class ProducerMapping
{
    /**
     * @var string
     */
    protected $name;

    protected $methods = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string|array $mapping
     */
    public function addMethod(string $method, $mapping)
    {
        $this->methods[$method] = $mapping;
    }

    public function getMethod(string $name = null)
    {
        if ($name) {
            return $this->methods[$name] ?? null;
        }

        return $this->methods;
    }
}
