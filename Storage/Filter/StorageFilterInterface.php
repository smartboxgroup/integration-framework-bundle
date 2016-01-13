<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Filter;

/**
 * Interface StorageFilterInterface
 * @package Smartbox\Integration\FrameworkBundle\Storage\Filter
 */
interface StorageFilterInterface
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
