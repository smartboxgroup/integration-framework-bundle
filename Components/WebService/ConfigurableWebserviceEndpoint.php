<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService;

use Smartbox\Integration\FrameworkBundle\Core\Endpoints\Endpoint;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurableWebserviceEndpoint extends Endpoint{
    const OPTION_TIMEOUT = 'timeout';
    const OPTION_CONNECT_TIMEOUT = 'connect_timeout';
    const OPTION_METHOD = 'method';

    public function getMethod(){
        return $this->options[self::OPTION_METHOD];
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsDescriptions(){
        return array_merge(parent::getOptionsDescriptions(),[
            self::OPTION_METHOD => ['Method to be executed in the producer',[]],
            self::OPTION_TIMEOUT => ['Timeout of the request in seconds. Use 0 to wait indefinitely.',[]],
            self::OPTION_CONNECT_TIMEOUT => ['Timeout to establish the connection in seconds. Use 0 to wait indefinitely.',[]],
        ]);
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
        $resolver->setDefaults([
            self::OPTION_TIMEOUT => 10,
            self::OPTION_CONNECT_TIMEOUT => 10,
            self::OPTION_TRACK => true,
        ]);

        $resolver->setRequired([
            self::OPTION_METHOD, self::OPTION_TIMEOUT, self::OPTION_CONNECT_TIMEOUT
        ]);

        $resolver->setAllowedTypes(self::OPTION_TIMEOUT,['numeric']);
        $resolver->setAllowedTypes(self::OPTION_CONNECT_TIMEOUT,['numeric']);
        $resolver->setAllowedTypes(self::OPTION_METHOD,['string']);
    }

}