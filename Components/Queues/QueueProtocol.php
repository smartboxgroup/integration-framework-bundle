<?php

namespace Smartbox\Integration\FrameworkBundle\Components\Queues;

use JMS\Serializer\Annotation as JMS;
use Smartbox\Integration\FrameworkBundle\Configurability\DescriptableInterface;
use Smartbox\Integration\FrameworkBundle\Core\Protocols\Protocol;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class QueueProtocol.
 */
class QueueProtocol extends Protocol implements DescriptableInterface
{
    /**
     * @JMS\Exclude
     *
     * @var array
     */
    protected static $SUPPORTED_EXCHANGE_PATTERNS = [self::EXCHANGE_PATTERN_IN_ONLY];

    const OPTION_PREFIX = 'prefix';
    const OPTION_PERSISTENT = 'persistent';
    const OPTION_TTL = 'ttl';
    const OPTION_TYPE = 'type';
    const OPTION_PRIORITY = 'priority';
    const OPTION_QUEUE_NAME = 'queue';
    const OPTION_QUEUE_DRIVER = 'queue_driver';

    protected $defaultPersistence;
    protected $defaultTTL;

    public function __construct($defaultPersistence, $defaultTTL)
    {
        $this->defaultPersistence = $defaultPersistence;
        $this->defaultTTL = $defaultTTL;
    }

    /**
     * @return mixed
     */
    public function getDefaultPersistence()
    {
        return $this->defaultPersistence;
    }

    /**
     * @param mixed $defaultPersistence
     */
    public function setDefaultPersistence($defaultPersistence)
    {
        $this->defaultPersistence = $defaultPersistence;
    }

    /**
     * @return mixed
     */
    public function getDefaultTTL()
    {
        return $this->defaultTTL;
    }

    /**
     * @param mixed $defaultTTL
     */
    public function setDefaultTTL($defaultTTL)
    {
        $this->defaultTTL = $defaultTTL;
    }

    protected $defaultOptions = [
    ];

    public function getOptionsDescriptions()
    {
        $options = array_merge(parent::getOptionsDescriptions(), [
            self::OPTION_PREFIX => ['Prefix to prepend to the queue name', []],
            self::OPTION_QUEUE_NAME => ['Name of the queue, e.g: /boxes/pending ', []],
            self::OPTION_QUEUE_DRIVER => ['Queue driver that should be used to talk with tclehe queueing system', []],
            self::OPTION_PRIORITY => ['Priority for the messages in this queue', []],
            self::OPTION_TTL => ['Time to live in seconds, after which the messages will expire', []],
            self::OPTION_PERSISTENT => ['Whether messages coming to this queue should be persisted in disk', []],
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
        $resolver->setDefaults([
            self::OPTION_TTL => $this->defaultTTL,
            self::OPTION_PERSISTENT => $this->defaultPersistence,
            self::OPTION_PRIORITY => 4,
            self::OPTION_EXCHANGE_PATTERN => self::EXCHANGE_PATTERN_IN_ONLY,
            self::OPTION_TRACK => true,
            self::OPTION_PREFIX => '',
        ]);

        $resolver->setRequired([
            self::OPTION_TTL, self::OPTION_PERSISTENT, self::OPTION_PRIORITY, self::OPTION_PREFIX,
            self::OPTION_QUEUE_DRIVER, self::OPTION_QUEUE_NAME,
        ]);

        $resolver->setAllowedTypes(self::OPTION_TTL, ['numeric']);
        $resolver->setAllowedValues(self::OPTION_PERSISTENT, [true, false, 'true', 'false', 0, 1, '0', '1']);
        $resolver->setAllowedTypes(self::OPTION_PERSISTENT, ['numeric', 'string', 'boolean']);
        $resolver->setAllowedTypes(self::OPTION_PRIORITY, ['numeric']);
        $resolver->setAllowedTypes(self::OPTION_PREFIX, ['string']);
        $resolver->setAllowedTypes(self::OPTION_QUEUE_DRIVER, ['string']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Specialized protocol to interact with queuing systems (ActiveMq/STOMP only for now)';
    }
}
