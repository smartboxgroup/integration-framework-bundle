<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Command\fixtures\Entity;

use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\EntityInterface;

class ApiEntity extends Entity
{
    public function __construct()
    {
        $this->entityGroup = EntityInterface::GROUP_PUBLIC;
    }
}
