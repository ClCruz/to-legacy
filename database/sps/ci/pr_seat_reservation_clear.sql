CREATE PROCEDURE dbo.pr_seat_reservation_clear (@id VARCHAR(100), @all BIT)

AS

SET NOCOUNT ON;

DECLARE @id_base INT
        ,@id_session VARCHAR(32) = replace(@id,'-','')

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

DECLARE @codCaixa INT
        ,@codUsuario INT

SELECT
    @codCaixa=tub.codCaixa
    ,@codUsuario=tub.codUsuario
FROM CI_MIDDLEWAY..ticketoffice_user_base tub
WHERE tub.id_ticketoffice_user=@id
AND tub.id_base=@id_base

DELETE FROM CI_MIDDLEWAY..mw_reserva WHERE id_session=@id_session

IF @all = 1
BEGIN
        DELETE TABLUGSALA WHERE CodUsuario=@codUsuario AND CodCaixa=@codCaixa AND id_session=@id_session AND StaCadeira NOT IN ('V','R')
END
ELSE
BEGIN
        DELETE TABLUGSALA WHERE CodUsuario=@codUsuario AND CodCaixa=@codCaixa AND id_session=@id_session AND StaCadeira NOT IN ('V','R')
END

SELECT 1 success