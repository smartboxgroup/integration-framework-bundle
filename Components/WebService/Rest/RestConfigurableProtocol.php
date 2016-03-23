<?php

namespace Smartbox\Integration\FrameworkBundle\Components\WebService\Rest;

use Smartbox\Integration\FrameworkBundle\Components\WebService\ConfigurableWebserviceProtocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RestConfigurableProtocol
 */
class RestConfigurableProtocol extends ConfigurableWebserviceProtocol
{
    const OPTION_BASE_URI = 'base_uri';
    const OPTION_HEADERS = 'headers';
    const OPTION_AUTH = 'authentication';
    const OPTION_ENCODING = 'encoding';

    const ENCODING_JSON = 'json';
    const ENCODING_XML = 'xml';
    const AUTH_BASIC = 'basic';

    /**
     * {@inheritDoc}
     */
    public function getOptionsDescriptions()
    {
        return array_merge(
            parent::getOptionsDescriptions(),
            [
                self::OPTION_ENCODING => [
                    'Encoding for requests and responses with the REST API',
                    [
                        self::ENCODING_JSON => 'JSON encoding',
                        self::ENCODING_XML => 'XML encoding',
                    ]
                ],
                self::OPTION_BASE_URI => ['Base URI for all requests', []],
                self::OPTION_HEADERS => ['Default headers to include in all requests (key-value array)', []],
                self::OPTION_AUTH => [
                    'Authentication method',
                    [
                        self::AUTH_BASIC => 'Use this method for basic http authentication'
                    ]
                ],
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);
        $resolver->setDefaults([
            self::OPTION_BASE_URI => '',
            self::OPTION_HEADERS => [],
            self::OPTION_AUTH => '',
            self::OPTION_ENCODING => self::ENCODING_JSON
        ]);

        $resolver->setRequired([
            self::OPTION_BASE_URI,
            self::OPTION_HEADERS,
            self::OPTION_AUTH,
            self::OPTION_ENCODING
        ]);

        $resolver->setAllowedTypes(self::OPTION_BASE_URI, ['string']);
        $resolver->setAllowedTypes(self::OPTION_HEADERS, ['array']);
        $resolver->setAllowedTypes(self::OPTION_AUTH, ['string', 'null']);
        $resolver->setAllowedTypes(self::OPTION_ENCODING, ['string']);
        $resolver->setAllowedValues(self::OPTION_ENCODING, [self::ENCODING_JSON, self::ENCODING_XML]);
    }
}
