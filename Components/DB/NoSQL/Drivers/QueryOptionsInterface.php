<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers;

/**
 * Interface QueryOptionsInterface.
 */
interface QueryOptionsInterface
{
    /**
     * @return array
     */
    public function getQueryParams();

    /**
     * @return array
     */
    public function getSortParams();

    /**
     * @return int
     */
    public function getLimit();

    /**
     * @return int
     */
    public function getOffset();
}
