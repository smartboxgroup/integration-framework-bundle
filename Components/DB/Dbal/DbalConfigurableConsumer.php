<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Dbal;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Statement;
use Smartbox\CoreBundle\Type\SerializableArray;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\AbstractConfigurableConsumer;
use Smartbox\Integration\FrameworkBundle\Core\Consumers\ConfigurableConsumerInterface;
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

    const CONF_HYDRATION = 'hydration';
    const CONF_PARAMETERS = 'parameters';
    const CONF_SQL = 'sql';
    const CONF_QUERY_NAME = 'name';

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
                    "Error while trying to consume using DbalConfigurableConsumer, null value found for query parameter: '$param'"
                );
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
            $this->configResolver->setDefaults(
                [
                    self::CONF_PARAMETERS => [],
                    self::CONF_HYDRATION => self::HYDRATION_ARRAY,
                ]
            );
            $this->configResolver->setDefined(self::CONF_QUERY_NAME);
            $this->configResolver->setAllowedTypes(self::CONF_SQL, ['string']);
            $this->configResolver->setAllowedTypes(self::CONF_PARAMETERS, ['array']);
            $this->configResolver->setAllowedValues(self::CONF_HYDRATION, [self::HYDRATION_ARRAY]);
        }
    }

    /** {@inheritdoc} */
    protected function initialize(EndpointInterface $endpoint)
    {
    }

    /** {@inheritdoc} */
    protected function cleanUp(EndpointInterface $endpoint)
    {
    }

    /** {@inheritdoc} */
    protected function readMessage(EndpointInterface $endpoint)
    {
        $method = $endpoint->getOption(ConfigurableDbalProtocol::OPTION_METHOD);
        $methodConf = $this->methodsConfiguration[$method];

        $context = [
            'serializer' => $this->getSerializer(),
            'options' => $endpoint->getOptions(),
            ConfigurableServiceHelper::KEY_VARS => [],
        ];

        foreach ($methodConf[ConfigurableConsumerInterface::CONFIG_QUERY_STEPS] as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $endpoint->getOptions(), $context);
            }
        }

        $result = $this->getConfHelper()->resolve(
            $methodConf[ConfigurableConsumerInterface::CONFIG_QUERY_RESULT],
            $context
        );

        unset($context);

        $message = $this->smartesbHelper->getMessageFactory()->createMessage(
            new SerializableArray($result)
        );

        return $message;
    }

    /**
     * {@inheritdoc}
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
            self::CONTEXT_RESULTS => [],
        ];

        foreach ($methodConf[ConfigurableConsumerInterface::CONFIG_ON_CONSUME] as $step) {
            foreach ($step as $stepAction => $stepActionParams) {
                $this->executeStep($stepAction, $stepActionParams, $endpoint->getOptions(), $context);
            }
        }

        unset($context);

        return $message;
    }
}