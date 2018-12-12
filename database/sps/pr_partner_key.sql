ALTER PROCEDURE dbo.pr_partner_key (
        @id UNIQUEIDENTIFIER = NULL
        ,@update BIT = 1)

AS

-- DECLARE @id UNIQUEIDENTIFIER = NULL
--         ,@update BIT = 1

DECLARE @key_live VARCHAR(100)
        ,@key_test VARCHAR(100)
        ,@cont BIT =1


--WHILE (@cont = 1)
--BEGIN  
    SET @key_live = CONCAT('live_', LOWER(REPLACE(CONVERT(VARCHAR(100),NEWID()),'-','')),LOWER(REPLACE(CONVERT(VARCHAR(100),NEWID()),'-','')));
    SET @key_test = CONCAT('test_', LOWER(REPLACE(CONVERT(VARCHAR(100),NEWID()),'-','')),LOWER(REPLACE(CONVERT(VARCHAR(100),NEWID()),'-','')));

    DECLARE @exist BIT = 0
    SELECT @exist=1 FROM [partner] WHERE [key]=@key_live

    IF @exist=0
        SET @cont=0

    SELECT @exist=1 FROM [partner] WHERE [key_test]=@key_test

    IF @exist=0
        SET @cont=0

IF @update = 1 AND @id IS NOT NULL
BEGIN
    UPDATE CI_MIDDLEWAY..[partner] SET [key]=@key_live, key_test=@key_test WHERE id=@id
END

SELECT @key_live key_live
        ,@key_test key_test