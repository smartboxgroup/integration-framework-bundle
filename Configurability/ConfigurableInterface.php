<?php

namespace Smartbox\Integration\FrameworkBundle\Configurability;

use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 * Interface ConfigurableInterface
 *
 * Use this interface to flag those Components which can expose options
 *
 * Usually for Endpoints, Consumers, Producers
 *
 * @package Smartbox\Integration\FrameworkBundle\Core\Endpoints
 */
interface ConfigurableInterface {

    /**
     *  Key-Value array with the option name as key and the details as value
     *
     *  [OptionName => [description, array of valid values],..]
     *
     * @return array
     */
    public function getOptionsDescriptions();

    /**
     * With this method this class can configure an OptionsResolver that will be used to validate the options
     *
     * @param OptionsResolver $resolver
     * @return mixed
     */
    public function configureOptionsResolver(OptionsResolver $resolver);

}