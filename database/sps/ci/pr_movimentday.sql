--
CREATE PROCEDURE dbo.pr_movimentday (
    @id_ticketoffice_user UNIQUEIDENTIFIER
    ,@date DATETIME)

AS

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT
mv.Codmovimento
,tou.[login]
,tou.name
FROM tabMovCaixa mv
INNER JOIN CI_MIDDLEWAY..ticketoffice_user_base toub ON mv.CodCaixa=toub.codCaixa AND mv.CodUsuario=toub.codUsuario AND toub.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..ticketoffice_user tou ON toub.id_ticketoffice_user=tou.id
WHERE toub.id_ticketoffice_user=@id_ticketoffice_user
AND DATEADD(dd, DATEDIFF(dd, 0, mv.DatMovimento), 0)=DATEADD(dd, DATEDIFF(dd, 0, @date), 0)
ORDER BY mv.Codmovimento DESC






