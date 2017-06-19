<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Dbal;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Driver\PDOStatement;
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
    const STEP_EXECUTE_ALL = 'execute_all';
    const STEP_INSERT = 'insert';
    const STEP_EXECUTE_IF_CONDITION = 'execute_if';

    const CONF_PARAMETERS = 'parameters';
    const CONF_SQL = 'sql';
    const CONF_QUERY_NAME = 'name';
    const CONF_CONDITION = 'condition';

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
     * DbalStepsProvider constructor.
     */
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
            $this->configResolver->setDefined(self::CONF_CONDITION);
            $this->configResolver->setAllowedTypes(self::CONF_SQL, ['string']);
            $this->configResolver->setAllowedTypes(self::CONF_PARAMETERS, ['array', 'string']);
            $this->configResolver->setAllowedTypes(self::CONF_CONDITION, ['string']);
        }
    }

    /**
     * {@inheritdoc}
     */
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
                $this->execute($stepActionParams, $context);

                return true;
            case self::STEP_EXECUTE_ALL:
                $stepActionParams = $this->configResolver->resolve($stepActionParams);
                $this->executeAll($stepActionParams, $context);

                return true;
            case self::STEP_INSERT:
                $stepActionParams = $this->configResolver->resolve($stepActionParams);
                $this->insert($stepActionParams, $context);

                return true;
            case self::STEP_EXECUTE_IF_CONDITION:
                $stepActionParams = $this->configResolver->resolve($stepActionParams);
                $this->executeIfConditionMatch($stepActionParams, $context);

                return true;
            default:
                return false;
        }
    }

    /**
     * @param array $configuration
     * @param $context
     *
     * @return array
     */
    protected function execute(array $configuration, &$context)
    {
        $parameters = $this->confHelper->resolve($configuration[self::CONF_PARAMETERS], $context);
        $parameters = $this->prepareParameters($parameters);

        $sql = $this->confHelper->resolve($configuration[self::CONF_SQL], $context);

        return $this->performQuery($configuration, $context, $parameters, $sql);
    }

    /**
     * @param array $configuration
     * @param $context
     *
     * @return array
     */
    protected function executeAll(array $configuration, &$context)
    {
        $parameters = $this->confHelper->resolve($configuration[self::CONF_PARAMETERS], $context);
        $sql = $this->confHelper->resolve($configuration[self::CONF_SQL], $context);

        $results = [];

        foreach ($parameters as $params) {
            $params = $this->prepareParameters($params);
            $results[] = $this->performQuery($configuration, $context, $params, $sql);
        }

        return $results;
    }

    /**
     * @param array $configuration
     * @param $context
     *
     * @return array
     */
    protected function insert(array $configuration, &$context)
    {
        $tuples = [];
        $parameters = [];

        $rows = $this->confHelper->resolve($configuration[self::CONF_PARAMETERS], $context);
        foreach ($rows as $key => $params) {
            $params = $this->prepareParameters($params, $key);
            $tuples[] = '('.implode(',', $params['names']).')';
            $parameters = array_merge_recursive($parameters, $params);
        }

        $sql = $this->confHelper->resolve($configuration[self::CONF_SQL], $context);
        $sql = str_replace(':values', implode(',', $tuples), $sql);

        return $this->performQuery($configuration, $context, $parameters, $sql);
    }

    /**
     * @param array $configuration
     * @param $context
     *
     * @return array
     */
    public function executeIfConditionMatch(array $configuration, &$context)
    {
        $condition = $this->confHelper->resolve($configuration[self::CONF_CONDITION], $context);

        if ($condition) {
            return $this->execute($configuration, $context);
        }
    }

    /**
     * @param array  $params
     * @param string $suffix
     *
     * @return array
     */
    protected function prepareParameters(array $params, $suffix = '')
    {
        $parameters = [
            'names' => [],
            'values' => [],
            'types' => [],
        ];

        foreach ($params as $name => $info) {
            $name = $name.$suffix;

            $value = null;
            if (isset($info['value'])) {
                $value = $info['value'];
            }

            $type = 'string';
            if (array_key_exists('type', $info)) {
                $type = $info['type'];
            }

            $parameters['names'][] = ":$name";
            $parameters['values'][$name] = $value;
            $parameters['types'][$name] = $type;
        }

        return $parameters;
    }

    /**
     * @param array $configuration
     * @param $context
     * @param array $parameters
     * @param $sql
     *
     * @return array
     *
     * @throws NoResultsException
     */
    protected function performQuery(array $configuration, &$context, array $parameters, $sql)
    {
        /** @var PDOStatement $stmt */
        $stmt = $this->getConnection($context)->executeQuery($sql, $parameters['values'], $parameters['types']);

        if ($stmt->columnCount() > 0) { // SQL query is for example a SELECT
            $result = $stmt->fetchAll();
        } else { // SQL query is for example an UPDATE
            $result = ['count' => $stmt->rowCount()];
        }

        if (array_key_exists(self::CONF_QUERY_NAME, $configuration)) {
            $name = $configuration[self::CONF_QUERY_NAME];
            $context[self::CONTEXT_RESULTS][$name] = $result;
            if (count($result) == 0) {
                throw new NoResultsException('No results found for query named: '.$name);
            }
        }

        return $result;
    }

    /**
     * @param $context
     * @return object
     */
    protected function getConnection($context)
    {
        return $this->doctrine->getConnection();
    }
}
