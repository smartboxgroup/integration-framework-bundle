<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;

use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CsvConfigurableProtocol extends Protocol implements DescriptableInterface
{
    const OPTION_ROOT_PATH = 'root_path';
    const OPTION_DEFAULT_PATH = 'default_path';
    const OPTION_DELIMITER = 'delimiter';
    const OPTION_ENCLOSURE = 'enclosure';
    const OPTION_ESCAPE_CHAR = 'escape_char';
    const OPTION_MAX_LENGTH = 'max_length';
    const OPTION_METHOD = 'method';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Protocol to deal with CSV files';
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsDescriptions()
    {
        return array_merge(parent::getOptionsDescriptions(), [
            self::OPTION_ROOT_PATH => ['The root path to work in', []],
            self::OPTION_DEFAULT_PATH => ['The default path/filename to use if none is specified in the methods', []],
            self::OPTION_DELIMITER => ['The optional delimiter parameter sets the field delimiter (one character only). fputcsv()', []],
            self::OPTION_ENCLOSURE => ['The optional enclosure parameter sets the field enclosure (one character only). fputcsv()', []],
            self::OPTION_ESCAPE_CHAR => ['The optional escape_char parameter sets the escape character (one character only). fputcsv()', []],
            self::OPTION_MAX_LENGTH => ['The optional length parameter when using fgetcsv()', []],
            self::OPTION_METHOD => ['Method to be executed in the consumer/producer', []],
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
        $resolver->setDefaults([
            self::OPTION_DELIMITER => ',',
            self::OPTION_ENCLOSURE => '"',
            self::OPTION_ESCAPE_CHAR => '\\',
            self::OPTION_MAX_LENGTH => 1000,
        ]);

        $resolver->setRequired([
            self::OPTION_ROOT_PATH,
            self::OPTION_METHOD,
        ]);

        $resolver->setOptional([
            self::OPTION_DEFAULT_PATH,
        ]);

        $resolver->setAllowedTypes(self::OPTION_ROOT_PATH, ['string']);
        $resolver->setAllowedTypes(self::OPTION_DEFAULT_PATH, ['string']);
        $resolver->setAllowedTypes(self::OPTION_DELIMITER, ['string']);
        $resolver->setAllowedTypes(self::OPTION_ENCLOSURE, ['string']);
        $resolver->setAllowedTypes(self::OPTION_ESCAPE_CHAR, ['string']);
        $resolver->setAllowedTypes(self::OPTION_METHOD, ['string']);

    }

}
