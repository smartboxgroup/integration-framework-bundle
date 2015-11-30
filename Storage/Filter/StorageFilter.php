<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Filter;

class StorageFilter implements StorageFilterInterface
{
    CONST SORT_ASC = 1;
    CONST SORT_DESC = -1;

    protected $limit;
    protected $offset;
    protected $queryParams = array();
    protected $sortParams = array();

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param mixed $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @param array $queryParams
     */
    public function setQueryParams($queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * @return array
     */
    public function getSortParams()
    {
        return $this->sortParams;
    }

    /**
     * @param $column
     * @param $type
     * @throws \Exception
     */
    public function addSorting($column, $type)
    {
        $availableSortTypes = [self::SORT_ASC, self::SORT_DESC];
        if (!in_array($type, $availableSortTypes)) {
            throw new \Exception(sprintf('Wrong sort type: "%s". Supported types: [%s]', $type, implode(',', $availableSortTypes)));
        }

        $this->sortParams[$column] = $type;
    }
}