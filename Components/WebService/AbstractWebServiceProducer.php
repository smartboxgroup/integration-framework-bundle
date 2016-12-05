<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService;

use Smartbox\Integration\FrameworkBundle\Components\WebService\Exception\ExternalSystemException;
use Smartbox\Integration\FrameworkBundle\Core\Producers\AbstractConfigurableProducer;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesGroupVersionHydrator;
use Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits\UsesValidator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractWebServiceProducer extends AbstractConfigurableProducer
{
    use UsesValidator;
    use UsesGroupVersionHydrator;

    const STEP_VALIDATE_OBJECT_OUTPUT = 'validate_output';
    const OUTPUT_OBJECT = 'object';
    const OUTPUT_GROUP = 'group';
    const OUTPUT_VERSION = 'version';


    /**
     * @param $stepActionParams
     * @param $options
     * @param $context
     *
     * @throws ExternalSystemException
     */
    protected function validateOutput($stepActionParams, $options, $context)
    {
        if (!is_array($stepActionParams)) {
            throw new InvalidConfigurationException(
                "Step 'validate_output' in AbstractConfigurableProducer expected an array as configuration"
            );
        }

        $stepParamsResolver = new OptionsResolver();

        $stepParamsResolver->setRequired([
            self::OUTPUT_OBJECT,
            self::OUTPUT_GROUP,
            self::OUTPUT_VERSION,
        ]);

        $params = $stepParamsResolver->resolve($stepActionParams);

        $object = $this->confHelper->resolve($params[self::OUTPUT_OBJECT], $context);
        $group = $this->confHelper->resolve($params[self::OUTPUT_GROUP], $context);
        $version = $this->confHelper->resolve($params[self::OUTPUT_VERSION], $context);

        $this->getHydrator()->hydrate($object, $group, $version);
        $validator = $this->getValidator();
        $errors = $validator->validate($object);
        if (count($errors) > 0) {
            $message = '';
            foreach ($errors as $error){
                $message .= $error->getPropertyPath().' - '.$error->getMessage().' | ';
            }
            throw new ExternalSystemException($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeStep($stepAction, &$stepActionParams, &$options, array &$context)
    {
        if (!parent::executeStep($stepAction, $stepActionParams, $options, $context)) {
            switch ($stepAction) {
                case self::STEP_VALIDATE_OBJECT_OUTPUT:
                    $this->validateOutput($stepActionParams, $options, $context);

                    return true;
            }
        }

        return false;
    }
}