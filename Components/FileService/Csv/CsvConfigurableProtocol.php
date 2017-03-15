<?php

namespace Smartbox\Integration\FrameworkBundle\Components\FileService\Csv;

use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CsvConfigurableProtocol extends Protocol implements DescriptableInterface
{
    const OPTION_PATH = 'path';
    const OPTION_DELIMITER = 'delimiter';
    const OPTION_ENCLOSURE = 'enclosure';
    const OPTION_ESCAPE_CHAR = 'escape_char';
    const OPTION_MAX_LENGTH = 'max_length';
    const OPTION_METHOD = 'method';
    const OPTION_STOP_ON_EOF = 'stop_on_eof';

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
            self::OPTION_PATH => ['The root path to work in', []],
            self::OPTION_DELIMITER => ['The optional delimiter parameter sets the field delimiter (one character only). fputcsv()', []],
            self::OPTION_ENCLOSURE => ['The optional enclosure parameter sets the field enclosure (one character only). fputcsv()', []],
            self::OPTION_ESCAPE_CHAR => ['The optional escape_char parameter sets the escape character (one character only). fputcsv()', []],
            self::OPTION_MAX_LENGTH => ['The optional length parameter when using fgetcsv()', []],
            self::OPTION_METHOD => ['Method to be executed in the consumer/producer', []],
            self::OPTION_STOP_ON_EOF => ['Consumer should stop on when reached the end of a file.', []],
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
            self::OPTION_STOP_ON_EOF => false,
        ]);

        $resolver->setRequired([
            self::OPTION_PATH,
            self::OPTION_METHOD,
        ]);

        $resolver->setAllowedTypes(self::OPTION_PATH, ['string']);
        $resolver->setAllowedTypes(self::OPTION_DELIMITER, ['string']);
        $resolver->setAllowedTypes(self::OPTION_ENCLOSURE, ['string']);
        $resolver->setAllowedTypes(self::OPTION_ESCAPE_CHAR, ['string']);
        $resolver->setAllowedTypes(self::OPTION_METHOD, ['string']);
        $resolver->setAllowedTypes(self::OPTION_STOP_ON_EOF, ['bool']);
    }
}
