<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB;


interface ConfigurableStepsProviderInterface {

    /**
     * Execute the given steps
     *
     * @param array $stepsConfig
     * @param array $options
     * @param array $context
     */
    public function executeSteps(array $stepsConfig, array &$options, array &$context);

    /**
     * Returns true if the step was executed, false if the step was not recognized.
     *
     * @param string $stepAction
     * @param array $stepActionParams
     * @param array $options
     * @param array $context
     * @return boolean
     */
    public function executeStep($stepAction, array &$stepActionParams, array &$options, array &$context);

}