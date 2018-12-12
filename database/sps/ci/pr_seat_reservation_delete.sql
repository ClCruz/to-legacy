CREATE PROCEDURE dbo.pr_seat_reservation_delete (@id_apresentacao INT, @indice INT, @id VARCHAR(100))

AS

SET NOCOUNT ON;
-- DECLARE @codPeca INT, @id_apresentacao INT, @indice INT, @id VARCHAR(100), @NIN VARCHAR(10), @minutesToExpire INT

-- SELECT
--     @codPeca=145
--     ,@id_apresentacao=166789
--     ,@indice=80847
--     ,@id='teste'


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

DECLARE @codApresentacao INT

SELECT
    @codApresentacao=a.CodApresentacao
FROM tabSalDetalhe sd
INNER JOIN tabApresentacao a ON sd.CodSala=a.CodSala
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON a.CodApresentacao=ap.CodApresentacao
INNER JOIN tabSala s ON sd.CodSala=s.CodSala
WHERE ap.id_apresentacao=@id_apresentacao AND sd.Indice=@indice


DELETE FROM CI_MIDDLEWAY..mw_reserva WHERE id_apresentacao=@id_apresentacao AND id_cadeira=@indice AND id_session=@id_session

DELETE TABLUGSALA WHERE CodApresentacao=@codApresentacao AND CodUsuario=@codUsuario AND CodCaixa=@codCaixa AND Indice=@indice AND id_session=@id_session

SELECT 1 success