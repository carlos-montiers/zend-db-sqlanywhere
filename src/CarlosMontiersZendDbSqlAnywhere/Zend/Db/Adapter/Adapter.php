<?php

namespace CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter;

use CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Driver\SqlAnywhere as SqlAnywhereDriver;
use CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Platform\SqlAnywhere as SqlAnywherePlatform;
use Zend\Db\Adapter\Adapter as ZendDbAdapter;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Adapter\Profiler\ProfilerInterface;
use Zend\Db\ResultSet\ResultSetInterface;

/**
 * SAP Sybase SQL Anywhere driver for zend-db adapter class.
 *
 * @author Carlos Montiers Aguilera <cmontiers@gmail.com>
 * @link https://github.com/carlos-montiers/zend-db-sqlanywhere
 * @license MIT License
 */
class Adapter extends ZendDbAdapter
{

    /**
     *
     * @param DriverInterface|array $driver
     * @param PlatformInterface $platform
     * @param ResultSetInterface $queryResultPrototype
     * @param ProfilerInterface $profiler
     * @throws \Zend\Db\Sql\Exception\InvalidArgumentException
     */
    public function __construct($driver, $platform = null, $queryResultPrototype = null, $profiler = null)
    {
        if ($platform === null) {
            if (is_array($driver)) {
                if ($driver['driver'] === 'SqlAnywhere') {
                    $platform = new SqlAnywherePlatform();
                }
            } elseif ($driver instanceof SqlAnywhereDriver) {
                $platform = new SqlAnywherePlatform();
            }
        }
        parent::__construct($driver, $platform, $queryResultPrototype, $profiler);
    }

    /**
     *
     * @param array $parameters
     * @return DriverInterface
     * @throws \InvalidArgumentException
     * @throws \Zend\Db\Sql\Exception\InvalidArgumentException
     */
    protected function createDriver($parameters)
    {
        $driverName = strtolower($parameters['driver']);
        if ($driverName === 'sqlanywhere') {
            $driver = new Driver\SqlAnywhere\SqlAnywhere($parameters);
            return $driver;
        }
        return parent::createDriver($parameters);
    }
}
