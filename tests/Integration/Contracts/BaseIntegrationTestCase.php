<?php declare(strict_types=1);

namespace Tests\Integration\Contracts;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Torugo\Sql\TSql;

class BaseIntegrationTestCase extends TestCase
{
    protected static TSql $tsql;
    protected static array $connectionParams;
    protected const QUERY_LOG_FILE = __DIR__ . "/../../logs/queries.log";
    protected const ERROR_LOG_FILE = __DIR__ . "/../../logs/errors.log";


    public function testShouldConnectToDatabase()
    {
        try {
            $this->assertTrue(self::$tsql->connect(...self::$connectionParams));
            $this->assertTrue(self::$tsql->isConnected());
        } catch (\Throwable $th) {
            exit("Could not connect to database.");
        }
    }


    public function testShouldCloseConnection()
    {
        self::$tsql->close();
        $this->assertFalse(self::$tsql->isConnected());
    }


    public function testShouldDeleteAllRecords()
    {
        self::$tsql->connect(...self::$connectionParams);
        $result = self::$tsql->query("DELETE FROM TESTTABLE");
        $this->assertTrue($result);

        $result = self::$tsql->query("DELETE FROM INTEGRATIONTABLE");
        $this->assertTrue($result);
    }


    public function testShouldInsertSomeData()
    {
        $result = self::$tsql->query("INSERT INTO IntegrationTable (name, age) VALUES('Test Record #1', 40) ");
        $this->assertTrue($result);

        $result = self::$tsql->query("INSERT INTO IntegrationTable (name, age) VALUES('Test Record #2', 30) ");
        $this->assertTrue($result);
    }


    public function testShouldSelectInsertedRecords()
    {
        $result = self::$tsql->query("SELECT ID, NAME, AGE FROM IntegrationTable");
        $this->assertTrue($result);
        $this->assertTrue(self::$tsql->hasActiveResultSet());
    }


    #[TestDox("numRows() should return 2")]
    public function testNumRowsShouldReturnTwo()
    {
        $this->assertEquals(2, self::$tsql->numRows());
    }


    #[TestDox("fetchArray() should return one row at a time")]
    public function testFetchArrayShouldReturnFirstSelectedRow()
    {
        $result = self::$tsql->query("SELECT NAME, AGE FROM IntegrationTable");
        $this->assertTrue($result);

        $row = self::$tsql->fetchArray();
        $this->assertEquals("Test Record #1", $row["name"]);
        $this->assertEquals(40, $row["age"]);

        $row = self::$tsql->fetchArray();
        $this->assertEquals("Test Record #2", $row["name"]);
        $this->assertEquals(30, $row["age"]);
    }


    #[TestDox("fetchAll() should all rows at once")]
    public function testFetchAllShouldReturnAllRows()
    {
        $result = self::$tsql->query("SELECT NAME, AGE FROM IntegrationTable");
        $this->assertTrue($result);

        $rows = self::$tsql->fetchAll();
        $this->assertEquals("Test Record #1", $rows[0]["name"]);
        $this->assertEquals(40, $rows[0]["age"]);
        $this->assertEquals("Test Record #2", $rows[1]["name"]);
        $this->assertEquals(30, $rows[1]["age"]);
    }


    public function testShouldClearTheResultSet()
    {
        self::$tsql->clearResultSet();
        $this->assertFalse(self::$tsql->hasActiveResultSet());
        $this->assertEquals(0, self::$tsql->numRows());
    }


    public function testShouldReturnTrueWhenTableExists()
    {
        $this->assertTrue(self::$tsql->tableExists("IntegrationTable"));
    }


    public function testShouldReturnTrueWhenFieldExists()
    {
        $this->assertTrue(self::$tsql->fieldExists("NAME", "IntegrationTable"));
    }


