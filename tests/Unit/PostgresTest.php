<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Torugo\Sql\Databases\TPostgres;

use function PHPUnit\Framework\assertEquals;

#[Group("Unit")]
#[Group("Postgres")]
#[TestDox("Postgres")]
class PostgresTest extends TestCase
{
    private static TPostgres $db;

    public static function setUpBeforeClass(): void
    {
        self::$db = new TPostgres();
    }


    public static function tearDownAfterClass(): void
    {
        self::$db->close();
    }

    // MARK: Valid tests

    public function testShouldConnectToDatabase()
    {
        self::$db->connect(
            "127.0.0.1",
            "TestDB",
            "super",
            "SuperStrongPassword!",
            "UTF-8",
            5433
        );
        $this->assertTrue(self::$db->isConnected());
    }


    public function testShouldCheckIfTableExists()
    {
        self::$db->connect(
            "127.0.0.1",
            "TestDB",
            "super",
            "SuperStrongPassword!",
            "UTF-8",
            5433
        );

        $this->assertTrue(self::$db->tableExists("TestTable"));
        $this->assertFalse(self::$db->tableExists("NotExists"));
        $this->assertFalse(self::$db->tableExists(""));
    }


    public function testShouldCheckIfFieldExists()
    {
        $this->assertTrue(self::$db->fieldExists("id", "TestTable"));
        $this->assertTrue(self::$db->fieldExists("name", "testtable"));
        $this->assertTrue(self::$db->fieldExists("age", "TESTTABLE"));
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
        $query = self::$db->query("INSERT INTO TestTable (name, age) VALUES(\$1, \$2)", ["Test Record #1", 35]);
        $this->assertTrue($query);

        $query = self::$db->query("INSERT INTO TestTable (name, age) VALUES(\$1, \$2)", ["Test Record #2", 40]);
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


    // MARK: Invalidation tests

    #[WithoutErrorHandler()]
    public function testShouldReturnSqlErrors()
    {
        self::$db->query("SELECT names, ages from TestTable");
        $errors = self::$db->getErrors();
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);

        self::$db->query("NOT A VALID QUERY");
        $errors = self::$db->getErrors();
        $this->assertStringContainsString("syntax error", $errors[0]);
    }


    public function testShouldDisconnect()
    {
        self::$db->close();
        $this->assertFalse(self::$db->isConnected());
    }


    #[WithoutErrorHandler()]
    #[TestDox("Methods should return FALSE, 0, or EMPTY when not connected")]
    public function testShouldReturnFalseEmptyOrZeroWhenNotConnected()
    {
        $result = self::$db->query("SELECT id, name, age FROM TestTable");
        $this->assertFalse($result);

        $numRows = self::$db->numRows();
        $this->assertEquals(0, $numRows);

        $arr = self::$db->fetchArray();
        $this->assertEquals([], $arr);

        $arr = self::$db->fetchAll();
        $this->assertEquals([], $arr);

        $exists = self::$db->tableExists("TestTable");
        $this->assertFalse($exists);

        $exists = self::$db->fieldExists("name", "TestTable");
        $this->assertFalse($exists);

        $structure = self::$db->getTableStructure("TestTable");
        $this->assertFalse($structure);

        $errors = self::$db->getErrors();
        $this->assertEquals([], $errors);
    }

    #[WithoutErrorHandler()]
    #[TestDox("Should return a connection error")]
    public function testConnectionError()
    {
        $db = new TPostgres();
        $db->connect("127.0.0.1", "TestDB", "super", "SuperStrongPassword!x");
        $errors = $db->getErrors();
        $this->assertEquals("Could not connect to TestDB on 127.0.0.1", $errors[0]);
        $this->assertFalse($db->isConnected());
    }
}
