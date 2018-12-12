-- pr_ticketoffice_user_add_base '8CC26A74-7E65-411E-B854-F7B281A46E01'
-- exec sp_executesql N'EXEC pr_ticketoffice_user_add_base @P1',N'@P1 nvarchar(4000)',N'8CC26A74-7E65-411E-B854-F7B281A46E01'
-- select * from CI_MIDDLEWAY..ticketoffice_user_base
-- exec sp_executesql N'EXEC pr_ticketoffice_user_base_list @P1',N'@P1 nvarchar(4000)',N'93B93F5D-133A-4464-A57F-3C532BB33C59'
GO
ALTER PROCEDURE pr_ticketoffice_user_add_base (@id UNIQUEIDENTIFIER)

AS

SET NOCOUNT ON;

DECLARE @has BIT = 0
        ,@hasActive BIT = 0 

SELECT TOP 1 @has = 1, @hasActive = active FROM CI_MIDDLEWAY..ticketoffice_user_base WHERE id_ticketoffice_user=@id

IF @has = 1
BEGIN
    IF @hasActive = 1
    BEGIN
        UPDATE CI_MIDDLEWAY..ticketoffice_user_base SET active=0 WHERE id_ticketoffice_user=@id
    END
    ELSE
    BEGIN
        UPDATE CI_MIDDLEWAY..ticketoffice_user_base SET active=1 WHERE id_ticketoffice_user=@id
    END
    RETURN;
END

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

DECLARE @tou_login VARCHAR(1000), @tou_name VARCHAR(1000), @tou_email VARCHAR(1000)

SELECT TOP 1
@tou_login = tou.[login]
,@tou_name = tou.name
,@tou_email = tou.email
FROM CI_MIDDLEWAY..ticketoffice_user tou
WHERE tou.id=@id AND tou.active=1

DECLARE @CodCaixa INT, @TipCaixa VARCHAR(1)='C', @StaCaixa VARCHAR(1) = 'A', @Maquina VARCHAR(30) = SUBSTRING(REPLACE(CONVERT(VARCHAR(36),newid()),'-','') ,1,30), @id_canal_venda INT=1, @DescrCaixa VARCHAR(50)=SUBSTRING(@tou_login,1,50)
        ,@CodUsuario INT, @NomUsuario VARCHAR(30)=SUBSTRING(@tou_login,1,30), @Login VARCHAR(10)=SUBSTRING(REPLACE(CONVERT(VARCHAR(36),newid()),'-','') ,1,10), @SenUsuario VARCHAR(25)=SUBSTRING(REPLACE(CONVERT(VARCHAR(36),newid()),'-','') ,1,25), @CodCargo VARCHAR(1)='G', @StaUsuario INT=1

SELECT TOP 1 @CodCaixa=id 
FROM CI_MIDDLEWAY..ticketoffice_base_idhelper WHERE id not in (SELECT CodCaixa FROM tabCaixa)

SELECT TOP 1 @CodUsuario=id 
FROM CI_MIDDLEWAY..ticketoffice_base_idhelper WHERE id not in (SELECT CodUsuario FROM tabUsuario)

IF @CodCaixa IS NOT NULL AND @CodUsuario IS NOT NULL
BEGIN

    INSERT INTO tabCaixa (CodCaixa, TipCaixa, StaCaixa, Maquina, id_canal_venda, DescrCaixa)
    SELECT @codCaixa, @TipCaixa, @StaCaixa, @Maquina, @id_canal_venda, @DescrCaixa

    INSERT INTO tabUsuario (CodUsuario, NomUsuario, Login,  SenUsuario, CodCargo)
    VALUES (@CodUsuario, @NomUsuario,  @Login, @SenUsuario, @CodCargo)

    INSERT INTO CI_MIDDLEWAY..ticketoffice_user_base (id_ticketoffice_user, id_base, codCaixa, codUsuario)
    SELECT @id, @id_base, @codCaixa, @codUsuario


END

SELECT @CodCaixa codCaixa, @CodUsuario codUsuario