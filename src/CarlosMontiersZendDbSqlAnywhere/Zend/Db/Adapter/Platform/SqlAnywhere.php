<?php

namespace CarlosMontiersZendDbSqlAnywhere\Zend\Db\Adapter\Platform;

use Zend\Db\Adapter\Platform\PlatformInterface;

/**
 * SAP Sybase SQL Anywhere driver for zend-db platform class.
 *
 * @author Carlos Montiers Aguilera <cmontiers@gmail.com>
 * @link https://github.com/carlos-montiers/zend-db-sqlanywhere
 * @license MIT License
 */
class SqlAnywhere implements PlatformInterface
{

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return 'SqlAnywhere';
    }

    /**
     * Get quote indentifier symbol
     *
     * @return string
     */
    public function getQuoteIdentifierSymbol()
    {
        return '"';
    }

    /**
     * Quote identifier
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        return '"' . str_replace('"', '\\' . '"', $identifier) . '"';
    }

    /**
     * Quote identifier chain
     *
     * @param string|string[] $identifierChain
     * @return string
     */
    public function quoteIdentifierChain($identifierChain)
    {
        $identifierChain = str_replace('"', '\\"', $identifierChain);
        if (is_array($identifierChain)) {
            $identifierChain = implode('"."', $identifierChain);
        }
        return '"' . $identifierChain . '"';
    }

    /**
     * Get quote value symbol
     *
     * @return string
     */
    public function getQuoteValueSymbol()
    {
        return '\'';
    }

    /**
     * Quote Trusted Value
     *
     * The ability to quote values without notices
     *
     * @param
     *            $value
     * @return mixed
     */
    public function quoteTrustedValue($value)
    {
        return $this->quoteValue($value);
    }

    /**
     * Quote value
     *
     * @param string $value
     * @return string
     */
    public function quoteValue($value)
    {
        $value = str_replace(array("'", '\\'), array("''", '\\\\'), $value);
        $value = preg_replace_callback('/[\x00-\x1F\x7F-\xFF]/', function ($chr) {
            return '\\x' . bin2hex($chr[0]);
        }, $value);
        return '\'' . $value . '\'';
    }

    /**
     * Quote value list
     *
     * @param string|string[] $valueList
     * @return string
     */
    public function quoteValueList($valueList)
    {
        if (!is_array($valueList)) {
            return $this->quoteValue($valueList);
        }

        $value = reset($valueList);
        do {
            $valueList[key($valueList)] = $this->quoteValue($value);
        } while ($value = next($valueList));
        return implode(', ', $valueList);
    }

    /**
     * Get identifier separator
     *
     * @return string
     */
    public function getIdentifierSeparator()
    {
        return '.';
    }

    /**
     * Quote identifier in fragment
     *
     * @param string $identifier
     * @param array $safeWords
     * @return string
     */
    public function quoteIdentifierInFragment($identifier, array $safeWords = array())
    {
        $parts = preg_split('#([\.\s\W])#', $identifier, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($safeWords) {
            $safeWords = array_flip($safeWords);
            $safeWords = array_change_key_case($safeWords, CASE_LOWER);
        }
        foreach ($parts as $i => $part) {
            if ($safeWords && isset($safeWords[strtolower($part)])) {
                continue;
            }

            switch ($part) {
                case ' ':
                case '.':
                case '*':
                case 'AS':
                case 'As':
                case 'aS':
                case 'as':
                    break;
                default:
                    $parts[$i] = '"' . str_replace('"', '\\' . '"', $part) . '"';
            }
        }

        return implode('', $parts);
    }
}
