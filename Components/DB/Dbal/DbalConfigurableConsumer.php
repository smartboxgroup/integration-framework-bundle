<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Dbal;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConfigurableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DbalConfigurableConsumer extends AbstractConfigurableConsumer
{
    /** @var Registry */
    protected $doctrine;

    const STEP_EXECUTE = 'execute';

    const CONTEXT_CONSUMER = 'consumer';
    const CONTEXT_RESULTS = 'results';

    const CONFIG_QUERY_OPTIONS = 'query_options';
    const CONFIG_MULTI_ROW = 'multi_row';
    const CONFIG_ON_CONSUME = 'on_consume';
    const CONF_HYDRATION = 'hydration';
    const CONF_PARAMETERS = 'parameters';
    const CONF_SQL = 'sql';
    const CONF_QUERY_NAME = 'name';

    const HEADER_ROW_COUNT = 'row_count';

    const HYDRATION_ARRAY = 'array';

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
    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context)
    {
        if (!parent::executeStep($stepAction, $stepActionParams, $options, $context)) {
            switch ($stepAction) {
                case self::STEP_EXECUTE:
                    $stepActionParams = $this->configResolver->resolve($stepActionParams);
                    $this->performQuery($stepActionParams, $context);
                    return true;
            }
        }

        return false;
    }

    /**
     * @param array $configuration
     * @param array $endpointOptions
     * @return Statement
     */
    protected function performQuery(array $configuration, &$context)
    {
        $parameters = [];
        $parameterTypes = [];

        foreach ($configuration[self::CONF_PARAMETERS] as $param => $info) {
            $value = $this->confHelper->resolve($info['value'], $context);
            if ($value === null) {
                throw new \RuntimeException("Error while trying to consume using DbalConfigurableConsumer, null value found for query parameter: '$param'");
            }

            $parameters[$param] = $value;
            $parameterTypes[$param] = $info['type'];
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
            $this->configResolver->setRequired([self::CONF_SQL, self::CONF_PARAMETERS, self::CONF_HYDRATION]);
            $this->configResolver->setDefaults([
                self::CONF_PARAMETERS => [],
                self::CONF_HYDRATION => self::HYDRATION_ARRAY,
                self::CONFIG_MULTI_ROW => false
            ]);
            $this->configResolver->setDefined(self::CONF_QUERY_NAME);
            $this->configResolver->setAllowedTypes(self::CONF_SQL, ['string']);
            $this->configResolver->setAllowedTypes(self::CONF_PARAMETERS, ['array']);
            $this->configResolver->setAllowedTypes(self::CONFIG_MULTI_ROW, ['bool']);
            $this->configResolver->setAllowedValues(self::CONF_HYDRATION, [self::HYDRATION_ARRAY]);
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
        $method = $endpoint->getOption(ConfigurableDbalProtocol::OPTION_METHOD);
        $methodConf = $this->methodsConfiguration[$method];

        $queryConfig = $this->configResolver->resolve($methodConf[self::CONFIG_QUERY_OPTIONS]);

        $context = [
            'serializer' => $this->getSerializer(),
            'options' => $endpoint->getOptions(),
            ConfigurableServiceHelper::KEY_VARS => [],
            self::CONTEXT_CONSUMER => $this,
        ];

        $results = $this->performQuery($queryConfig, $context);

        unset($context);

        $count = $results->rowCount();
        $message = null;

        if ($count > 0) {
            if ($queryConfig[self::CONFIG_MULTI_ROW]) {
                $body = $results->fetchAll();
            } else {
                $body = $results->fetch();
            }

            $message = $this->smartesbHelper->getMessageFactory()->createMessage(
                new SerializableArray($body),
                [self::HEADER_ROW_COUNT => $count]
            );
        }

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
        $method = $endpoint->getOption(ConfigurableDbalProtocol::OPTION_METHOD);
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