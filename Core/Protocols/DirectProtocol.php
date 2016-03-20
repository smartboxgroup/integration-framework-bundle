<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Protocols;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DirectProtocol extends Protocol {

    const OPTION_PATH = 'path';

    /**
     *  Key-Value array with the option name as key and the details as value
     *
     *  [OptionName => [description, array of valid values],..]
     *
     * @return array
     */
    public function getOptionsDescriptions()
    {
        return array_merge(
            parent::getOptionsDescriptions(),
            [
                self::OPTION_PATH => ['Path representing the subroutine to execute',[]],
            ]
        );
    }

    /**
     * With this method this class can configure an OptionsResolver that will be used to validate the options
     *
     * @param OptionsResolver $resolver
     * @return mixed
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver->setDefault(Protocol::OPTION_EXCHANGE_PATTERN,Protocol::EXCHANGE_PATTERN_IN_ONLY);
        $resolver->setAllowedValues(Protocol::OPTION_EXCHANGE_PATTERN, [Protocol::EXCHANGE_PATTERN_IN_ONLY]);
        $resolver->setRequired(self::OPTION_PATH);
        $resolver->setAllowedTypes(self::OPTION_PATH,['string']);
    }

}