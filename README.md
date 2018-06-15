# Zend Db Sqlanywhere
ZF2 module that provides functionality for Zend Db for connect to a Sybase|SAP SQL Anywhere database.

# Description
Zend Db by default not comes with a driver for connect to a Sybase|SAP SQL Anywhere database.

# Installation
1. Install sqlanywhere php extension from here: https://wiki.scn.sap.com/wiki/display/SQLANY/The+SAP+SQL+Anywhere+PHP+Module
2. Require the module:
```bash
composer require carlos-montiers/zend-db-sqlanywhere:dev-master
```
3. Optional: If you are using Zend DB inside Zend Framework 2 you need enable this module in your `application.config.php` file.
4. Use the next adapter for setup a database connection: `CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Adapter`

# Test example: /test.php
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;

//Connect to a database on this computer
//for connect to a database on another computer
//you must provide host and port
$configDb = array(
    'driver' => 'SqlAnywhere',
    'userid' => 'carlos',
    'password' => 'anypass',
    'servername' => 'localhost',
    'databasename' => 'main',
    'host' => null,
    'port' => null,
);

$adapter = new CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Adapter($configDb);

$sql = new Sql($adapter);
$select = $sql->select();
$where = new Where();

$select
    ->columns(array(
        'id_country',
        'name',
    ))
    ->from(array(
        'Country' => 'countries'
    ))
    ->where($where);

$where->equalTo('name', 'Chile');

$preparedSelect = $sql->prepareStatementForSqlObject($select);
$result = $preparedSelect->execute();
if ($result->isQueryResult()) {
    $resultSet = clone $adapter->getQueryResultSetPrototype();
    $resultSet->initialize($result);
    foreach ($resultSet as $row) {
        var_dump($row);
    }
}
// Outputs:
// object(ArrayObject)#45 (1) {
//  ["storage":"ArrayObject":private]=>
//  array(2) {
//    ["id_country"]=>
//    int(1)
//    ["name"]=>
//    string(5) "Chile"
//  }
//}

echo PHP_EOL;

$resultSet = $adapter->query('SELECT id_country, name FROM countries WHERE name = ?', array('Chile'));
foreach ($resultSet as $row) {
    var_dump($row);
}

// Outputs:
// object(ArrayObject)#49 (1) {
//  ["storage":"ArrayObject":private]=>
//  array(2) {
//    ["id_country"]=>
//    int(1)
//    ["name"]=>
//    string(5) "Chile"
//  }
// }

```

# Changelog
0.2.6 : First stable version
