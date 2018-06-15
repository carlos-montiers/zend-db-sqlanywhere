<?php

namespace CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Driver\SqlAnywhere;

use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\Exception;
use Zend\Db\Adapter\Profiler;

/**
 * SAP Sybase SQL Anywhere driver for zend-db.
 *
 * @author Carlos Montiers Aguilera <cmontiers@gmail.com>
 * @link https://github.com/carlos-montiers/zend-db-sqlanywhere
 * @license MIT License
 */
class SqlAnywhere implements DriverInterface, Profiler\ProfilerAwareInterface
{

    const SASQL_USE_RESULT = 0;

    const SASQL_STORE_RESULT = 1;

    const SASQL_NUM = 1;

    const SASQL_ASSOC = 2;

    const SASQL_BOTH = 3;

    /**
     *
     * @var Connection
     */
    protected $connection = null;

    /**
     *
     * @var Statement
     */
    protected $statementPrototype = null;

    /**
     *
     * @var Result
     */
    protected $resultPrototype = null;

    /**
     *
     * @var null|Profiler\ProfilerInterface
     */
    protected $profiler = null;

    /**
     *
     * @var bool
     */
    protected $extensionLoaded = false;

    /**
     *
     * @param array|Connection|resource $connection
     * @param null|Statement $statementPrototype
     * @param null|Result $resultPrototype
     */
    public function __construct($connection, Statement $statementPrototype = null, Result $resultPrototype = null)
    {
        $this->extensionLoaded = extension_loaded('sqlanywhere');

        if (!$connection instanceof Connection) {
            $connection = new Connection($connection);
        }

        if (empty($statementPrototype)) {
            $statementPrototype = new Statement();
        }

        if (empty($resultPrototype)) {
            $resultPrototype = new Result();
        }

        $this->registerConnection($connection);
        $this->registerStatementPrototype($statementPrototype);
        $this->registerResultPrototype($resultPrototype);
    }

    /**
     * Register connection
     *
     * @param Connection $connection
     * @return self Provides a fluent interface
     */
    public function registerConnection(Connection $connection)
    {
        $this->connection = $connection;
        $this->connection->setDriver($this);
        return $this;
    }

    /**
     * Register statement prototype
     *
     * @param Statement $statementPrototype
     * @return self Provides a fluent interface
     */
    public function registerStatementPrototype(Statement $statementPrototype)
    {
        $this->statementPrototype = $statementPrototype;
        $this->statementPrototype->setDriver($this);
        return $this;
    }

    /**
     * Register result prototype
     *
     * @param Result $resultPrototype
     * @return self Provides a fluent interface
     */
    public function registerResultPrototype(Result $resultPrototype)
    {
        $this->resultPrototype = $resultPrototype;
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
        if ($this->connection instanceof Profiler\ProfilerAwareInterface) {
            $this->connection->setProfiler($profiler);
        }
        if ($this->statementPrototype instanceof Profiler\ProfilerAwareInterface) {
            $this->statementPrototype->setProfiler($profiler);
        }
        return $this;
    }

    /**
     * Get database paltform name
     *
     * @param string $nameFormat
     * @return string
     */
    public function getDatabasePlatformName($nameFormat = self::NAME_FORMAT_CAMELCASE)
    {
        if ($nameFormat == self::NAME_FORMAT_CAMELCASE) {
            return 'SqlAnywhere';
        }

        return 'SQLAnywhere';
    }

    /**
     * Check environment
     *
     * @throws Exception\RuntimeException
     * @return void
     */
    public function checkEnvironment()
    {
        if (!$this->extensionLoaded) {
            throw new Exception\RuntimeException('The Sqlanywhere extension is required for this adapter but the extension is not loaded');
        }
    }

    public function getIntClientVersion()
    {
        if (!$this->extensionLoaded) {
            return 0;
        }
        // 11.0.1.2044
        $strClientVersion = sasql_get_client_info();
        $intClientVersion = (int)$strClientVersion;
        return $intClientVersion;
    }

    /**
     *
     * @param string|resource $sqlOrResource
     * @return Statement
     * @throws SQLAnywhereException
     */
    public function createStatement($sqlOrResource = null)
    {
        $statement = clone $this->statementPrototype;
        if (is_resource($sqlOrResource)) {
            $statement->initialize($sqlOrResource);
        } else {
            if (!$this->connection->isConnected()) {
                $this->connection->connect();
            }
            $statement->initialize($this->connection->getResource());
            if (is_string($sqlOrResource)) {
                $statement->setSql($sqlOrResource);
            } elseif ($sqlOrResource !== null) {
                throw new Exception\InvalidArgumentException('createStatement() only accepts an SQL string or a SqlAnywhere resource');
            }
        }
        return $statement;
    }

    /**
     *
     * @param sasql_result $resource
     * @return Result
     * @throws SQLAnywhereException
     */
    public function createResult($resource)
    {
        if (is_bool($resource)) {
            $affectedRows = sasql_affected_rows($this->connection->getResource());
        } else {
            $affectedRows = sasql_num_rows($resource);
        }
        $generatedValue = $this->connection->getLastGeneratedValue();

        $result = clone $this->resultPrototype;
        $result->initialize($resource, $affectedRows, $generatedValue);
        return $result;
    }

    /**
     *
     * @return string
     */
    public function getPrepareType()
    {
        return self::PARAMETERIZATION_POSITIONAL;
    }

    /**
     *
     * @param string $name
     * @param mixed $type
     * @return string
     */
    public function formatParameterName($name, $type = null)
    {
        return '?';
    }

    /**
     *
     * @return mixed
     * @throws SQLAnywhereException
     */
    public function getLastGeneratedValue()
    {
        return $this->getConnection()->getLastGeneratedValue();
    }

    /**
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
