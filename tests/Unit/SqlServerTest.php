<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Torugo\Sql\Databases\TSqlServer;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;

#[Group("Unit")]
#[Group("SqlServer")]
#[TestDox("SqlServer")]
class SqlServerTest extends TestCase
{
    private static TSqlServer $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = new TSqlServer;
    }


    public static function tearDownAfterClass(): void
    {
        self::$db->close();
    }

    // MARK: Valid tests

    public function testShouldConnectToDatabase()
    {
        self::$db->connect("localhost", "TestDB", "SA", "SuperStrongPassword!");
        $this->assertTrue(self::$db->isConnected());
    }


    public function testShouldCheckIfTableExists()
    {
        $this->assertTrue(self::$db->tableExists("TestTable"));
        $this->assertFalse(self::$db->tableExists("NotExists"));
        $this->assertFalse(self::$db->tableExists(""));
    }


    public function testShouldCheckIfFieldExists()
    {
        $this->assertTrue(self::$db->fieldExists("id", "TestTable"));
        $this->assertTrue(self::$db->fieldExists("name", "TestTable"));
        $this->assertTrue(self::$db->fieldExists("age", "TestTable"));
        $this->assertFalse(self::$db->fieldExists("not", "TestTable"));
        $this->assertFalse(self::$db->fieldExists("", "TestTable"));
        $this->assertFalse(self::$db->fieldExists("name", ""));
    }


    public function testShouldReturnTableStructure()
    {
        $structure = self::$db->getTableStructure("TestTable");
        $this->assertArrayHasKey("id", $structure);
        $this->assertArrayHasKey("name", $structure);
        $this->assertArrayHasKey("age", $structure);

        foreach ($structure as $field) {
            $this->assertArrayHasKey("type", $field);
            $this->assertArrayHasKey("maxLength", $field);
        }

        $structure = self::$db->getTableStructure("TestTables");
        $this->assertFalse($structure);
    }


    public function testShouldInsertSomeData()
    {
        $query = self::$db->query("INSERT INTO TestTable (name, age) VALUES(?,?)", ["Test Record #1", 35]);
        $this->assertTrue($query);

        $query = self::$db->query("INSERT INTO TestTable (name, age) VALUES(?,?)", ["Test Record #2", 40]);
        $this->assertTrue($query);
    }


    public function testShouldSelectRecords()
    {
        $query = self::$db->query("SELECT id, name, age FROM TestTable");
        $this->assertTrue($query);
    }


    public function testShouldReturnNumberOfRows()
    {
        $numRows = self::$db->numRows();
        $this->assertEquals(2, $numRows);
    }


    public function testShouldReturnFirstRecordAsAssociativeArray()
    {
        $row = self::$db->fetchArray();
        $this->assertArrayHasKey("id", $row);
        $this->assertArrayHasKey("name", $row);
        $this->assertArrayHasKey("age", $row);
        $this->assertEquals("Test Record #1", $row["name"]);
        $this->assertEquals(35, $row["age"]);
    }


    public function testShouldReturnNextRecordAsAssociativeArray()
    {
        $row = self::$db->fetchArray();
        $this->assertArrayHasKey("id", $row);
        $this->assertArrayHasKey("name", $row);
        $this->assertArrayHasKey("age", $row);
        $this->assertEquals("Test Record #2", $row["name"]);
        $this->assertEquals(40, $row["age"]);
    }


    public function testShouldFetchAllRecordsFromAQuery()
    {
        self::$db->query("SELECT id, name, age FROM TestTable");
        $rows = self::$db->fetchAll();
        $this->assertEquals(2, count($rows));

        foreach ($rows as $row) {
            $this->assertArrayHasKey("id", $row);
            $this->assertArrayHasKey("name", $row);
            $this->assertArrayHasKey("age", $row);
        }
    }


    public function testShouldHasActiveResultSet()
    {
        $result = self::$db->hasActiveResultSet();
        $this->assertTrue($result);
    }


    public function testShouldClearResultSet()
    {
        self::$db->clearResultSet();
        $result = self::$db->hasActiveResultSet();
        $this->assertFalse($result);
    }


    public function testShouldDeleteRecords()
    {
        $query = self::$db->query("DELETE FROM TestTable");
        $this->assertTrue($query);
    }


    // MARK: Valid tests

    public function testShouldReturnSqlErrors()
    {
        self::$db->query("NOT A VALID QUERY STRING");
        $errors = self::$db->getErrors();
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors);
        $this->assertTrue(str_contains($errors[0]["message"], "Incorrect syntax"));
    }


    public function testShouldDisconnect()
    {
        self::$db->close();
        $this->assertFalse(self::$db->isConnected());
    }


    #[TestDox("Methods should return FALSE, 0, or EMPTY when not connected")]
    public function testShouldReturnFalseEmptyOrZeroWhenNotConnected()
    {
        $result = self::$db->query("SELECT id, name, age FROM TestTable");
        assertFalse($result);

        $numRows = self::$db->numRows();
        assertEquals(0, $numRows);

        $arr = self::$db->fetchArray();
        assertEquals([], $arr);

        $arr = self::$db->fetchAll();
        assertEquals([], $arr);

        $exists = self::$db->tableExists("TestTable");
        assertFalse($exists);

        $exists = self::$db->fieldExists("name", "TestTable");
        assertFalse($exists);

        $structure = self::$db->getTableStructure("TestTable");
        assertFalse($structure);
    }
}
