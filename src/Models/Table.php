<?php declare(strict_types=1);

namespace Torugo\Sql\Models;

class Table
{
    /**
     * @param string $name Table name.
     * @param string $alias Table alias.
     */
    public function __construct(
        private string $name,
        private string $alias = ""
    ) {
        $this->validateTableName($name);

        if (strlen($alias)) {
            $this->validateTableName($alias);
        }
    }


    /**
     * Checks if the table name has invalid characters
     * @param string $name Table name
     * @return string
     * @throws \InvalidArgumentException
     */
    private function validateTableName(string $name)
    {
        $name = trim($name);

        if (!preg_match("/^[\p{L}_][\p{L}\p{N}@$#_]{1,127}$/", $name)) {
            throw new \InvalidArgumentException("Invalid table name '$name'.");
        }

        return $name;
    }


    /**
     * Returns the name of the table.
     * @param bool $withAlias If [true] returns 'TableName as Alias'
     * @return string
     */
    public function getName(bool $withAlias = false): string
    {
        if ($withAlias && strlen($this->alias)) {
            return "{$this->name} as {$this->alias}";
        }

        return $this->name;
    }


    public function setName(string $name): void
    {
        $this->name = $this->validateTableName($name);
    }


    public function getAlias(): string
    {
        return $this->alias;
    }


    public function setAlias(string $alias): void
    {
        $this->alias = $this->validateTableName($alias);
    }
}
