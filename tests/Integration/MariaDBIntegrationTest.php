<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Integration\Contracts\BaseIntegrationTestCase;
use Torugo\Sql\Enums\DBEngine;
use Torugo\Sql\TSql;

#[Group("Integration")]
#[Group("TSql1")]
#[TestDox("TSql: MariaDB Integration Tests")]
class MariaDBIntegrationTest extends BaseIntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$tsql = new TSql(DBEngine::MariaDB);
        self::$connectionParams = [
            "127.0.0.1",
            "TestDB",
            "root",
            "12345",
            "UTF-8",
            3308
        ];
        self::$tsql->connect(...self::$connectionParams);
    }


    public static function tearDownAfterClass(): void
    {
        self::$tsql->close();
    }


    public function testShouldConnectToDatabase()
    {
        $tsql = new TSql(DBEngine::MariaDB);
        $tsql->connect(...self::$connectionParams);
        $this->assertTrue($tsql->isConnected());
        $tsql->close();
    }
}
