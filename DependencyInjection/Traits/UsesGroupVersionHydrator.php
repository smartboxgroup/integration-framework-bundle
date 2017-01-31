<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\CoreBundle\Hydrator\GroupVersionHydrator;

/**
 * Trait UsesGroupVersionHydrator.
 */
trait UsesGroupVersionHydrator
{
    /** @var GroupVersionHydrator */
    protected $hydrator;

    /**
     * @return GroupVersionHydrator
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }

    /**
     * @param GroupVersionHydrator $hydrator
     *
     * @return $this
     */
    public function setHydrator(GroupVersionHydrator $hydrator)
    {
        $this->hydrator = $hydrator;

        return $this;
    }
}
