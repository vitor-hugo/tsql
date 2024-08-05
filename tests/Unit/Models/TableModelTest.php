<?php declare(strict_types=1);

namespace Tests\Unit\Models;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Torugo\Sql\Models\Table;

#[Group("Models")]
#[Group("TableModel")]
#[TestDox("TableModel")]
class TableModelTest extends TestCase
{
    public $stub;

    #[TestDox("Should be valid")]
    public function testShouldBeValid()
    {
        $stub = new Table("IntegrationTable", "IT");
        $this->assertEquals("IntegrationTable", $stub->getName());
        $this->assertEquals("IntegrationTable as IT", $stub->getName(true));
    }


    #[TestDox("Should update table name")]
    public function test()
    {
        $stub = new Table("IntegrationTable", "IT");
        $this->assertEquals("IntegrationTable", $stub->getName());

        $stub->setName("TestTable");
        $this->assertEquals("TestTable", $stub->getName());
    }


    #[TestDox("Should update table alias")]
    public function testShouldUpdateTableAlias()
    {
        $stub = new Table("IntegrationTable", "IT");
        $this->assertEquals("IntegrationTable as IT", $stub->getName(true));

        $stub->setAlias("IntTbl");
        $this->assertEquals("IntTbl", $stub->getAlias());
    }


    #[TestDox("Should throw InvalidArgumentException when table name has invalid characters.")]
    public function testShouldThrowOnInvalidTableNames()
    {
        $this->expectException(InvalidArgumentException::class);
        new Table("Not a valid table name.");
    }
}
