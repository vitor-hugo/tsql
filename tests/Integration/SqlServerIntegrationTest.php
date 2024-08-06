<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Integration\Contracts\BaseIntegrationTestCase;
use Torugo\Sql\Enums\DBEngine;
use Torugo\Sql\TSql;

#[Group("Integration")]
#[Group("TSql4")]
#[TestDox("TSql: SqlServer Integration Tests")]
class SqlServerIntegrationTest extends BaseIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$tsql = new TSql(DBEngine::SqlServer);
        self::$connectionParams = [
            "localhost",
            "TestDB",
            "SA",
            "SuperStrongPassword!",
            "UTF-8",
            1434
        ];
    }


    public static function tearDownAfterClass(): void
    {
        self::$tsql->close();
    }


    public function testShouldConnectToDatabase()
    {
        $tsql = new TSql(DBEngine::SqlServer);
        $tsql->connect(...self::$connectionParams);
        $this->assertTrue($tsql->isConnected());
        $tsql->close();
    }
}
