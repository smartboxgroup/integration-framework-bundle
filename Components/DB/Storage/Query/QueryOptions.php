<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\Storage\Query;

/**
 * Class QueryOptions.
 */
class QueryOptions implements QueryOptionsInterface
{
    const SORT_ASC = 1;
    const SORT_DESC = -1;

    protected $limit;
    protected $offset;
    protected $queryParams = array();
    protected $sortParams = array();

    /**
     * StorageFilter constructor.
     *
     * @param array $queryParams
     */
    public function __construct(array $queryParams = [])
    {
        $this->queryParams = $queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = intval($limit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = intval($offset);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @param array $queryParams
     *
     * @return $this
     */
    public function setQueryParams($queryParams)
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortParams()
    {
        return $this->sortParams;
    }

    /**
     * @param $column
     * @param $type
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function addSorting($column, $type)
    {
        $availableSortTypes = [self::SORT_ASC, self::SORT_DESC];
        if (!in_array($type, $availableSortTypes)) {
            throw new \Exception(sprintf('Wrong sort type: "%s". Supported types: [%s]', $type, implode(',', $availableSortTypes)));
        }

        $this->sortParams[$column] = $type;

        return $this;
    }
}
