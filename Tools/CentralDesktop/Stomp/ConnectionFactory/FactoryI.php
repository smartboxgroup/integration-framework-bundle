<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\CentralDesktop\Stomp\ConnectionFactory;

use CentralDesktop\Stomp\ConnectionFactory\FactoryI as BaseFactoryI;

interface FactoryI extends BaseFactoryI
{
    public function notifyAboutSuccessfulConnection();
}