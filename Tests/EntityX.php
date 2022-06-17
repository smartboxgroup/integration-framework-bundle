<?php

namespace Smartbox\Integration\FrameworkBundle\Tests;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\EntityInterface;

class EntityX extends Entity
{
    protected string $entityGroup = EntityInterface::GROUP_PUBLIC;
    protected string $version = "v1";

    public function __construct($x = 0)
    {
        parent::__construct();
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
