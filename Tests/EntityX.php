<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\Entity;
use Symfony\Component\Validator\Constraints as Assert;

class EntityX extends Entity
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
