<?php

namespace CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Driver\SqlAnywhere;

use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Adapter\Exception;
use Zend\Db\Adapter\Profiler\ProfilerAwareInterface;
use Zend\Db\Adapter\Profiler\ProfilerInterface;

/**
 * SAP Sybase SQL Anywhere driver for zend-db connection class.
 *
 * @author Carlos Montiers Aguilera <cmontiers@gmail.com>
 * @link https://github.com/carlos-montiers/zend-db-sqlanywhere
 * @license MIT License
 */
class Connection implements ConnectionInterface, ProfilerAwareInterface
{

    /**
     *
     * @var array
     */
    protected $connectionParameters = array();

    /**
     *
     * @var SqlAnywhere
     */
    protected $driver = null;

    /**
     *
     * @var sasql_conn
     */
    protected $resource = null;

    /**
     *
     * @var ProfilerInterface|null
     */
    protected $profiler;

    /**
     * Constructor
     *
     * @param array|sasql_conn $connectionInfo
     * @throws \Zend\Db\Adapter\Exception\InvalidArgumentException
     */
    public function __construct($connectionInfo)
    {
        if (is_array($connectionInfo)) {
            $this->setConnectionParameters($connectionInfo);
        } elseif (is_resource($connectionInfo)) {
            $this->setResource($connectionInfo);
        } else {
            throw new Exception\InvalidArgumentException('$connection must be an array of parameters or a resource');
        }
    }

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
     * {@inheritdoc}
     *
     * @throws SQLAnywhereException
     */
    public function getCurrentSchema()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $sql = 'SELECT DB_NAME()';
        $result = sasql_query($this->resource, $sql);
        if (!is_resource($result)) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        $row = sasql_fetch_row($result);
        if (!$row) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        return $row[0];
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function isConnected()
    {
        return (is_resource($this->resource));
    }

    /**
     *
     * {@inheritdoc}
     *
     * @throws SQLAnywhereException
     */
    public function connect()
    {
        if ($this->resource) {
            return $this;
        }

        $userid = null;
        $password = null;
        $serverName = null;
        $databaseName = null;
        $host = null;
        $port = null;
        $charset = null;

        foreach ($this->connectionParameters as $key => $value) {
            switch (strtolower($key)) {
                case 'userid':
                    $userid = (string)$value;
                    break;
                case 'password':
                    $password = (string)$value;
                    break;
                case 'servername':
                    $serverName = (string)$value;
                    break;
                case 'databasename':
                    $databaseName = (string)$value;
                    break;
                case 'host':
                    $host = (string)$value;
                    break;
                case 'port':
                    $port = (int)$value;
                    break;
                case 'charset':
                    $charset = (string)$value;
                    break;
            }
        }

        $con_str = $this->buildConnectionString($userid, $password, $serverName, $databaseName, $host, $port, $charset);

        $this->resource = sasql_connect($con_str);

        if (!$this->resource) {
            throw SQLAnywhereException::fromSQLAnywhereError();
        }

        // Disable PHP warnings on error.
        if (!sasql_set_option($this->resource, 'verbose_errors', false)) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        // Enable auto committing by default.
        if (!sasql_set_option($this->resource, 'auto_commit', 'on')) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        // Enable exact, non-approximated row count retrieval.
        if (!sasql_set_option($this->resource, 'row_counts', true)) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }

