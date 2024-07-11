<?php declare(strict_types=1);

namespace Torugo\Sql\Interfaces;

interface TDatabaseInterface
{
    /**
     * Indicates if there is an active connection.
     * @return bool
     */
    public function isConnected(): bool;


    /**
     * Indicates if there is an active result set
     * @return bool
     */
    public function hasActiveResultSet(): bool;


    /**
     * Starts a database connection.
     * @param string $address Database address
     * @param string $database Database name
     * @param string $user Authentication user name
     * @param string $password Authentication password
     * @param string $characterSet Used by SqlServer engine
     * @param int $port Port number
     * @return bool
     */
    public function connect(
        string $address,
        string $database,
        string $user = "",
        string $password = "",
        string $characterSet = "UTF-8",
        ?int $port = null
    ): bool;


    /**
     * Closes the connection to the database.
     * @return void
     */
    public function close(): void;


    /**
     * Clears the current result set.
     * @return void
     */
    public function clearResultSet(): void;


    /**
     * Executes a parametrized query.
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function query(string $sql, array $params = []): bool;


    /**
     * Returns the number of records affected by the last query.
     * @return int
     */
    public function numRows(): int;


    /**
     * Returns the next available row of data as an associative array.
     * @return array
     */
    public function fetchArray(): array;


    /**
     * Returns all available rows of data as an associative array.
     * @return array
     */
    public function fetchAll(): array;


    /**
     * Checks if a table exists in the current database.
     * @param string $tableName Searched table name
     * @return bool
     */
    public function tableExists(string $tableName): bool;


    /**
     * Checks whether a field exists in a given table in the current database.
     * @param string $fieldName Searched field name
     * @param string $tableName The name of the table
     * @return bool
     */
    public function fieldExists(string $fieldName, string $tableName): bool;


    /**
     * Returns an associative array with the structure of a given table in the current database, returns `false` if the table does not exist.
     * @param string $tableName The table name
     * @return array|false
     */
    public function getTableStructure(string $tableName): array|false;


    /**
     * Returns one or more triggered message errors.
     * @return array|string array of strings or a single string
     */
    public function getErrors(): array|string;
}
