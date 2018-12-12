-- pr_ticketoffice_shoppingcart_result '8cc26a74-7e65-411e-b854-f7b281a46e01'
-- select * from CI_MIDDLEWAY..ticketoffice_shoppingcart

CREATE PROCEDURE dbo.pr_ticketoffice_shoppingcart_result (@id UNIQUEIDENTIFIER)

AS

SELECT DISTINCT
tosc.id_apresentacao
,tosc.indice
,tosc.id_event
,tosc.id_base
,tosc.amount
,tosc.amount_discount
,tosc.amount_topay
,tosc.created
,tosc.quantity
,tosc.id_payment_type
,tosc.id_ticket_type
,sd.NomObjeto
,se.NomSetor
,se.PerDesconto
,ls.StaCadeira
,0 amountSubTotalSector
,0 amountSubTotalTicket
,(CASE WHEN tosc.id_ticket_type IS NULL THEN 0 ELSE 1 END) valid
,tpb.TipBilhete
,tpb.PerDesconto PerDescontoTipBilhete
FROM CI_MIDDLEWAY.dbo.ticketoffice_shoppingcart tosc
INNER JOIN CI_MIDDLEWAY.dbo.mw_apresentacao ap ON tosc.id_apresentacao=ap.id_apresentacao
INNER JOIN tabApresentacao a ON ap.CodApresentacao=a.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSetor se ON s.CodSala=se.CodSala
INNER JOIN tabSalDetalhe sd ON s.CodSala=sd.CodSala AND tosc.indice=sd.Indice
INNER JOIN tabLugSala ls ON ap.CodApresentacao=ls.CodApresentacao AND tosc.indice=ls.Indice
LEFT JOIN tabTipBilhete tpb ON tosc.id_ticket_type=tpb.CodTipBilhete
WHERE id_ticketoffice_user=@id
ORDER BY se.NomSetor, sd.NomObjeto