        return $this;
    }

    /**
     * @param string $userid
     * @param string $password
     * @param string $serverName
     * @param string $databaseName
     * @param string|null $host
     * @param string|null $port
     * @param string|null $charset
     * @return string
     */
    protected function buildConnectionString($userid, $password, $serverName, $databaseName, $host = null, $port = null, $charset = null)
    {
        $oldClient = $this->driver->getIntClientVersion() <= 11;

        $connectionParams = array();

        if (!empty($userid)) {
            $connectionParams['UID'] = $userid;
        }
        if (!empty($password)) {
            $connectionParams['PWD'] = $password;
        }
        if (!empty($serverName)) {
            if ($oldClient) {
                $connectionParams['ENG'] = $serverName;
            } else {
                $connectionParams['SERVER'] = $serverName;
            }
        }
        if (!empty($databaseName)) {
            $connectionParams['DBN'] = $databaseName;
        }
        if (!empty($host)) {
            if (!empty($port)) {
                $hostParam = $host . ':' . $port;
            } else {
                $hostParam = $host;
            }
            if ($oldClient) {
                $connectionParams['LINKS'] = "tcpip(IP=$hostParam)";
            } else {
                $connectionParams['HOST'] = $hostParam;
            }
        }
        if (!empty($charset)) {
            $connectionParams['CS'] = $charset;
        } else {
            $connectionParams['CS'] = 'utf-8';
        }

        $conStr = implode(';', array_map(function ($key, $value) {
            return $key . '=' . $value;
        }, array_keys($connectionParams), $connectionParams));

        return $conStr;
    }

    /**
     *
     * @return sasql_conn
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set resource
     *
     * @param sasql_conn $resource
     * @return self Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setResource($resource)
    {
        if (get_resource_type($resource) !== 'SQLAnywhere connection') {
            throw new Exception\InvalidArgumentException('Invalid resource provided to ' . __CLASS__);
        }
        $this->resource = $resource;

        return $this;
    }

    /**
     *
     * @return null|ProfilerInterface
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return self Provides a fluent interface
     */
    public function setProfiler(ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            sasql_close($this->resource);
            $this->resource = null;
        }

        return $this;
    }

    /**
     * Get connection parameters
     *
     * @return array
     */
    public function getConnectionParameters()
    {
        return $this->connectionParameters;
    }

    /**
     *
     * @param array $connectionParameters
     * @return self Provides a fluent interface
     */
    public function setConnectionParameters(array $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @throws SQLAnywhereException
     */
    public function beginTransaction()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if (!sasql_set_option($this->resource, 'auto_commit', 'off')) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @throws SQLAnywhereException
     */
    public function commit()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if (!sasql_commit($this->resource)) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        $this->endTransaction();

        return $this;
    }

    /**
     *
     * @throws SQLAnywhereException
     */
    protected function endTransaction()
    {
        if (!sasql_set_option($this->resource, 'auto_commit', 'on')) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @throws SQLAnywhereException
     */
    public function rollback()
    {
        if (!$this->isConnected()) {
            throw new Exception\RuntimeException('Must be connected before you can rollback.');
        }

        if (!sasql_rollback($this->resource)) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        $this->endTransaction();

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @throws SQLAnywhereException
     * @throws Exception\RuntimeException
     */
    public function execute($sql)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if (!$this->driver instanceof SqlAnywhere) {
            throw new Exception\RuntimeException('Connection is missing an instance of SqlAnywhere');
        }

        if ($this->profiler) {
            $this->profiler->profilerStart($sql);
        }

        $returnValue = sasql_query($this->resource, $sql);

        if ($this->profiler) {
            $this->profiler->profilerFinish();
        }

        $result = $this->driver->createResult($returnValue);

        return $result;
    }

    /**
     * Prepare
     *
     * @param string $sql
     * @return string
     * @throws SQLAnywhereException
     */
    public function prepare($sql)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $statement = $this->driver->createStatement($sql);

        return $statement;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @throws SQLAnywhereException
     * @return mixed
     */
    public function getLastGeneratedValue($name = null)
    {
        if (!$this->resource) {
            $this->connect();
        }

        if (null === $name) {
            return sasql_insert_id($this->resource);
        }

        $sql = 'SELECT ' . $name . '.CURRVAL as as Current_Identity';
        $result = sasql_query($this->resource, $sql);
        if (!is_resource($result)) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }
        $row = sasql_fetch_assoc($result);
        if (!$row) {
            throw SQLAnywhereException::fromSQLAnywhereError($this->resource);
        }

        return $row['Current_Identity'];
    }
}
