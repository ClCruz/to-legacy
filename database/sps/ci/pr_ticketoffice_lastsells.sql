CREATE PROCEDURE dbo.pr_ticketoffice_lastsells (@id_ticketoffice_user UNIQUEIDENTIFIER)

AS
SET NOCOUNT ON;
-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER = '8cc26a74-7e65-411e-b854-f7b281a46e01'

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT DISTINCT TOP 50
c.Nome
,c.CPF
,ls.Indice
,se.NomSetor
,p.NomPeca
,s.NomSala
,a.HorSessao
,CONVERT(VARCHAR(10),a.DatApresentacao,103) DatApresentacao
,l.ValPagto
,tb.TipBilhete
,CONVERT(VARCHAR(10),l.DatVenda,103) + ' ' + CONVERT(VARCHAR(8),l.DatVenda,114) AS created
,tosh.created created2
,tosh.id_pedido_venda
,sd.NomObjeto
,togr.id_gateway
,togr.transactionKey
,(CASE WHEN togr.transactionKey IS NULL THEN 0 ELSE 1 END) refundInGateway
,ls.CodVenda
,(CASE WHEN tosh.id IS NULL THEN 'web' ELSE 'bilheteria' END) purchaseType
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSetor se ON s.CodSala=se.CodSala
INNER JOIN tabSalDetalhe sd ON a.CodSala=sd.CodSala AND ls.Indice=sd.Indice
INNER JOIN CI_MIDDLEWAY..mw_evento e ON a.CodPeca=e.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND ls.CodApresentacao=ap.CodApresentacao
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart_hist tosh ON tosh.id_base=e.id_base AND tosh.id_apresentacao=ap.id_apresentacao AND tosh.indice=ls.Indice
INNER JOIN CI_MIDDLEWAY..ticketoffice_user tou ON tosh.id_ticketoffice_user=tou.id
LEFT JOIN CI_MIDDLEWAY..ticketoffice_gateway_result togr ON tosh.id=togr.id_ticketoffice_shoppingcart
LEFT JOIN tabLancamento l ON ls.Indice=l.Indice AND ls.CodApresentacao=l.CodApresentacao
LEFT JOIN tabTipBilhete tb ON l.CodTipBilhete=tb.CodTipBilhete
LEFT JOIN tabHisCliente hc ON l.NumLancamento=hc.NumLancamento AND ls.CodApresentacao=hc.CodApresentacao AND ls.Indice=hc.Indice
LEFT JOIN tabCliente c ON hc.Codigo=c.Codigo
WHERE tosh.id_ticketoffice_user=@id_ticketoffice_user
ORDER BY tosh.created DESC