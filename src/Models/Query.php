<?php declare(strict_types=1);

namespace Torugo\Sql\Models;

class Query
{
    public function __construct(public string $sql, public array $values)
    {
    }
}
