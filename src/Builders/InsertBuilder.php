<?php declare(strict_types=1);

namespace Torugo\Sql\Builders;

use Torugo\Sql\Enums\DBEngine;
use Torugo\Sql\Models\Query;
use Torugo\Sql\Models\Table;

class InsertBuilder
{
    public function __construct(
        private DBEngine $engine,
        private array $tableStructure
    ) {
    }

    public function build(Table $table, array $fieldValue): Query
    {
        $fieldValue = array_change_key_case($fieldValue, CASE_LOWER);

        $fields = []; // (field1, field2, field3)
        $values = []; // ["A", "B", "C"]
        $marks = [];  // (?, ?, ?)

        $count = 1;

        foreach ($fieldValue as $field => $value) {
            if (!array_key_exists($field, $this->tableStructure)) {
                continue;
            }

            $fields[] = $field;
            $values[] = $value;
            $marks[] = $this->engine === DBEngine::Postgres ? "\$$count" : "?";
            $count++;
        }

        $fields = join(", ", $fields);
        $marks = join(", ", $marks);

        $query = "INSERT INTO {$table->getName()} ($fields) values($marks)";

        return new Query($query, $values);
    }
}
