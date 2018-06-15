<?php

namespace CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Driver\SqlAnywhere;

use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Exception;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Profiler;

/**
 * SAP Sybase SQL Anywhere driver for zend-db statement class.
 *
 * @author Carlos Montiers Aguilera <cmontiers@gmail.com>
 * @link https://github.com/carlos-montiers/zend-db-sqlanywhere
 * @license MIT License
 */
class Statement implements StatementInterface, Profiler\ProfilerAwareInterface
{

    /**
     *
     * @var sasql_conn
     */
    protected $conn = null;

    /**
     *
     * @var SqlAnywhere
     */
    protected $driver = null;

    /**
     *
     * @var Profiler\ProfilerInterface
     */
    protected $profiler = null;

    /**
     *
     * @var string
     */
    protected $sql = null;

    /**
     *
     * @var ParameterContainer
     */
    protected $parameterContainer = null;

    /**
     *
     * @var sasql_stmt
     */
    protected $resource = null;

    /**
     *
     * @var bool
     */
    protected $isPrepared = false;

    /**
     *
     * @var array
     */
    protected $prepareOptions = array();

    /**
     * Set driver
     *
     * @param SqlAnywhere $driver
     * @return self Provides a fluent interface
     */
    public function setDriver(SqlAnywhere $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     *
     * @return null|Profiler\ProfilerInterface
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     *
     * @param Profiler\ProfilerInterface $profiler
     * @return self Provides a fluent interface
     */
    public function setProfiler(Profiler\ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
        return $this;
    }

    /**
     *
     * @param sasql_conn|sasql_stmt $resource
     * @return self Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function initialize($resource)
    {
        $resourceType = get_resource_type($resource);
        if ($resourceType == 'SQLAnywhere connection') {
            $this->conn = $resource;
        } elseif ($resourceType == 'SQLAnywhere statement') {
            $this->resource = $resource;
            $this->isPrepared = true;
        } else {
            throw new Exception\InvalidArgumentException('Invalid resource provided to ' . __CLASS__);
        }
        return $this;
    }

    /**
     *
     * @return ParameterContainer
     */
    public function getParameterContainer()
    {
        return $this->parameterContainer;
    }

    /**
     * Set parameter container
     *
     * @param ParameterContainer $parameterContainer
     * @return self Provides a fluent interface
     */
    public function setParameterContainer(ParameterContainer $parameterContainer)
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    /**
     * Get resource
     *
     * @return sasql_stmt
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     *
     * @param
     *            $resource
     * @return self Provides a fluent interface
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * Get sql
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     *
     * @param string $sql
     * @return self Provides a fluent interface
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     *
     * @return bool
     */
    public function isPrepared()
    {
        return $this->isPrepared;
    }

    /**
     * Execute
     *
     * @param null|array|ParameterContainer $parameters
     * @return Result
     * @throws SQLAnywhereException
     * @throws Exception\RuntimeException
     */
    public function execute($parameters = null)
    {
        if (!$this->parameterContainer instanceof ParameterContainer) {
            if ($parameters instanceof ParameterContainer) {
                $this->parameterContainer = $parameters;
                $parameters = null;
            } else {
                $this->parameterContainer = new ParameterContainer();
            }
        }

        if (is_array($parameters)) {
            $this->parameterContainer->setFromArray($parameters);
        }

        if ($this->profiler) {
            $this->profiler->profilerStart($this);
        }

        if (!$this->isPrepared) {
            $this->prepare();
        }

        if ($this->parameterContainer->count() > 0) {
            $this->bindParametersFromContainer();
        }

        if (!sasql_stmt_execute($this->resource)) {
            throw SQLAnywhereException::fromSQLAnywhereError();
        }
        $resultValue = sasql_stmt_result_metadata($this->resource);

        if ($this->profiler) {
            $this->profiler->profilerFinish();
        }

        $result = $this->driver->createResult($resultValue);
        return $result;
    }

    /**
     *
     * @param string $sql
     * @return self Provides a fluent interface
     * @throws SQLAnywhereException
     * @throws Exception\RuntimeException
     */
    public function prepare($sql = null)
    {
        if ($this->isPrepared) {
            throw new Exception\RuntimeException('Already prepared');
        }
        if (empty($sql)) {
            $sql = $this->sql;
        }

        $this->resource = sasql_prepare($this->conn, $sql);
        if (!$this->resource) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->conn);
        }

        $this->isPrepared = true;

        return $this;
    }

    /**
     * Bind parameters from container
     *
     * @throws SQLAnywhereException
     */
    protected function bindParametersFromContainer()
    {
        $parameterValues = $this->parameterContainer->getPositionalArray();
        $count = count($parameterValues);
        for ($position = 0; $position < $count; $position++) {
            $variable = &$parameterValues[$position];
            $type = $this->getType($variable);
            if (!sasql_stmt_bind_param_ex($this->resource, $position, $variable, $type, $variable === null)) {
                throw SQLAnywhereException::fromSQLAnywhereError($this->conn, $this->resource);
            }
        }
    }

    public function getType(&$variable)
    {
        if (is_string($variable)) {
            $type = 's';
        } elseif (is_int($variable)) {
            $type = 'i';
        } else if (is_double($variable)) {
            $type = 'd';
        } else {
            $type = 'b';
        }
        return $type;
    }
}
