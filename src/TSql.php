<?php declare(strict_types=1);

namespace Torugo\Sql;

use InvalidArgumentException;
use Torugo\Sql\Databases\TMySql;
use Torugo\Sql\Databases\TPostgres;
use Torugo\Sql\Databases\TSqlServer;
use Torugo\Sql\Enums\DBEngine;
use Torugo\Sql\Interfaces\TDatabaseInterface;

class TSql
{
    /**
     * Database handler instance
     * @var TDatabaseInterface
     */
    private TDatabaseInterface $database;

    /**
     * Last sql command executed by the method query(). Used on query logging.
     * @var string
     */
    private string $lastExecutedQuerySql = "";

    /**
     * Last parameters used by the method query(). Used on query logging.
     * @var array
     */
    private array $lastExecutedQueryParams = [];

    /**
     * Path to the query log file. Used on query logging.
     * @var string
     */
    private string $queryLogFilePath = "";
    private bool $queryLogginIsEnabled = false;

    /**
     * Path to the errors log file. Used by the errors handler.
     * @var string
     */
    private string $errorLogFilePath = "";
    private bool $errorLoggingIsEnabled = false;


    /**
     * @param \Torugo\Sql\Enums\DBEngine $dbEngine
     */
    public function __construct(private DBEngine $dbEngine)
    {
        $this->initDatabaseObject();
    }


    /**
     * Instantiates the correct database handler
     * @throws \Exception
     * @return void
     */
    private function initDatabaseObject()
    {
        $this->database = match ($this->dbEngine) {
            DBEngine::MariaDB => new TMySql(),
            DBEngine::MySql => new TMySql(),
            DBEngine::Postgres => new TPostgres(),
            DBEngine::SqlServer => new TSqlServer(),
        };
    }


    /**
     * Indicates if there is an active connection.
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->database->isConnected();
    }


    /**
     * Indicates if there is an active result set
     * @return bool
     */
    public function hasActiveResultSet(): bool
    {
        return $this->database->hasActiveResultSet();
    }


    /**
     * Starts a database connection.
     * @param string $address Database address
     * @param string $database Database name
     * @param string $user Authentication user name
     * @param string $password Authentication password
     * @param string $characterSet Used by SqlServer engine
     * @return bool
     */
    public function connect(
        string $address,
        string $database,
        string $user = "",
        string $password = "",
        string $characterSet = "UTF-8",
        ?int $port = null
    ): bool {
        $connection = $this->database->connect($address, $database, $user, $password, $characterSet);

        if ($connection === false) {
            $this->handleError();
            return false;
        }

        return true;
    }


    /**
     * Closes an open connection and releases resourses associated with the connection.
     * @return void
     */
    public function close(): void
    {
        $this->database->close();
    }


    /**
     * Frees all resources for the specified statement. The statement cannot be used after clearing the result.
     * @return void
     */
    public function clearResultSet(): void
    {
        $this->database->clearResultSet();
    }


    /**
     * Executes a parametrized query.
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function query(string $sql, array $params = []): bool
    {
        $sql = trim($sql);

        $this->lastExecutedQuerySql = $sql;
        $this->lastExecutedQueryParams = $params;
        $this->logQuery($sql, $params);

        $result = $this->database->query($sql, $params);

        if ($result == false) {
            $this->handleError();
            return false;
        }

        return true;
    }


    /**
     * Returns the number of records affected by the last query.
     * @return int
     */
    public function numRows(): int
    {
        return $this->database->numRows();
    }


    /**
     * Returns the next available row of data as an associative array.
     * @return array
     */
    public function fetchArray(): array
    {
        $row = $this->database->fetchArray();
        $row = array_change_key_case($row, CASE_LOWER);
        return $row;
    }


    /**
     * Returns all available rows of data as an associative array.
     * @return array
     */
    public function fetchAll(): array
    {
        $rows = [];

        while ($row = $this->fetchArray()) {
            $rows[] = $row;
        }

        return $rows;
    }


    /**
     * Checks if a table exists in the current database.
     * @param string $tableName Searched table name
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        $exists = $this->database->tableExists($tableName);
        return $exists;
    }


    /**
     * Checks whether a field exists in a given table in the current database.
     * @param string $fieldName Searched field name
     * @param string $tableName The name of the table
     * @return bool
     */
    public function fieldExists(string $fieldName, string $tableName): bool
    {
        $exists = $this->database->fieldExists($fieldName, $tableName);
        return $exists;
    }


