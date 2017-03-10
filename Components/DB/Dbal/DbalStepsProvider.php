<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Dbal;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\NoResultException;
use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\Exceptions\NoResultsException;
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

    /** @var OptionsResolver */
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
     *
     * @return array
     * @throws NoResultsException
     */
    protected function performQuery(array $configuration, &$context)
    {
        $parameters = [];
        $parameterTypes = [];

        $params = $this->confHelper->resolve($configuration[self::CONF_PARAMETERS], $context);

        foreach ($params as $param => $info) {
            $value = null;
            if (isset($info['value'])) {
                $value = $info['value'];
            }

            $type = 'string';
            if (array_key_exists('type', $info)) {
                $type = $info['type'];
            }

            $parameters[$param] = $value;
            $parameterTypes[$param] = $type;
        }

        $sql = $this->confHelper->resolve($configuration[self::CONF_SQL], $context);

        /** @var PDOStatement $stmt */
        $stmt = $this->doctrine->getConnection()->executeQuery($sql, $parameters, $parameterTypes);

        if ($stmt->columnCount() > 0){ // SQL query is for example a SELECT
            $result = $stmt->fetchAll();
        } else { // SQL query is for example an UPDATE
            $result = ['count' => $stmt->rowCount()];
        }

        if (array_key_exists(self::CONF_QUERY_NAME, $configuration)) {
            $name = $configuration[self::CONF_QUERY_NAME];
            $context[self::CONTEXT_RESULTS][$name] = $result;
            if (count($result) == 0) {
                throw new NoResultsException("No results found for query named: ".$name);
            }
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
            $this->configResolver->setAllowedTypes(self::CONF_PARAMETERS, ['array', 'string']);
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
