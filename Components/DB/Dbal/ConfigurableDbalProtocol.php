<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Dbal;

use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurableDbalProtocol extends Protocol implements DescriptableInterface
{
    const OPTION_METHOD = 'method';
    const OPTION_STOP_ON_NO_RESULTS = 'stop_on_no_results';
    const OPTION_DB_CONNECTION_NAME = 'db_connection_name';
    const OPTION_SLEEP_TIME = 'sleep_time_ms';
    const OPTION_INACTIVITY_TRIGGER = 'inactivity_trigger_sec';
    const OPTION_ALWAYS_SLEEP = 'always_sleep';

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
            self::OPTION_DB_CONNECTION_NAME => ['Option to chose which DB connection the consumer/producer should use', []],
            self::OPTION_SLEEP_TIME => ['Duration of the pause made in the consume loop, when nothing to do (slow mode), in milliseconds.', []],
            self::OPTION_INACTIVITY_TRIGGER => ['Inactivity duration before switching to slow mode, in seconds.', []],
            self::OPTION_ALWAYS_SLEEP => ['Always sleep for sleep_time_ms after each consume.', []],
        ]);
    }

    /**
     * With this method this class can configure an OptionsResolver that will be used to validate the options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);
        $resolver->setRequired([self::OPTION_METHOD]);

        $resolver->setDefaults([
            self::OPTION_STOP_ON_NO_RESULTS => false,
            self::OPTION_DB_CONNECTION_NAME => '',
            self::OPTION_SLEEP_TIME => 100,
            self::OPTION_INACTIVITY_TRIGGER => 10,
            self::OPTION_ALWAYS_SLEEP => false,
        ]);

        $resolver->setAllowedTypes(self::OPTION_METHOD, ['string']);
        $resolver->setAllowedTypes(self::OPTION_STOP_ON_NO_RESULTS, ['bool', 'numeric']);
        $resolver->setAllowedTypes(self::OPTION_DB_CONNECTION_NAME, 'string');
        $resolver->setAllowedTypes(self::OPTION_SLEEP_TIME, 'numeric');
        $resolver->setAllowedTypes(self::OPTION_INACTIVITY_TRIGGER, 'numeric');
        $resolver->setAllowedTypes(self::OPTION_ALWAYS_SLEEP, 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Specialized protocol to interact with databases using the doctrine DBAL';
    }
}
