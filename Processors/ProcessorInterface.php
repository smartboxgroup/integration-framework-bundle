<?php

namespace Smartbox\Integration\FrameworkBundle\Processors;

use Smartbox\Integration\FrameworkBundle\Messages\Exchange;

interface ProcessorInterface
{

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $description
     */
    public function setDescription($description);

    /**
     * @param Exchange $exchange
     * @return bool
     * @throws \Exception
     */
    public function process(Exchange $exchange);

    /**
     * @param bool $runtimeBreakpoint
     */
    public function setRuntimeBreakpoint($runtimeBreakpoint);
}
