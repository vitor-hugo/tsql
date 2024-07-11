<?php declare(strict_types=1);

namespace Torugo\Sql\Databases;

use Torugo\Sql\Interfaces\TDatabaseInterface;

class TSqlServer implements TDatabaseInterface
{
    /**
     * @var resource|false
     */
    private $connection = false;

    /**
     * @var resource|false
     */
    private $resultSet = false;


    public function isConnected(): bool
    {
        return $this->connection != false;
    }


    public function hasActiveResultSet(): bool
    {
        return $this->isConnected() && $this->resultSet != false;
    }


    public function connect(
        string $address,
        string $database,
        string $user = "",
        string $password = "",
        string $characterSet = "UTF-8",
        ?int $port = 1433
    ): bool {
        $connectionParams = [
            "Database" => $database,
            "UID" => $user,
            "PWD" => $password,
            "CharacterSet" => "UTF-8",
            "ConnectionPooling" => "1",
            "MultipleActiveResultSets" => "0",
            "LoginTimeout" => 0,
            "TrustServerCertificate" => "1",
        ];

        $port ??= 1433;
        $this->connection = sqlsrv_connect("$address, $port", $connectionParams);

        return $this->isConnected();
    }


    public function close(): void
    {
        $this->clearResultSet();
        if ($this->isConnected()) {
            sqlsrv_close($this->connection);
        }

        $this->connection = false;
    }


    public function clearResultSet(): void
    {
        if ($this->hasActiveResultSet()) {
            sqlsrv_free_stmt($this->resultSet);
        }
        $this->resultSet = false;
    }


    public function query(string $sql, array $params = []): bool
    {
        if (!$this->isConnected() || empty($sql)) {
            return false;
        }

        $this->clearResultSet();

        $this->resultSet = sqlsrv_query(
            $this->connection,
            $sql,
            $params,
            ["Scrollable" => "static"]
        );

        return $this->hasActiveResultSet();
    }


    public function numRows(): int
    {
        if (!$this->hasActiveResultSet()) {
            return 0;
        }

        return (int) sqlsrv_num_rows($this->resultSet);
    }


    public function fetchArray(): array
    {
        if (!$this->hasActiveResultSet()) {
            return [];
        }

        $row = sqlsrv_fetch_array($this->resultSet, SQLSRV_FETCH_ASSOC);

        if (empty($row)) {
            return [];
        }

        return $row;
    }


    public function fetchAll(): array
    {
        $rows = [];

        while ($row = $this->fetchArray()) {
            $rows[] = $row;
        }

        return $rows;
    }


    public function tableExists(string $tableName): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        $tableName = trim($tableName);
        $result = $this->query(
            "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?",
            [$tableName]
        );

        $numRows = $this->numRows();

        $this->clearResultSet();

        if ($result == false || $numRows != 1) {
            return false;
        }

        return true;
    }


    public function fieldExists(string $fieldName, string $tableName): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        $fieldName = trim($fieldName);
        $tableName = trim($tableName);

        if (empty($fieldName) || empty($tableName)) {
            return false;
        }

        $result = $this->query(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=? AND COLUMN_NAME=?",
            [$tableName, $fieldName]
        );

        $numRows = $this->numRows();

        $this->clearResultSet();

        if ($result == false || $numRows != 1) {
            return false;
        }

        return true;
    }


    public function getTableStructure(string $tableName): array|false
    {
        if (!$this->isConnected()) {
            return false;
        }

        $query = "SELECT column_name, data_type, character_maximum_length FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=? ORDER BY ORDINAL_POSITION";
        $result = $this->query($query, [$tableName]);

        if ($result == false || $this->numRows() == 0) {
            $this->clearResultSet();
            return false;
        }

        $structure = [];

        while ($field = $this->fetchArray()) {
            $maxLength = $field["character_maximum_length"];
            $maxLength = empty($maxLength) ? 0 : (int) $maxLength;

            $structure[$field["column_name"]] = [
                "type" => mb_strtolower($field["data_type"]),
                "maxLength" => $maxLength,
            ];
        }

        $this->clearResultSet();

        return $structure;
    }


    public function getErrors(): array|string
    {
        $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS) ?? [];

        if (count($errors) === 0) {
            return "";
        }

        if (count($errors) === 1) {
            return $errors[0]["message"];
        }

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error["message"];
        }

        return $messages;
    }
}
