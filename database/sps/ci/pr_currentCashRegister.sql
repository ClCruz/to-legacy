CREATE PROCEDURE dbo.pr_currentCashRegister (@id UNIQUEIDENTIFIER)

AS

-- DECLARE @id UNIQUEIDENTIFIER

-- SELECT @id='8cc26a74-7e65-411e-b854-f7b281a46e01'


DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT
	tou.id
	,tou.[login]
	,tou.name
	,(CASE WHEN mc.Codmovimento IS NULL THEN 0 ELSE 1 END) opened
	,mc.Codmovimento
	,mc.Saldo
	,mc.ValDiferenca
	,mc.ObsDiferenca
	,mc.DatMovimento
	,datediff(hh, mc.created, getdate()) hoursOpened
FROM CI_MIDDLEWAY..ticketoffice_user tou
INNER JOIN CI_MIDDLEWAY..ticketoffice_user_base toub ON tou.id=toub.id_ticketoffice_user
LEFT JOIN tabMovCaixa mc ON mc.CodCaixa=toub.codCaixa AND mc.CodUsuario=mc.CodUsuario AND mc.StaMovimento='A'
WHERE
	toub.id_base=@id_base
	AND tou.id=@id
