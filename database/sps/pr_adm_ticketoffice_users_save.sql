-- select * from CI_MIDDLEWAY..mw_evento_extrainfo where id_evento=22657
--exec sp_executesql N'EXEC pr_adm_ticketoffice_users_save @P1, @P2, @P3, @P4, @P5, @P6, @P7',N'@P1 nvarchar(4000),@P2 nvarchar(4000),@P3 nvarchar(4000),@P4 nvarchar(4000),@P5 nvarchar(4000),@P6 nvarchar(4000),@P7 nvarchar(4000)',N'live_keykeykey',N'',N'teste',N'testeoi',N'teste@teste.com',N'1',N'56f4485c63c0ef77d158f4739d4a4025148e1091'

ALTER PROCEDURE dbo.pr_adm_ticketoffice_users_save (@api VARCHAR(100), @id VARCHAR(100), @name VARCHAR(1000), @login VARCHAR(1000), @email VARCHAR(1000), @active BIT, @newPass VARCHAR(1000))

AS 

SET NOCOUNT ON;

-- DECLARE @api VARCHAR(100) = 'live_keykeykey', @id VARCHAR(100) = '', @name VARCHAR(1000)='teste', @login VARCHAR(1000)= 'teste', @email VARCHAR(1000)='teste@teste.com', @active BIT = 1, @newPass VARCHAR(1000) = '56f4485c63c0ef77d158f4739d4a4025148e1091'

DECLARE @hasAnotherWithLogin BIT = 0
        ,@has BIT = 0
        ,@idAux UNIQUEIDENTIFIER = NULL

IF @id IS NOT NULL AND @id != ''
    SET @idAux = @id


SELECT TOP 1 @hasAnotherWithLogin=1 FROM CI_MIDDLEWAY..ticketoffice_user WHERE LOWER([login])=RTRIM(LTRIM(LOWER(@login))) AND id!=@idAux
SELECT TOP 1 @has=1 FROM CI_MIDDLEWAY..ticketoffice_user WHERE id=@idAux

IF @hasAnotherWithLogin = 1
BEGIN
    SELECT 0 success
            ,'Já há outro usuário com esse login.' msg

    RETURN;
END

IF @has = 1
BEGIN
    UPDATE CI_MIDDLEWAY..ticketoffice_user SET [login]=@login, [name]=@name, email=@email, active=@active WHERE id=@idAux
END
ELSE
BEGIN
    INSERT INTO CI_MIDDLEWAY..ticketoffice_user (updated,[login],[password],lastLogin,[name],email,active,currentToken,tokenValidUntil)
    SELECT GETDATE(),@login,@newPass, NULL, @name, @email, 1, NULL, NULL
END

SELECT 1 success
        ,'' msg