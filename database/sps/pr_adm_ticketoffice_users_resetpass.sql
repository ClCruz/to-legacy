-- select * from CI_MIDDLEWAY..mw_evento_extrainfo where id_evento=22657
--exec sp_executesql N'EXEC pr_adm_ticketoffice_users_save @P1, @P2, @P3, @P4, @P5, @P6, @P7',N'@P1 nvarchar(4000),@P2 nvarchar(4000),@P3 nvarchar(4000),@P4 nvarchar(4000),@P5 nvarchar(4000),@P6 nvarchar(4000),@P7 nvarchar(4000)',N'live_keykeykey',N'',N'teste',N'testeoi',N'teste@teste.com',N'1',N'56f4485c63c0ef77d158f4739d4a4025148e1091'

CREATE PROCEDURE dbo.pr_adm_ticketoffice_users_resetpass (@api VARCHAR(100), @id UNIQUEIDENTIFIER, @newPass VARCHAR(1000))

AS 

SET NOCOUNT ON;

UPDATE CI_MIDDLEWAY..ticketoffice_user SET [password]=@newPass WHERE id=@id

SELECT 1 success
        ,'' msg