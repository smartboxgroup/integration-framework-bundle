<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\SerializableInterface;

class EntityX extends Entity implements SerializableInterface
{
    public function __construct($x = 0)
    {
        $this->x = $x;
    }

    /**
     * @JMS\Type("integer")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     *
     * @var int
     */
    protected $x = 0;

    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @param int $x
     */
    public function setX($x)
    {
        $this->x = $x;
    }
}
