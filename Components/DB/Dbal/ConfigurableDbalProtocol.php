<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Dbal;

use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurableDbalProtocol extends Protocol implements DescriptableInterface
{
    const OPTION_METHOD = 'method';
    const OPTION_STOP_ON_NO_RESULTS = 'stop_on_no_results';

    /**
     * Get static default options.
     *
     * @return array Array with option name, description, and options (optional)
     */
    public function getOptionsDescriptions()
    {
        return array_merge(parent::getOptionsDescriptions(), [
            self::OPTION_METHOD => ['Method to be executed in the consumer/producer', []],
            self::OPTION_STOP_ON_NO_RESULTS => ['Consumer should stop on when all the records have been processed.', []],
        ]);
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
        parent::configureOptionsResolver($resolver);
        $resolver->setRequired([self::OPTION_METHOD]);

        $resolver->setDefaults([
            self::OPTION_STOP_ON_NO_RESULTS => false,
        ]);

        $resolver->setAllowedTypes(self::OPTION_METHOD, ['string']);
        $resolver->setAllowedTypes(self::OPTION_STOP_ON_NO_RESULTS, ['bool']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Specialized protocol to interact with databases using the doctrine DBAL';
    }
}
