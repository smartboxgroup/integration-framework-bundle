<?php

namespace Smartbox\Integration\FrameworkBundle\Components\SymfonyService;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class QueueProtocol
 */
class SymfonyServiceProtocol extends Protocol implements DescriptableInterface
{
    /**
     * @JMS\Exclude
     *
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY];

    const OPTION_SERVICE = 'service';
    const OPTION_METHOD = 'method';

    public function getOptionsDescriptions()
    {
        $options = array_merge(parent::getOptionsDescriptions(), [
            self::OPTION_SERVICE => ['Symfony service id to call to send the message', []],
            self::OPTION_METHOD => ['Method within the service to be called', []],
        ]);

        unset($options[self::OPTION_USERNAME]);
        unset($options[self::OPTION_PASSWORD]);
        unset($options[self::OPTION_EXCHANGE_PATTERN]);

        return $options;
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

        $resolver->setRequired([
            self::OPTION_SERVICE,
            self::OPTION_METHOD,
        ]);

        $resolver->setAllowedTypes(self::OPTION_SERVICE, ['string']);
        $resolver->setAllowedTypes(self::OPTION_METHOD, ['string']);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return 'Specialized protocol to interact with symfony services';
    }
}
