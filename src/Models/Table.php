<?php declare(strict_types=1);

namespace Torugo\Sql\Models;

class Table
{
    public function __construct(public string $name, public string $alias = "")
    {
        $this->validateTableName($name);
        if (strlen($alias)) {
            $this->validateTableName($alias);
        }
    }

    private function validateTableName(string $name)
    {
        $name = trim($name);

        if (!preg_match("/^[\p{L}_][\p{L}\p{N}@$#_]{1,127}$/", $name)) {
            throw new \InvalidArgumentException("Invalid table name '$name'.");
        }

        return $name;
    }

    public function getName(): string
    {
        $alias = strlen($this->alias) ? " as {$this->alias}" : "";
        return "{$this->name}$alias";
    }
}
