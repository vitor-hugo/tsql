<?php declare(strict_types=1);

namespace Tests\Unit\Contracts;

interface DBTestInterface
{
    public function testShouldConnectToDatabase();
    public function testShouldCheckIfTableExists();
    public function testShouldCheckIfFieldExists();
    public function testShouldReturnTableStructure();
    public function testShouldInsertSomeData();
    public function testShouldSelectRecords();
    public function testShouldReturnNumberOfRows();
    public function testShouldReturnFirstRecordAsAssociativeArray();
    public function testShouldReturnNextRecordAsAssociativeArray();
    public function testShouldFetchAllRecordsFromAQuery();
    public function testShouldHasActiveResultSet();
    public function testShouldClearResultSet();
    public function testShouldDeleteRecords();
    public function testShouldReturnSqlErrors();
    public function testShouldDisconnect();
    public function testShouldReturnFalseEmptyOrZeroWhenNotConnected();
}
