<?php

namespace Smartbox\Integration\FrameworkBundle\Tools\EventsDeferring;

/**
 * Class EventFiltersRegistry.
 */
class EventFiltersRegistry
{
    /** @var EventFilterInterface[] */
    protected $deferringFilters = [];

    /**
     * @return array
     */
    public function getDeferringFilters()
    {
        return $this->deferringFilters;
    }

    /**
     * @param array $deferringFilters
     */
    public function setDeferringFilters($deferringFilters)
    {
        $this->deferringFilters = $deferringFilters;
    }

    /**
     * @param EventFilterInterface $deferringFilter
     */
    public function addDeferringFilter($deferringFilter)
    {
        $this->deferringFilters[] = $deferringFilter;
    }
}
