<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Command\fixtures\Entity;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\EntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ApiEntity extends Entity
{
    public function __construct()
    {
        $this->group = EntityInterface::GROUP_PUBLIC;
    }
}