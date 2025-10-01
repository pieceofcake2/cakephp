-- Create test databases for CakePHP
IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'cakephp_test')
BEGIN
    CREATE DATABASE cakephp_test;
END
GO

-- Use cakephp_test as the default database
USE cakephp_test;
GO

-- Create additional schemas for testing
IF NOT EXISTS (SELECT * FROM sys.schemas WHERE name = 'test2')
BEGIN
    EXEC('CREATE SCHEMA test2');
END
GO

IF NOT EXISTS (SELECT * FROM sys.schemas WHERE name = 'test3')
BEGIN
    EXEC('CREATE SCHEMA test3');
END
GO
