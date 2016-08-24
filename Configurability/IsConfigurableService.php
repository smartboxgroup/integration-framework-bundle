<?php
namespace Smartbox\Integration\FrameworkBundle\Configurability;


use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesConfigurableServiceHelper;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesEvaluator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesSerializer;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait IsConfigurableService {
    use UsesEvaluator;
    use UsesSerializer;
    use UsesConfigurableServiceHelper;

    /** @var array */
    protected $methodsConfiguration;

    /** @var array */
    protected $configuredOptions = [];

    /** @var string */
    protected $name;

    /**
     * @return string
     */
    public function getName()
    {
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
     * {@inheritdoc}
     */
    public function getOptionsDescriptions()
    {
        $methodDescriptions = [];
        foreach ($this->methodsConfiguration as $method => $methodConfig) {
            $methodDescriptions[$method] = $methodConfig['description'];
        }

        $options = [
            ConfigurableServiceHelper::OPTION_METHOD => ["Method to be executed", $methodDescriptions]
        ];

        foreach ($this->configuredOptions as $option => $value) {
            $options[$option] = ['Custom option added in configurable service',[]];
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
