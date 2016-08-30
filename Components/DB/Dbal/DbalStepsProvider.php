<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Dbal;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Statement;
use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DbalStepsProvider extends Service implements ConfigurableStepsProviderInterface
{
    use UsesConfigurableServiceHelper;

    const CONTEXT_RESULTS = 'results';
    const CONTEXT_VARS = 'vars';

    /** @var Registry */
    protected $doctrine;

    const STEP_EXECUTE = 'execute';

    const CONF_PARAMETERS = 'parameters';
    const CONF_SQL = 'sql';
    const CONF_QUERY_NAME = 'name';

    /** @var  OptionsResolver */
    protected $configResolver;

    /**
     * @return Registry
     */
    public function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function executeStep($stepAction, array &$stepActionParams, array &$options, array &$context)
    {
        if ($this->doctrine === null) {
            throw new \InvalidArgumentException('Doctrine should be installed to use DbalStepsProvider');
        }

        $handled = $this->getConfHelper()->executeStep($stepAction, $stepActionParams, $options, $context);

        if ($handled) {
            return true;
        }

        switch ($stepAction) {
            case self::STEP_EXECUTE:
                $stepActionParams = $this->configResolver->resolve($stepActionParams);
                $this->performQuery($stepActionParams, $context);

                return true;
                break;

            default:
                return false;
                break;
        }
    }

    /**
     * @param array $configuration
     * @param $context
     * @return Statement
     * @internal param array $endpointOptions
     */
    protected function performQuery(array $configuration, &$context)
    {
        $parameters = [];
        $parameterTypes = [];

        foreach ($configuration[self::CONF_PARAMETERS] as $param => $info) {
            $value = $this->confHelper->resolve($info['value'], $context);
            if ($value === null) {
                throw new \RuntimeException(
                    "Error while trying to execute query in DbalStepsProvider, null value found for query parameter: '$param'"
                );
            }

            if(array_key_exists('type',$info)){
                $type = $info['type'];
            }else{
                $type = 'string';
            }

            $parameters[$param] = $value;
            $parameterTypes[$param] = $type;
        }

        $sql = $this->confHelper->resolve($configuration[self::CONF_SQL], $context);

        $result = $this->doctrine->getConnection()->executeQuery($sql, $parameters, $parameterTypes);

        if (array_key_exists(self::CONF_QUERY_NAME, $configuration)) {
            $name = $configuration[self::CONF_QUERY_NAME];
            $context[self::CONTEXT_RESULTS][$name] = $result;
        }

        return $result;
    }

    public function __construct()
    {
        parent::__construct();

        if (!$this->configResolver) {
            $this->configResolver = new OptionsResolver();
            $this->configResolver->setRequired([self::CONF_SQL, self::CONF_PARAMETERS]);
            $this->configResolver->setDefaults(
                [
                    self::CONF_PARAMETERS => [],
                ]
            );
            $this->configResolver->setDefined(self::CONF_QUERY_NAME);
            $this->configResolver->setAllowedTypes(self::CONF_SQL, ['string']);
            $this->configResolver->setAllowedTypes(self::CONF_PARAMETERS, ['array']);
        }
    }


    public function executeSteps(array $stepsConfig, array &$options, array &$context)
    {

        if (!array_key_exists(self::CONTEXT_RESULTS, $context)) {
            $context[self::CONTEXT_RESULTS] = [];
        }

        if (!array_key_exists(self::CONTEXT_VARS, $context)) {
            $context[self::CONTEXT_VARS] = [];
        }

        foreach ($stepsConfig as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $options, $context);
            }
        }
    }
}