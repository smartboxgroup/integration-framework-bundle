<?php

namespace Smartbox\Integration\FrameworkBundle;


use Smartbox\CoreBundle\Type\SerializableInterface;

interface ServiceInterface extends SerializableInterface {
    public function getId();
}