<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\App\Producers;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Tests\App\Entity\EntityX;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Smartbox\Integration\FrameworkBundle\Tests\Fixtures\Exceptions\SampleRecoverableException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ErrorTriggerProducer.
 */
class ErrorTriggerProducer extends Producer implements ConfigurableInterface
{
    /**
     * @JMS\Exclude
     *
     * @var array
     */
    public static $count = 0;

    const OPTION_RECOVERABLE = 'recoverable';
    const OPTION_FORCE = 'force';
    const OPTION_NUMBER_ERRORS = 'nb_errors';

    /**
     * Sends an exchange to the producer.
     *
     * @param \Smartbox\Integration\FrameworkBundle\Core\Exchange $ex
     *
     * @throws \Exception
     */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();

        if (self::$count < @$options[self::OPTION_NUMBER_ERRORS] || @$options[self::OPTION_FORCE]) {
            $ex->getIn()->setBody(new EntityX(666));
            ++self::$count;

            if (@$options[self::OPTION_RECOVERABLE]) {
                throw new SampleRecoverableException('test recoverable exception');
            }

            throw new \RuntimeException('test exception');
        }
    }

    /**
     *  Key-Value array with the option name as key and the details as value.
     *
     *  [OptionName => [description, array of valid values],..]
     *
     * @return array
     */
    public function getOptionsDescriptions()
    {
        $options = [
            self::OPTION_RECOVERABLE => ['Whether the errors triggered are recoverable or not', []],
            self::OPTION_FORCE => ['Force to throw the exception every time, not only "n" number of times', []],
            self::OPTION_NUMBER_ERRORS => ['Define the number of times the exception will be throw', []]
        ];

        return $options;
    }

    /**
     * With this method this class can configure an OptionsResolver that will be used to validate the options.
     *
     * @param OptionsResolver $resolver
     *
     * @return mixed
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::OPTION_RECOVERABLE);
        $resolver->setDefault(Protocol::OPTION_EXCHANGE_PATTERN, Protocol::EXCHANGE_PATTERN_IN_ONLY);
        $resolver->setDefault(self::OPTION_RECOVERABLE, false);
        $resolver->setDefault(self::OPTION_FORCE, false);
        $resolver->setAllowedValues(Protocol::OPTION_EXCHANGE_PATTERN, [Protocol::EXCHANGE_PATTERN_IN_ONLY]);
        $resolver->setDefault(self::OPTION_NUMBER_ERRORS, 1);
    }
}
