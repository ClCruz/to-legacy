CREATE PROCEDURE dbo.pr_payment (@ticketoffice BIT = NULL)

AS

SELECT 
tabForPagamento.CodForPagto
,tabForPagamento.ForPagto
,tabTipForPagamento.CodTipForPagto
,tabTipForPagamento.TipForPagto
,StaForPagto
FROM tabForPagamento 
INNER JOIN tabTipForPagamento ON tabForPagamento.CodTipForPagto = tabTipForPagamento.CodTipForPagto
WHERE (StaForPagto = 'A') --AND (tabForPagamento.TipCaixa = @TipCaixa OR @TipCaixa is null )
ORDER BY ForPagto


-- select * from tabForPagamento order by CodForPagto desc


-- update tabForPagamento set StaForPagto='I' where CodForPagto not in (52,53,54,55)