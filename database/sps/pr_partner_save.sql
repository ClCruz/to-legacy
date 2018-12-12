--exec sp_executesql N'EXEC pr_partner_save @P1, @P2, @P3, @P4, @P5',N'@P1 nvarchar(4000),@P2 nvarchar(4000),@P3 nvarchar(4000),@P4 nvarchar(4000),@P5 nvarchar(4000)',N'5A087E34-006D-4279-8F9E-52D6338034BA',N'teste murdock',N'1',N'01/01/2018',N'01/01/2019'
GO
ALTER PROCEDURE dbo.pr_partner_save (@id UNIQUEIDENTIFIER
                                    ,@name VARCHAR(1000)
                                    ,@active BIT = 1
                                    ,@dateStart DATETIME = NULL
                                    ,@dateEnd DATETIME = NULL
                                    ,@domain VARCHAR(1000))

AS
-- DECLARE @id UNIQUEIDENTIFIER = '5A087E34-006D-4279-8F9E-52D6338034BA'
--                                     ,@name VARCHAR(1000) = 'teste murdock'
--                                     ,@active BIT = 1
--                                     ,@dateStart DATETIME = '01/01/2018'
--                                     ,@dateEnd DATETIME = '01/01/2019'


SET NOCOUNT ON;

IF @dateEnd<='1900-01-01 00:00:00.000'
    SET @dateEnd=NULL

DECLARE @exist BIT = 0

IF @id IS NOT NULL
    SELECT @exist=1 FROM CI_MIDDLEWAY..[partner] WHERE id=@id

IF @exist = 1
BEGIN
    UPDATE CI_MIDDLEWAY..[partner] SET [name]=@name
                                        ,active=@active
                                        ,dateStart=@dateStart
                                        ,dateEnd=@dateEnd
                                        ,domain=@domain
    WHERE id=@id
END
ELSE
BEGIN
    DECLARE @key VARCHAR(100)
            ,@key_test VARCHAR(100)

   IF OBJECT_ID('tempdb.dbo.#keypartner', 'U') IS NOT NULL
        DROP TABLE #keypartner; 

    CREATE TABLE #keypartner ([key] VARCHAR(100), [key_test] VARCHAR(100));

    INSERT INTO #keypartner EXEC CI_MIDDLEWAY..pr_partner_key NULL, 0;

    SELECT @key=[key], @key_test=key_test FROM #keypartner

    INSERT INTO CI_MIDDLEWAY..[partner] ([key],key_test,[name],active,dateStart,dateEnd,domain)
        SELECT @key, @key_test, @name,1, @dateStart,@dateEnd,@domain
END

SELECT 1 success
        ,'Cadastrado com sucesso.' msg