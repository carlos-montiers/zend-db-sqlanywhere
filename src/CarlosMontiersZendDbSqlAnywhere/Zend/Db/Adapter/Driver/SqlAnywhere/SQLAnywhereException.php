<?php

namespace CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Driver\SqlAnywhere;

use Throwable;
use Zend\Db\Adapter\Exception;

/**
 * SAP Sybase SQL Anywhere driver for zend-db exception class
 * Code based on SQLAnywhereException class of Doctrine Project
 *
 * @author Carlos Montiers Aguilera <cmontiers@gmail.com>
 * @link https://github.com/carlos-montiers/zend-db-sqlanywhere
 * @license MIT License
 *
 * @author Steve Müller <st.mueller@dzh-online.de>
 * @link http://www.doctrine-project.org
 * @license MIT License
 */
class SQLAnywhereException extends Exception\ErrorException
{

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Helper method to turn SQL Anywhere error into exception.
     *
     * @param resource|null $conn
     *            The SQL Anywhere connection resource to retrieve the last error from.
     * @param resource|null $stmt
     *            The SQL Anywhere statement resource to retrieve the last error from.
     *
     * @return SQLAnywhereException
     *
     * @throws \InvalidArgumentException
     */
    public static function fromSQLAnywhereError($conn = null, $stmt = null)
    {
        if (null !== $conn && !(is_resource($conn))) {
            throw new \InvalidArgumentException('Invalid SQL Anywhere connection resource given: ' . $conn);
        }
        if (null !== $stmt && !(is_resource($stmt))) {
            throw new \InvalidArgumentException('Invalid SQL Anywhere statement resource given: ' . $stmt);
        }
        $state = $conn ? sasql_sqlstate($conn) : sasql_sqlstate();
        $code = null;
        $message = null;
        /**
         * Try retrieving the last error from statement resource if given
         */
        if ($stmt) {
            $code = sasql_stmt_errno($stmt);
            $message = sasql_stmt_error($stmt);
        }
        /**
         * Try retrieving the last error from the connection resource
         * if either the statement resource is not given or the statement
         * resource is given but the last error could not be retrieved from it (fallback).
         * Depending on the type of error, it is sometimes necessary to retrieve
         * it from the connection resource even though it occurred during
         * a prepared statement.
         */
        if ($conn && !$code) {
            $code = sasql_errorcode($conn);
            $message = sasql_error($conn);
        }
        /**
         * Fallback mode if either no connection resource is given
         * or the last error could not be retrieved from the given
         * connection / statement resource.
         */
        if (!$conn || !$code) {
            $code = sasql_errorcode();
            $message = sasql_error();
        }
        if ($message) {
            $message = 'SQLSTATE [' . $state . '] [' . $code . '] ' . $message;
        } else {
            $message = 'SQL Anywhere error occurred but no error message was retrieved from driver.';
        }
        return new self($message);
    }
}
