-- SELECT
--     codCaixa
--     ,codUsuario
-- FROM CI_MIDDLEWAY..ticketoffice_user_base
-- WHERE id_ticketoffice_user='8CC26A74-7E65-411E-B854-F7B281A46E01';


-- pr_cashregister '8CC26A74-7E65-411E-B854-F7B281A46E01'
GO
CREATE PROCEDURE pr_cashregister (@id UNIQUEIDENTIFIER)
 AS

SET NOCOUNT ON;

DECLARE @codCaixa INT, @codUsuario INT


SELECT
    @codCaixa=codCaixa
    ,@codUsuario=codUsuario
FROM CI_MIDDLEWAY..ticketoffice_user_base
WHERE id_ticketoffice_user=@id;

IF @codCaixa IS NULL
BEGIN
    EXEC pr_ticketoffice_user_add_base @id

    SELECT
        @codCaixa=codCaixa
        ,@codUsuario=codUsuario
    FROM CI_MIDDLEWAY..ticketoffice_user_base
    WHERE id_ticketoffice_user=@id;
END

EXEC SP_MOV_UPD001 @codCaixa, @codUsuario

