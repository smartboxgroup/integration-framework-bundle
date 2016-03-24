<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Processors;

use Smartbox\Integration\FrameworkBundle\Core\Exchange;

/**
 * Interface ProcessorInterface.
 */
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
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function process(Exchange $exchange);

    /**
     * @param bool $runtimeBreakpoint
     */
    public function setRuntimeBreakpoint($runtimeBreakpoint);
}