    public function getTableStructure(string $tableName): array|false
    {
        $structure = $this->database->getTableStructure($tableName);

        if ($structure === false) {
            return false;
        }

        return $structure;
    }


    /**
     * Saves the sql and parameters of a query in a log file.
     * @param string $sql Sql command
     * @param array $params Parametrized query parameters
     * @return void
     */
    private function logQuery(string $sql, array $params): void
    {
        if (!$this->queryLogginIsEnabled) {
            return;
        }

        $paramsStr = join(", ", $params);

        $text = "Date: " . date("Y/m/d H:i:s") . PHP_EOL;
        $text .= "Query: $sql" . PHP_EOL;
        $text .= "Params: $paramsStr" . PHP_EOL;
        $text .= PHP_EOL;

        $file = fopen($this->queryLogFilePath, 'a');
        fwrite($file, $text);
        fclose($file);
    }


    private function handleError()
    {
        if ($this->errorLoggingIsEnabled) {
            $this->logError();
        } else {
            $this->throwError();
        }
    }


    private function throwError()
    {
        $error = $this->database->getErrors()[0] ?? "Unkown error";
        if (strlen($error)) {
            throw new \Exception($error);
        }
    }


    /**
     * Saves the dtabase error in a log file
     * @return void
     */
    private function logError()
    {
        $errors = $this->database->getErrors();

        $text = "Date: " . date("Y/m/d H:i:s") . PHP_EOL;

        if (strlen($this->lastExecutedQuerySql) > 0) {
            $text .= "Query: " . $this->lastExecutedQuerySql . PHP_EOL;
            $text .= "Params: " . implode(", ", $this->lastExecutedQueryParams) . PHP_EOL;
        }

        $text .= "Messages: " . PHP_EOL;
        foreach ($errors as $error) {
            $text .= " - $error" . PHP_EOL;
        }

        $text .= "Backtrace: " . PHP_EOL;
        $backtraces = debug_backtrace();
        foreach ($backtraces as $backtrace) {
            $line = $backtrace["line"];
            $file = $backtrace["file"];
            $text .= "  (Line $line) - $file" . PHP_EOL;
        }

        $text .= PHP_EOL;

        $file = fopen($this->errorLogFilePath, 'a');
        fwrite($file, $text);
        fclose($file);
    }


    /**
     * Enables queries logging, saving all executed queries sql statement and its parameters
     * @param string $path Path to the log file e.g. '/var/logs/queries.log'
     * @throws \InvalidArgumentException When path is invalid or not writable
     * @return void
     */
    public function enableQueryLogging(string $path): void
    {
        if ($this->logFileExistsAndIsEditable($path)) {
            $this->queryLogFilePath = $path;
            $this->queryLogginIsEnabled = true;
        } else {
            $this->disableQueryLogging();
            throw new InvalidArgumentException("The query log file path '$path' is invalid.");
        }
    }


    /**
     * Disables queries logging.
     * @return void
     */
    public function disableQueryLogging(): void
    {
        $this->queryLogFilePath = "";
        $this->queryLogginIsEnabled = false;
    }


    /**
     * Enables errors logging, saving all errors in a log file, otherwise errors will be thrown.
     * @param string $path Path to the log file e.g. '/var/logs/errors.log'
     * @throws \InvalidArgumentException When path is invalid or not writable
     * @return void
     */
    public function enableErrorLogging(string $path): void
    {
        if ($this->logFileExistsAndIsEditable($path)) {
            $this->errorLogFilePath = $path;
            $this->errorLoggingIsEnabled = true;
        } else {
            $this->disableErrorLogging();
            throw new InvalidArgumentException("The error log file path '$path' is invalid.");
        }
    }


    /**
     * Disables error logging.
     * @return void
     */
    public function disableErrorLogging(): void
    {
        $this->errorLogFilePath = "";
        $this->errorLoggingIsEnabled = false;
    }


    /**
     * Checks if the log file exists or can be created and is editable
     * @param string $filePath
     * @throws \InvalidArgumentException
     * @return bool
     */
    private function logFileExistsAndIsEditable(string $filePath): bool
    {
        $file = @fopen($filePath, "a");

        if ($file === false) {
            return false;
        }

        @fclose($file);
        return true;
    }
}
