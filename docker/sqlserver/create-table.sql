USE TestDB

IF OBJECT_ID(N'[dbo].[TestTable]', 'U') IS NULL
BEGIN
    CREATE TABLE TestTable
    (
        id INT IDENTITY(1,1) PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        age INT NOT NULL
    )
END ELSE BEGIN
    DELETE FROM TestTable
END

IF OBJECT_ID(N'[dbo].[IntegrationTable]', 'U') IS NULL
BEGIN
    CREATE TABLE IntegrationTable
    (
        id INT IDENTITY(1,1) PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        age INT NOT NULL
    )
END ELSE BEGIN
    DELETE FROM TestTable
END

DELETE FROM TestTable;
DELETE FROM IntegrationTable;
