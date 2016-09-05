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

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit);

    /**
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset($offset);

    /**
     * @param array $queryParams
     *
     * @return $this
     */
    public function setQueryParams($queryParams);
}
