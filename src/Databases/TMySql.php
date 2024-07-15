<?php declare(strict_types=1);

namespace Torugo\Sql\Databases;

use Torugo\Sql\Interfaces\TDatabaseInterface;

class TMySql implements TDatabaseInterface
{
    /**
     * @var bool|\mysqli
     */
    private bool|\mysqli $connection = false;

    /**
     * @var bool|\mysqli_stmt
     */
    private bool|\mysqli_stmt $statement = false;

    /**
     * @var false|\mysqli_result
     */
    private bool|\mysqli_result $resultSet = false;

    /**
     * Current database name
     * @var string
     */
    private string $database = "";


    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_STRICT);
    }


    public function isConnected(): bool
    {
        return $this->connection != false;
    }


    public function hasActiveResultSet(): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        if ($this->resultSet == false) {
            return false;
        }

        return true;
    }


    public function connect(
        string $address,
        string $database,
        string $user = "",
        string $password = "",
        string $characterSet = "UTF-8",
        ?int $port = 3306
    ): bool {
        $this->connection = false;

        try {
            $this->connection = new \mysqli($address, $user, $password, $database, $port);
        } catch (\Throwable $_) {
            return false;
        }

        $this->database = $database;

        return $this->isConnected();
    }


    public function close(): void
    {
        $this->clearResultSet();

        if ($this->isConnected()) {
            mysqli_close($this->connection);
        }

        $this->connection = false;
    }


    public function clearResultSet(): void
    {
        if ($this->statement != false) {
            mysqli_stmt_free_result($this->statement);
        }

        if ($this->resultSet != false) {
            mysqli_free_result($this->resultSet);
        }

        $this->statement = false;
        $this->resultSet = false;
    }


    public function query(string $sql, array $params = []): bool
    {
        if (!$this->isConnected() || empty($sql)) {
            return false;
        }

        $this->clearResultSet();

        $this->statement = $this->connection->prepare($sql);

        if ($this->statement == false) {
            return false;
        }

        if (count($params)) {
            $types = $this->buildParametersTypes($params);
            $this->statement->bind_param($types, ...$params);
        }

        $this->statement->execute();

        $this->resultSet = $this->statement->get_result();

        return true;
    }


    /**
     * Builds a string with parameters types to be used on parametrized queries
     * @param array $params Query parameters
     * @return string
     */
    public function buildParametersTypes(array $params): string
    {
        $types = "";
        foreach ($params as $param) {
            $types .= $this->getParamType($param);
        }
        return $types;
    }


    /**
     * Returns the appropriate character for each data type
     * @param mixed $value
     * @return string
     */
    public function getParamType(mixed $value): string
    {
        $type = gettype($value);

        switch ($type) {
            case 'string':
            case 'null':
                return 's';

            case 'boolean':
            case 'integer':
                return 'i';

            case 'double':
            case 'float':
                return 'd';

            default:
                return "b";
        }
    }


    public function numRows(): int
    {
        if ($this->hasActiveResultSet()) {
            return mysqli_num_rows($this->resultSet);
        }

        return 0;
    }


    public function fetchArray(): array
    {
        if (!$this->hasActiveResultSet()) {
            return [];
        }

        $row = $this->resultSet->fetch_assoc();

        if (empty($row)) {
            return [];
        }

        return array_change_key_case($row, CASE_LOWER);
    }


    public function fetchAll(): array
    {
        $rows = [];

        while ($row = $this->fetchArray()) {
            if (!empty($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }


    public function tableExists(string $tableName): bool
    {
        if ($this->isConnected() == false) {
            return false;
        }

        $database = mb_strtoupper($this->database);
        $tableName = mb_strtoupper(trim($tableName));

        $sql = "SELECT TABLE_SCHEMA, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES ";
        $sql .= "WHERE UPPER(TABLE_SCHEMA)=? AND UPPER(TABLE_NAME) = ?";
        $result = $this->query($sql, [$database, $tableName]);
        $numRows = $this->numRows();

        $this->clearResultSet();

        if ($result == false || $numRows != 1) {
            return false;
        }

        return true;
    }


    public function fieldExists(string $fieldName, string $tableName): bool
    {
        if ($this->isConnected() == false) {
            return false;
        }

        $database = mb_strtoupper($this->database);
        $fieldName = mb_strtoupper(trim($fieldName));
        $tableName = mb_strtoupper(trim($tableName));

        $sql = "SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ";
        $sql .= "WHERE UPPER(TABLE_SCHEMA)=? AND UPPER(TABLE_NAME)=? AND UPPER(COLUMN_NAME)=?";
        $result = $this->query($sql, [$database, $tableName, $fieldName]);
        $numRows = $this->numRows();

        $this->clearResultSet();

        if ($result == false || $numRows != 1) {
            return false;
        }

        return true;
    }


    public function getTableStructure(string $tableName): array|false
    {
        if ($this->isConnected() == false) {
            return false;
        }

        $tableName = mb_strtoupper($tableName);

        $sql = "SELECT column_name, data_type, character_maximum_length ";
        $sql .= "FROM INFORMATION_SCHEMA.COLUMNS ";
        $sql .= "WHERE UPPER(TABLE_NAME)=? ";
        $sql .= "ORDER BY ORDINAL_POSITION";

        $result = $this->query($sql, [$tableName]);

        if ($result == false || $this->numRows() == 0) {
            $this->clearResultSet();
            return false;
        }

        $structure = [];

        while ($field = $this->fetchArray()) {
            $maxLength = $field["character_maximum_length"];
            $maxLength = empty($maxLength) ? 0 : (int) $maxLength;

            $structure[$field["column_name"]] = [
                "type" => $field["data_type"],
                "maxLength" => $maxLength,
            ];
        }

        $this->clearResultSet();

        return $structure;
    }


    public function getErrors(): array
    {
        $messages = [];

        $connError = mysqli_connect_error();
        if ($connError) {
            $messages[] = $connError;
        }

        $errors = [];

        if ($this->connection !== false) {
            $err = mysqli_error_list($this->connection);
            array_push($errors, $err);
        }

        foreach ($errors as $error) {
            if ($error) {
                $messages[] = $error[0]["error"];
            }
        }

        return $messages;
    }
}
