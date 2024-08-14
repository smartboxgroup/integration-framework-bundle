<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Command\fixtures\Entity;

use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\EntityInterface;

class ApiEntity extends Entity
{
    protected string $entityGroup = EntityInterface::GROUP_PUBLIC;
    public function __construct()
    {
        parent::__construct();
        $this->entityGroup = EntityInterface::GROUP_PUBLIC;
    }
}
