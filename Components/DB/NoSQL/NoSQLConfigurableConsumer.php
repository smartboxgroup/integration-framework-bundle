<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL;

use JMS\Serializer\DeserializationContext;
use MongoDB\BSON\ObjectID;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\NoSQLDriverInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Query\QueryOptions;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConfigurableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoSQLConfigurableConsumer extends AbstractConfigurableConsumer
{
    const STEP_UPDATE           = 'update';
    const STEP_FIND             = 'find';
    const STEP_DELETE           = 'delete';
    const STEP_INSERT           = 'insert';

    const CONTEXT_CONSUMER      = 'consumer';
    const CONTEXT_RESULTS       = 'results';

    const CONFIG_QUERY_OPTIONS  = 'query_options';
    const CONFIG_ON_CONSUME     = 'on_consume';
    const CONF_HYDRATION        = 'hydration';
    const CONF_QUERY            = 'query';
    const CONF_PARAMETERS       = 'parameters';
    const CONF_COLLECTION       = 'collection';
    const CONF_QUERY_NAME       = 'name';
    const CONF_LIMIT            = 'limit';
    const CONF_OFFSET           = 'offset';
    const CONF_TRANSFORMATION   = 'transformation';
    const CONF_SORT             = 'sort';
    const CONF_SORT_FIELD       = 'field';
    const CONF_SORT_TYPE        = 'type';

    const HYDRATION_ARRAY       = 'array';

    const HEADER_ROW_COUNT      = 'row_count';

    /** @var  OptionsResolver */
    protected $configResolver;

    /** @var NoSQLDriverInterface */
    protected $noSQLDriver;

    /**
     * @return NoSQLDriverInterface
     */
    public function getNoSQLDriver()
    {
        return $this->noSQLDriver;
    }

    /**
     * @param NoSQLDriverInterface $noSQLDriver
     */
    public function setNoSQLDriver($noSQLDriver)
    {
        $this->noSQLDriver = $noSQLDriver;
    }

    /**
     * @return array
     */
    public static function getAllowedMethods()
    {
        return [
            self::STEP_DELETE,
            self::STEP_FIND,
            self::STEP_INSERT,
            self::STEP_UPDATE
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context)
    {
        if (!parent::executeStep($stepAction, $stepActionParams, $options, $context)) {
            if(in_array($stepAction, self::getAllowedMethods())){
                return $this->performQuery($stepAction, $stepActionParams, $context);
            }
        }

        return false;
    }

    /**
     * @param $method
     * @param $configuration
     * @param $context
     *
     * @return null|\Smartbox\CoreBundle\Type\SerializableInterface|\Smartbox\CoreBundle\Type\SerializableInterface[]
     */
    protected function performQuery($method, $configuration, &$context)
    {
        $collection = $context["options"][self::CONF_COLLECTION];
        if ($collection  === null) {
            throw new \RuntimeException("Error : Collection is empty");
        }

        switch ($method){
            case self::STEP_FIND:
                $result = $this->performFind($collection, $configuration, $context);
                break;

            case self::STEP_UPDATE:
                $result = $this->performUpdate($collection, $configuration, $context);
                break;

            case self::STEP_DELETE:
                throw new \RuntimeException("Method $method has not been implemented yet");
                break;

            case self::STEP_INSERT:
                throw new \RuntimeException("Method $method has not been implemented yet");
                break;

            default:
                throw new \RuntimeException("Method $method is not recognized by this consumer");
                break;
        }

        return $result;
    }

    public function performFind($collection, $configuration, $context)
    {
        $query = $configuration[self::CONF_QUERY] ;
        $resolvedQuery = $this->confHelper->resolve($query, $context);
        if ($resolvedQuery  === null) {
            throw new \RuntimeException("Error while trying to consume using NoSQLConsumer, null value found for query param $resolvedQuery");
        }

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams($resolvedQuery);


        $limit = $this->confHelper->resolve($configuration[self::CONF_LIMIT], $context);
        $offset = $this->confHelper->resolve($configuration[self::CONF_OFFSET], $context);
        $queryOptions->setLimit($limit);
        $queryOptions->setOffset($offset);

        $sorting = $configuration[self::CONF_SORT];
        if(!empty($sorting)){
            $queryOptions->addSorting(
                $sorting[self::CONF_SORT_FIELD],
                $sorting[self::CONF_SORT_TYPE]
            );
        }

        $result = $this->getNoSQLDriver()->find($collection, $queryOptions);
        if($limit == 1){
            $result = reset($result);
        }

        return $result;
    }

    public function performUpdate($collection, $configuration, $context)
    {
        $query = $configuration[self::CONF_QUERY] ;
        $resolvedQuery = $this->confHelper->resolve($query, $context);
        if ($resolvedQuery  === null) {
            throw new \RuntimeException("Error while trying to consume using NoSQLConsumer, null value found for query param $resolvedQuery");
        }

        $queryOptions = new QueryOptions();
        $queryOptions->setQueryParams($resolvedQuery);

        $transformation = $this->confHelper->resolve($configuration[self::CONF_TRANSFORMATION], $context);
        $result = $this->getNoSQLDriver()->update($collection, $queryOptions, $transformation);

        return $result;
    }

    public function __construct()
    {
        parent::__construct();

        if (!$this->configResolver) {
            $this->configResolver = new OptionsResolver();
            $this->configResolver->setRequired([self::CONF_QUERY, self::CONF_PARAMETERS, self::CONF_HYDRATION]);
            $this->configResolver->setDefaults([
                self::CONF_PARAMETERS => [],
                self::CONF_HYDRATION => self::HYDRATION_ARRAY,
                self::CONF_LIMIT => 1,
                self::CONF_OFFSET => 0,
             ]);
            $this->configResolver->setDefined(self::CONF_SORT);
            $this->configResolver->setDefined(self::CONF_SORT_FIELD);
            $this->configResolver->setDefined(self::CONF_SORT_TYPE);
            $this->configResolver->setDefined(self::CONF_TRANSFORMATION);
            $this->configResolver->setDefined(self::CONF_QUERY_NAME);
            $this->configResolver->setAllowedTypes(self::CONF_SORT_FIELD, ['string']);
            $this->configResolver->setAllowedTypes(self::CONF_QUERY, ['array']);
            $this->configResolver->setAllowedTypes(self::CONF_SORT, ['array']);
            $this->configResolver->setAllowedTypes(self::CONF_TRANSFORMATION, ['array']);
            $this->configResolver->setAllowedTypes(self::CONF_LIMIT, ['integer']);
            $this->configResolver->setAllowedTypes(self::CONF_OFFSET, ['integer']);
            $this->configResolver->setAllowedTypes(self::CONF_PARAMETERS, ['array']);
            $this->configResolver->setAllowedValues(self::CONF_HYDRATION, [self::HYDRATION_ARRAY]);
            $this->configResolver->setAllowedValues(self::CONF_SORT_TYPE, [QueryOptions::SORT_ASC, QueryOptions::SORT_DESC]);
        }
    }

    /**
     * Initializes the consumer for a given endpoint
     *
     * @param EndpointInterface $endpoint
     */
    protected function initialize(EndpointInterface $endpoint)
    {
    }

    /**
     * @param EndpointInterface $endpoint
     *
     * @return mixed
     */
    protected function cleanUp(EndpointInterface $endpoint)
    {
    }

    /**
     * This function is called to read and usually lock a message from the source Endpoint. The message should not be
     * removed from the source Endpoint, this is important to ensure the Message Delivery Guarantee.
     *
     * Additionally, if the source Endpoint can be consumed by competing consumers, the consumption of this message
     * should be locked in the source Endpoint, to avoid processing a message twice.
     *
     * If it was not possible to read a message, or there are no more messages in the Endpoint right now, this method
     * must return null to indicate that.
     *
     * @param EndpointInterface $endpoint
     *
     * @return MessageInterface | null
     */
    protected function readMessage(EndpointInterface $endpoint)
    {
        $method = $endpoint->getOption(NoSQLConfigurableProtocol::OPTION_METHOD);
        $methodConf = $this->methodsConfiguration[$method];
        $queryConfig = $this->configResolver->resolve($methodConf[self::CONFIG_QUERY_OPTIONS]);

        $context = [
            'serializer' => $this->getSerializer(),
            'options' => $endpoint->getOptions(),
            ConfigurableServiceHelper::KEY_VARS => [],
            self::CONTEXT_CONSUMER => $this,
        ];
        $results = $this->performQuery(self::STEP_FIND, $queryConfig, $context);

        $limit = $queryConfig[self::CONF_LIMIT];
        if($limit > 1 ){
            $results = new SerializableArray($results);
        }

        unset($context);
        $message = $this->smartesbHelper->getMessageFactory()->createMessage(
            $results,
            [self::HEADER_ROW_COUNT => $limit]
        );

        return $message;
    }

    /**
     * This function is called to confirm that a message was successfully handled. Until this point, the message should
     * not be removed from the source Endpoint, this is very important to ensure the Message delivery guarantee.
     *
     * @return MessageInterface
     */
    protected function confirmMessage(EndpointInterface $endpoint, MessageInterface $message)
    {
        $method = $endpoint->getOption(NoSQLConfigurableProtocol::OPTION_METHOD);
        $methodConf = $this->methodsConfiguration[$method];

        $context = [
            'msg' => $message,
            'headers' => $message->getHeaders(),
            'body' => $message->getBody(),
            'serializer' => $this->getSerializer(),
            'options' => $endpoint->getOptions(),
            ConfigurableServiceHelper::KEY_VARS => [],
            self::CONTEXT_CONSUMER => $this,
            self::CONTEXT_RESULTS => [],
        ];

        foreach ($methodConf[self::CONFIG_ON_CONSUME] as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $endpoint->getOptions(), $context);
            }
        }

        unset($context);

        return $message;
    }
}