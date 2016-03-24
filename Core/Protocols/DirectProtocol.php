<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Protocols;

use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DirectProtocol.
 */
class DirectProtocol extends Protocol implements DescriptableInterface
{
    const OPTION_PATH = 'path';

    /**
     * {@inheritdoc}
     */
    public function getOptionsDescriptions()
    {
        return array_merge(
            parent::getOptionsDescriptions(),
            [
                self::OPTION_PATH => ['Path representing the subroutine to execute', []],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver->setDefault(Protocol::OPTION_EXCHANGE_PATTERN, Protocol::EXCHANGE_PATTERN_IN_ONLY);
        $resolver->setAllowedValues(Protocol::OPTION_EXCHANGE_PATTERN, [Protocol::EXCHANGE_PATTERN_IN_ONLY]);
        $resolver->setRequired(self::OPTION_PATH);
        $resolver->setAllowedTypes(self::OPTION_PATH, ['string']);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return 'Specialized protocol to execute subroutines';
    }
}
