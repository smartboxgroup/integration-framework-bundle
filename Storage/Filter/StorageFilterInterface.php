<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Filter;

interface StorageFilterInterface
{
    public function getQueryParams();

    public function getSortParams();

    public function getLimit();

    public function getOffset();
}