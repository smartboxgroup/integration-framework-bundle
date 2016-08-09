<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Consumers;

use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSmartesbHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractConfigurableConsumer extends AbstractConsumer implements ConfigurableConsumerInterface
{
    use HasInternalType;
    use UsesSmartesbHelper;
    use UsesEvaluator;
    use UsesSerializer;
    use UsesConfigurableServiceHelper;

    /** @var  array */
    protected $methodsConfiguration;

    /** @var array  */
    protected $configuredOptions = [];

    /** @var string */
    protected $name;

    /**
     * @return string
     */
    public function getName()
    {
        if (!$this->name) {
            return parent::getName();
        }

        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->configuredOptions = array_merge($this->configuredOptions, $options);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->configuredOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setMethodsConfiguration(array $methodsConfiguration)
    {
        $this->methodsConfiguration = $methodsConfiguration;
    }

    /**
     * Returns true if the step was executed, false if the step was not recognized.
     *
     * @param       $stepAction
     * @param       $stepActionParams
     * @param       $options
     * @param array $context
     *
     * @return bool
     */
    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context)
    {
        switch ($stepAction) {
            case ConfigurableServiceHelper::STEP_DEFINE:
                $this->configurableServiceHelper->define($stepActionParams, $context);

                return true;
            default:
                return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsDescriptions()
    {
        $methodDescriptions = [];
        foreach ($this->methodsConfiguration as $method => $methodConfig) {
            $methodDescriptions[$method] = $methodConfig['description'];
        }

        $options = [
            ConfigurableServiceHelper::OPTION_METHOD => ["Method of the consumer to be executed", $methodDescriptions]
        ];

        foreach ($this->configuredOptions as $option => $value) {
            $options[$option] = ['Custom option added in configurable consumer', []];
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        $resolver->setRequired([ConfigurableServiceHelper::OPTION_METHOD]);
        $resolver->setAllowedValues(ConfigurableServiceHelper::OPTION_METHOD, array_keys($this->methodsConfiguration));

        foreach ($this->configuredOptions as $option => $value) {
            $resolver->setDefault($option, $value);
        }
    }
}