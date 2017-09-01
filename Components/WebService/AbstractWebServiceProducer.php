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
    const OUTPUT_DISPLAY_ERROR = 'display_error';
    const HTTP_HEADER_TRANSACTION_ID ='X-Transaction-Id';
    const HTTP_HEADER_EAI_TIMESTAMP ='X-Eai-Timestamp';

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
                "Step '" . self::STEP_VALIDATE_OBJECT_OUTPUT . "' in AbstractConfigurableProducer expected an array as configuration"
            );
        }

        $stepParamsResolver = new OptionsResolver();

        $stepParamsResolver->setRequired([
            self::OUTPUT_OBJECT,
            self::OUTPUT_GROUP,
            self::OUTPUT_DISPLAY_ERROR,
        ]);

        $params = $stepParamsResolver->resolve($stepActionParams);

        $object = $this->confHelper->resolve($params[self::OUTPUT_OBJECT], $context);
        $group = $this->confHelper->resolve($params[self::OUTPUT_GROUP], $context);
        $showError = $this->confHelper->resolve($params[self::OUTPUT_DISPLAY_ERROR], $context);

        $version = $context['exchange']->getHeaders()['apiVersion'];

        $this->getHydrator()->hydrate($object, $group, $version);
        $validator = $this->getValidator();
        $errors = $validator->validate($object);
        if (count($errors) > 0) {
            $message = '';
            foreach ($errors as $error) {
                $message .= $error->getPropertyPath().' - '.$error->getMessage().' | ';
            }
            $exception = new ExternalSystemException($message);
            $exception->setExternalSystemName($this->getName());
            $exception->setShowExternalSystemErrorMessage($showError);
            throw $exception;
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
