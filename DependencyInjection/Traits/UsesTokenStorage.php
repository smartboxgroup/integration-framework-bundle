<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Trait UsesTokenStorage.
 */
trait UsesTokenStorage
{
    /* @var  TokenStorage */
    public $tokenStorage;

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
