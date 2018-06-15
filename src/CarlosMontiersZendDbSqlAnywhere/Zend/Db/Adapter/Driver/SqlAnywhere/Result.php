<?php

namespace CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Driver\SqlAnywhere;

use Zend\Db\Adapter\Driver\ResultInterface;

/**
 * SAP Sybase SQL Anywhere driver for zend-db result class.
 *
 * @author Carlos Montiers Aguilera <cmontiers@gmail.com>
 * @link https://github.com/carlos-montiers/zend-db-sqlanywhere
 * @license MIT License
 */
class Result implements ResultInterface
{

    /**
     *
     * @var sasql_result
     */
    protected $resource = null;

    /**
     *
     * @var bool
     */
    protected $currentData = false;

    /**
     *
     * @var bool
     */
    protected $currentComplete = false;

    /**
     *
     * @var int
     */
    protected $position = 0;

    /**
     *
     * @var mixed
     */
    protected $generatedValue = null;

    /**
     *
     * @var int
     */
    protected $affectedRows = 0;

    /**
     * Initialize
     *
     * @param sasql_result $resource
     * @param int $affectedRows
     * @param mixed $generatedValue
     * @return self Provides a fluent interface
     */
    public function initialize($resource, $affectedRows, $generatedValue = null)
    {
        $this->resource = $resource;
        $this->affectedRows = $affectedRows;
        $this->generatedValue = $generatedValue;
        return $this;
    }

    /**
     *
     * @return null
     */
    public function buffer()
    {
        return;
    }

    /**
     *
     * @return bool
     */
    public function isBuffered()
    {
        return false;
    }

    /**
     * Get resource
     *
     * @return sasql_result
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Current
     *
     * @return mixed
     */
    public function current()
    {
        if ($this->currentComplete) {
            return $this->currentData;
        }

        $this->load();
        return $this->currentData;
    }

    /**
     * Load
     *
     * @return mixed
     */
    protected function load()
    {
        $this->currentData = sasql_fetch_array($this->resource, SqlAnywhere::SASQL_ASSOC);
        $this->currentComplete = true;
        $this->position++;
        return $this->currentData;
    }

    /**
     * Next
     *
     * @return bool
     */
    public function next()
    {
        $this->load();
        return true;
    }

    /**
     * Rewind
     *
     * @return bool
     */
    public function rewind()
    {
        $this->position = 0;
        $this->load();
        return true;
    }

    /**
     * Key
     *
     * @return mixed
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        if ($this->currentComplete && $this->currentData) {
            return true;
        }

        return $this->load();
    }

    /**
     * Count
     *
     * @return int
     */
    public function count()
    {
        return sasql_num_rows($this->resource);
    }

    /**
     *
     * @return bool|int
     */
    public function getFieldCount()
    {
        return sasql_num_fields($this->resource);
    }

    /**
     * Is query result
     *
     * @return bool
     */
    public function isQueryResult()
    {
        if (is_bool($this->resource)) {
            return false;
        }
        return (sasql_num_fields($this->resource) > 0);
    }

    /**
     * Get affected rows
     *
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    /**
     *
     * @return mixed|null
     */
    public function getGeneratedValue()
    {
        return $this->generatedValue;
    }
}
