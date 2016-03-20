<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;


use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class UsesTokenStorage
 * @package Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits
 */
trait UsesTokenStorage
{
    /** @var  TokenStorage */
    var $tokenStorage;

    /**
     * @return TokenStorage
     */
    public function getTokenStorage()
    {
        return $this->tokenStorage;
    }

    /**
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

}