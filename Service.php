<?php

namespace Smartbox\Integration\FrameworkBundle;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\SerializableInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;
use Smartbox\Integration\FrameworkBundle\Traits\FlowsVersionAware;
use Smartbox\Integration\FrameworkBundle\Traits\MessageFactoryAware;

/**
 * Class Service
 * @package Smartbox\Integration\FrameworkBundle
 */
abstract class Service implements SerializableInterface
{
    use HasInternalType;
    use MessageFactoryAware;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     */
    public $id;

    public function __construct()
    {

    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
