<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\App\Producers;

use Smartbox\Integration\FrameworkBundle\Tests\App\Entity\EntityX;
use Smartbox\Integration\FrameworkBundle\Configurability\ConfigurableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Endpoints\EndpointInterface;
use Smartbox\Integration\FrameworkBundle\Core\Exchange;
use Smartbox\Integration\FrameworkBundle\Core\Producers\Producer;
use Smartbox\Integration\FrameworkBundle\Core\Producers\ProducerInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class HelperProducer.
 */
class HelperProducer extends Producer implements ProducerInterface, ConfigurableInterface
{
    const OPTION_OPERATION = 'operation';
    const OPTION_OPERAND = 'operand';

    const OPERATION_MULTIPLY = 'multiply';
    const OPERATION_ADD = 'add';

    /**
     * Sends an exchange to the producer.
     *
     * @param Exchange $ex
     *
     * @throws \Exception
     */
    public function send(Exchange $ex, EndpointInterface $endpoint)
    {
        $options = $endpoint->getOptions();

        /** @var EntityX $x */
        $x = $ex->getIn()->getBody();
        if (empty($x) || !($x instanceof EntityX)) {
            throw new \InvalidArgumentException('Expected entity of type EntityX');
        }

        $operand = (int) @$options[self::OPTION_OPERAND];

        switch (@$options[self::OPTION_OPERATION]) {
            case self::OPERATION_MULTIPLY:
                $message = $this->messageFactory->createMessage(new EntityX($x->getX() * $operand));
                $ex->setOut($message);
                break;
            case self::OPERATION_ADD:
                $message = $this->messageFactory->createMessage(new EntityX($x->getX() + $operand));
                $ex->setOut($message);
                break;
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
            self::OPTION_OPERATION => ['Operation to apply to the EntityX in the body of the incoming messages', [
                self::OPERATION_ADD => 'Adds <comment>operand</comment> to the entityX value',
                self::OPERATION_MULTIPLY => 'Multiplies <comment>operand</comment> by the entityX value',
            ]],
            self::OPTION_OPERAND => ['Operand to use (number)', []],
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
        $resolver->setRequired(self::OPTION_OPERATION);
        $resolver->setAllowedValues(self::OPTION_OPERATION, [self::OPERATION_ADD, self::OPERATION_MULTIPLY]);

        $resolver->setRequired(self::OPTION_OPERAND);
        $resolver->setAllowedTypes(self::OPTION_OPERAND, ['numeric']);

        $resolver->setDefault(Protocol::OPTION_EXCHANGE_PATTERN, Protocol::EXCHANGE_PATTERN_IN_OUT);
    }
}
