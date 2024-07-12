<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[Group("Extensions")]
#[TestDox("Extensions")]
class ExtensionsTest extends TestCase
{
    #[TestDox("Extension SQLSRV must be loaded")]
    public function testExtensionSqlsrvMustBeLoaded()
    {
        $ext = extension_loaded('sqlsrv');
        $this->assertTrue($ext);
    }


    #[TestDox("Extension MYSQLI must be loaded")]
    public function testExcetionMysqliMustBeLoaded()
    {
        $ext = extension_loaded('mysqli');
        $this->assertTrue($ext);
    }


    #[TestDox("Extension PGSQL must be loaded")]
    public function testExcetionPgsqlMustBeLoaded()
    {
        $ext = extension_loaded('pgsql');
        $this->assertTrue($ext);
    }


    #[TestDox("Extension MBSTRING must be loaded")]
    public function testExcetionMbstringMustBeLoaded()
    {
        $ext = extension_loaded('mbstring');
        $this->assertTrue($ext);
    }
}
