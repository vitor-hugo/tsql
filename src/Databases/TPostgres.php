<?php declare(strict_types=1);

namespace Torugo\Sql\Databases;

use Torugo\Sql\Interfaces\TDatabaseInterface;

class TPostgres implements TDatabaseInterface
{
    private mixed $connection = false;

    private mixed $resultSet = false;

    private array $errorMessages = [];

    public function isConnected(): bool
    {
        return $this->connection !== false;
    }


    public function hasActiveResultSet(): bool
    {
        return $this->isConnected() !== false && $this->resultSet !== false;
    }


    public function connect(
        string $address,
        string $database,
        string $user = "",
        string $password = "",
        string $characterSet = "UTF-8",
        ?int $port = null
    ): bool {
        $this->connection = false;
        $port ??= 5432;

        $connectionParams = [
            "host=$address",
            "port=$port",
            "dbname=$database",
            "user=$user",
            "password=$password",
            "options='--client_encoding=$characterSet'"
        ];

        $connectionString = implode(" ", $connectionParams);
        $this->connection = @pg_connect($connectionString);
        if ( $this->connection === false) {
            $this->errorMessages[] = "Could not connect to $database on $address";
        }

        return $this->isConnected();
    }


    public function close(): void
    {
        $this->clearResultSet();

        if ($this->isConnected()) {
            @pg_close($this->connection);
        }

        $this->connection = false;
    }


    public function clearResultSet(): void
    {
        if ($this->resultSet !== false) {
            @pg_free_result($this->resultSet);
        }

        $this->resultSet = false;
    }


    public function query(string $sql, array $params = []): bool
    {
        if (!$this->isConnected() || empty($sql)) {
            return false;
        }

        $this->clearResultSet();

        $this->resultSet = @pg_query_params($this->connection, $sql, $params);

        if ($this->resultSet === false) {
            return false;
        }

        return true;
    }


    public function numRows(): int
    {
        if ($this->hasActiveResultSet()) {
            return @pg_num_rows($this->resultSet);
        }
        return 0;
    }


    public function fetchArray(): array
    {
        if (!$this->hasActiveResultSet()) {
            return [];
        }

        $row = @pg_fetch_assoc($this->resultSet);

        if (empty($row)) {
            return [];
        }

        return array_change_key_case($row, CASE_LOWER);
    }


    public function fetchAll(): array
    {
        if (!$this->hasActiveResultSet()) {
            return [];
        }

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
        if (!$this->isConnected()) {
            return false;
        }

        $tableName = mb_strtoupper(trim($tableName));

        $query = "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND UPPER(tablename) = \$1";

        $result = $this->query($query, [$tableName]);
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

        $fieldName = mb_strtoupper(trim($fieldName));
        $tableName = mb_strtoupper(trim($tableName));

        $query = "SELECT column_name FROM information_schema.columns WHERE UPPER(table_name)=\$1 and UPPER(column_name)=\$2";

        $result = $this->query($query, [$tableName, $fieldName]);
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

        $tableName = mb_strtoupper(trim($tableName));

        $query = "SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE UPPER(table_name)=\$1";

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
        if ($this->connection) {
            $messages[] = @pg_last_error($this->connection);
        }

        $messages = [...$messages, ...$this->errorMessages];

        return $messages;
    }
}
