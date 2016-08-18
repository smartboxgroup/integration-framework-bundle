<?php

namespace Smartbox\Integration\FrameworkBundle\Components\JsonFileLoader;

use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonFileLoaderProtocol extends Protocol implements DescriptableInterface
{
    const OPTION_FILENAME = 'filename';
    const OPTION_BASE_PATH = 'base_path';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Protocol to deal with JSON files';
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
        return array_merge_recursive(parent::getOptionsDescriptions(),
            [
            self::OPTION_BASE_PATH => ['Base path to look for the json file', []],
            self::OPTION_FILENAME => ['Name of the file to load', []],
            ]
        );
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
        $resolver->setRequired(self::OPTION_BASE_PATH);
        $resolver->setAllowedTypes(self::OPTION_BASE_PATH, ['string']);
        $resolver->setRequired(self::OPTION_FILENAME);
        $resolver->setAllowedTypes(self::OPTION_FILENAME, ['string']);
    }
}
