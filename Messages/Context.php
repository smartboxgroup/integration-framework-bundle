<?php

namespace Smartbox\Integration\FrameworkBundle\Messages;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableArray;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Context
 * @package Smartbox\Integration\FrameworkBundle\Messages
 */
class Context implements \ArrayAccess
{
    const TRANSACTION_ID = 'transaction_id';
    const ORIGINAL_FROM = 'from';
    const ORIGINAL_TIMESTAMP = 'timestamp';
    const USER = 'user';
    const IP = 'ip';
    const VERSION = 'version';

    /**
     * @JMS\Type("Smartbox\CoreBundle\Type\SerializableArray")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @var SerializableArray
     */
    protected $values;

    /**
     * @param SerializableArray|array $values
     */
    public function __construct($values = [])
    {
        if ($values instanceof SerializableArray) {
            $values = $this->getOptionsResolver()->resolve($values->toArray());
        } else if (is_array($values)) {
            $values = $this->getOptionsResolver()->resolve($values);
        } else {
            throw new \InvalidArgumentException("Invalid value, expected array or SerializableArray");
        }

        $this->values = new SerializableArray($values);
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([self::TRANSACTION_ID, self::ORIGINAL_FROM, self::ORIGINAL_TIMESTAMP, self::USER, self::IP, self::VERSION]);
        $resolver->setAllowedTypes(self::VERSION, ['string']);

        return $resolver;
    }

    /**
     * Get a value from the context
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->values->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return @$this->values[$offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception("You can not mutate the context once is created");
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        throw new \Exception("You can not mutate the context once is created");
    }
}