    public function testShouldReturnTableStructure()
    {
        $structure = self::$tsql->getTableStructure("IntegrationTable");
        $this->assertIsArray($structure);

        $this->assertArrayHasKey("id", $structure);
        $this->assertArrayHasKey("name", $structure);
        $this->assertArrayHasKey("age", $structure);

        $this->assertArrayHasKey("type", $structure["id"]);
        $this->assertArrayHasKey("maxLength", $structure["id"]);

        $this->assertArrayHasKey("type", $structure["name"]);
        $this->assertArrayHasKey("maxLength", $structure["name"]);

        $this->assertArrayHasKey("type", $structure["age"]);
        $this->assertArrayHasKey("maxLength", $structure["age"]);
    }


    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////

    // MARK: Errors test

    public function testShouldThrowExceptionWhenConnectionFails()
    {
        $this->expectException(\Exception::class);
        $params = self::$connectionParams;
        $params[3] = "InvalidPass";
        self::$tsql->connect(...$params);
    }


    public function testShouldThrowExceptionOnInvalidSqlCommand()
    {
        $this->expectException(\Exception::class);
        self::$tsql->query("NOT A VALID SQL COMMAND");
    }


    public function testShouldReturnFalseWhenTableDoesNotExist()
    {
        $this->assertFalse(self::$tsql->tableExists("IntegrationTables"));
    }


    public function testShouldReturnFalseWhenFieldDoesNotExist()
    {
        $this->assertFalse(self::$tsql->fieldExists("NOME", "IntegrationTable"));
    }


    public function testShouldReturnFalseWhenTryingToGetTheStructureOfANonExistentTable()
    {
        $this->assertFalse(self::$tsql->getTableStructure("InvalidTable"));
    }


    public function testShouldThrowExceptionWhenQueryLogFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The query log file path '/private/query.log' is invalid.");
        self::$tsql->enableQueryLogging("/private/query.log");
    }


    public function testShouldThrowExceptionWhenErrorLogFileDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The error log file path '/private/error.log' is invalid.");
        self::$tsql->enableErrorLogging("/private/error.log");
    }

    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////

    // MARK: Logging Tests

    public function testSetupLogTests()
    {
        $this->expectNotToPerformAssertions();

        if (file_exists(self::QUERY_LOG_FILE)) {
            unlink(self::QUERY_LOG_FILE);
        }

        if (file_exists(self::ERROR_LOG_FILE)) {
            unlink(self::ERROR_LOG_FILE);
        }

        self::$tsql->connect(...self::$connectionParams);
    }


    public function testShouldEnableQueryLogging()
    {
        self::$tsql->enableQueryLogging(self::QUERY_LOG_FILE);
        $this->assertTrue(file_exists(self::QUERY_LOG_FILE));
    }


    public function testShouldLogQueryInstructionsOnALogFile()
    {
        $result = self::$tsql->query("SELECT name, age FROM IntegrationTable WHERE name like ?", ["%#1%"]);
        $this->assertTrue($result);

        $lines = file(self::QUERY_LOG_FILE);
        $this->assertMatchesRegularExpression("/^Date:\s[0-9\/\s:]+$/", $lines[0]);
        $this->assertEquals("Query: SELECT name, age FROM IntegrationTable WHERE name like ?\n", $lines[1]);
        $this->assertEquals("Params: %#1%\n", $lines[2]);
    }


    public function testShouldEnableErrorLogging()
    {
        self::$tsql->enableErrorLogging(self::ERROR_LOG_FILE);
        $this->assertTrue(file_exists(self::ERROR_LOG_FILE));
    }


    public function testShouldReturnFalseWhenErrorLoginIsEnabledAndConnectionFails()
    {
        $params = self::$connectionParams;
        $params[3] = "InvalidPass";
        $connection = self::$tsql->connect(...$params);
        $this->assertFalse($connection);
    }


    public function testShouldReturnFalseWhenErrorLoginIsEnabledAndQueryExecutionFails()
    {
        $result = self::$tsql->query("NOT A VALID SQL COMMAND");
        $this->assertFalse($result);
    }


}
