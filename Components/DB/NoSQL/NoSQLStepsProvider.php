<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL;

use Smartbox\Integration\FrameworkBundle\Components\DB\ConfigurableStepsProviderInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\QueryOptions;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesDriverRegistry;
use Smartbox\Integration\FrameworkBundle\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoSQLStepsProvider extends Service implements ConfigurableStepsProviderInterface
{
    use UsesDriverRegistry;
    use UsesConfigurableServiceHelper;

    // Context vars
    const CONTEXT_RESULTS = 'results';
    const CONTEXT_VARS = 'vars';

    const CONF_RESULT = 'result';

    // Conf for queries
    const CONF_QUERY = 'query';
    const CONF_LIMIT = 'limit';
    const CONF_OFFSET = 'offset';
    const CONF_SORT = 'sort';
    const CONF_NAME = 'name';

    // Conf for insert/update
    const CONF_DATA = 'data';
    const CONF_TRANSFORMATION = 'transformation';

    /** @var OptionsResolver */
    protected $findOptionsResolver;

    /** @var OptionsResolver */
    protected $insertOptionsResolver;

    /** @var OptionsResolver */
    protected $deleteOptionsResolver;

    /** @var OptionsResolver */
    protected $updateOptionsResolver;

    // Available steps
    const STEP_UPDATE = 'update';
    const STEP_FIND = 'find';
    const STEP_DELETE = 'delete';
    const STEP_INSERT_MANY = 'insertMany';
    const STEP_INSERT_ONE = 'insertOne';

    protected function configureQueryOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined([self::CONF_LIMIT, self::CONF_OFFSET, self::CONF_SORT]);
        $resolver->setRequired(self::CONF_QUERY);

        $resolver->setAllowedTypes(self::CONF_LIMIT, ['integer']);
        $resolver->setAllowedTypes(self::CONF_OFFSET, ['integer']);
        $resolver->setAllowedTypes(self::CONF_QUERY, ['array']);
        $resolver->setAllowedTypes(self::CONF_SORT, ['array']);
    }

    /**
     * @param array $params
     * @param array $context
     *
     * @return QueryOptions
     *
     * @throws \Exception
     */
    protected function prepareQueryOptions(array $params, array &$context)
    {
        $query = $this->confHelper->resolve($params[self::CONF_QUERY], $context);

        if ($query === null) {
            throw new \RuntimeException(
                "Error while trying to consume using NoSQLConsumer, null value found for query param $query"
            );
        }

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams($query);

        if (array_key_exists(self::CONF_SORT, $params)) {
            $sort = $this->confHelper->resolve($params[self::CONF_SORT], $context);
            foreach ($sort as $field => $order) {
                $queryOptions->addSorting($field, $order);
            }
        }
        if (array_key_exists(self::CONF_LIMIT, $params)) {
            $queryOptions->setLimit($params[self::CONF_LIMIT]);
        }

        if (array_key_exists(self::CONF_OFFSET, $params)) {
            $queryOptions->setOffset($params[self::CONF_OFFSET]);
        }

        return $queryOptions;
    }

    public function __construct()
    {
        parent::__construct();

        if (!$this->findOptionsResolver) {
            $this->findOptionsResolver = new OptionsResolver();
            $this->configureQueryOptions($this->findOptionsResolver);
            $this->findOptionsResolver->setRequired(self::CONF_NAME);

            $this->deleteOptionsResolver = new OptionsResolver();
            $this->configureQueryOptions($this->deleteOptionsResolver);

            $this->updateOptionsResolver = new OptionsResolver();
            $this->configureQueryOptions($this->updateOptionsResolver);
            $this->updateOptionsResolver->setRequired(self::CONF_TRANSFORMATION);

            $this->insertOptionsResolver = new OptionsResolver();
            $this->insertOptionsResolver->setRequired(self::CONF_DATA);
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

    public function executeStep($stepAction, array &$stepActionParams, array &$options, array &$context)
    {
        $handled = $this->getConfHelper()->executeStep($stepAction, $stepActionParams, $options, $context);

        if ($handled) {
            return true;
        }

        $collection = $options[NoSQLConfigurableProtocol::OPTION_COLLECTION_PREFIX].$options[NoSQLConfigurableProtocol::OPTION_COLLECTION_NAME];

        $driver = $this->resolveDriver($options);

        switch ($stepAction) {
            case self::STEP_FIND:
                $this->performFind($driver, $collection, $stepActionParams, $context);

                return true;
                break;

            case self::STEP_UPDATE:
                $this->performUpdate($driver, $collection, $stepActionParams, $context);

                return true;
                break;

            case self::STEP_DELETE:
                $this->performDelete($driver, $collection, $stepActionParams, $context);

                return true;
                break;

            case self::STEP_INSERT_ONE:
                $this->performInsertOne($driver, $collection, $stepActionParams, $context);

                return true;
                break;

            case self::STEP_INSERT_MANY:
                $this->performInsertMany($driver, $collection, $stepActionParams, $context);

                return true;
                break;

            default:
                return false;
                break;
        }
    }

    public function performFind(NoSQLDriverInterface $driver, $collection, $configuration, &$context)
    {
        $params = $this->findOptionsResolver->resolve($configuration);
        $queryOptions = $this->prepareQueryOptions($params, $context);
        $name = $this->getConfHelper()->resolve($params[self::CONF_NAME], $context);
        $results = $driver->find($collection, $queryOptions);

        $context[self::CONTEXT_RESULTS][$name] = $results;
    }

    public function performUpdate(NoSQLDriverInterface $driver, $collection, $configuration, $context)
    {
        $params = $this->updateOptionsResolver->resolve($configuration);
        $queryOptions = $this->prepareQueryOptions($params, $context);
        $transformation = $this->getConfHelper()->resolve($params[self::CONF_TRANSFORMATION], $context);

        $driver->update($collection, $queryOptions, $transformation);
    }

    public function performDelete(NoSQLDriverInterface $driver, $collection, $configuration, $context)
    {
        $params = $this->deleteOptionsResolver->resolve($configuration);
        $queryOptions = $this->prepareQueryOptions($params, $context);

        $driver->delete($collection, $queryOptions);
    }

    public function performInsertOne(NoSQLDriverInterface $driver, $collection, $configuration, $context)
    {
        $params = $this->insertOptionsResolver->resolve($configuration);
        $data = $this->getConfHelper()->resolve($params[self::CONF_DATA], $context);

        $driver->insertOne($collection, $data);
    }

    public function performInsertMany(NoSQLDriverInterface $driver, $collection, $configuration, $context)
    {
        $params = $this->insertOptionsResolver->resolve($configuration);
        $queryOptions = $this->prepareQueryOptions($params, $context);
        $data = $this->getConfHelper()->resolve($params[self::CONF_DATA], $context);

        $driver->insertMany($collection, $queryOptions, $data);
    }

    /**
     * @param $options
     *
     * @return NoSQLDriverInterface
     */
    protected function resolveDriver($options)
    {
        $driverName = $options[NoSQLConfigurableProtocol::OPTION_NOSQL_DRIVER];

        return $this->getDriverRegistry()->getDriver($driverName);
    }
}
