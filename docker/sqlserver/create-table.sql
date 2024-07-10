USE TestDB;

IF (EXISTS (SELECT *
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'TestDB' AND TABLE_NAME = 'TestTable'))
BEGIN
    DELETE FROM TestTable;
END

DECLARE @sql nvarchar(255);
WHILE EXISTS(select *
from INFORMATION_SCHEMA.TABLE_CONSTRAINTS
where constraint_catalog = 'TestDB' AND table_name = 'TestTable')
BEGIN
    SELECT @sql = 'ALTER TABLE TestTable DROP CONSTRAINT ' + CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE constraint_catalog = 'TestDB' AND table_name = 'TestTable'
    EXEC sp_executesql @sql
END

CREATE TABLE TestTable
(
    id INT IDENTITY(1,1) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    age INT NOT NULL
)
