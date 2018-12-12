--pr_print_ticket 'MWRKVIEABA'
GO
ALTER PROCEDURE dbo.pr_print_ticket(@codVenda VARCHAR(10)
        ,@indice INT = NULL)

AS

SET NOCOUNT ON;

IF @indice=''
    SET @indice=NULL

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

DECLARE @weekday TABLE (id INT, [name] VARCHAR(100), [full] VARCHAR(100));

INSERT INTO @weekday (id, [name],[full]) VALUES(1, 'dom', 'domingo')
INSERT INTO @weekday (id, [name],[full]) VALUES(2, 'seg', 'segunda-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(3, 'ter', 'terça-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(4, 'qua', 'quarta-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(5, 'qui', 'quinta-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(6, 'sex', 'sexta-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(7, 'sab', 'sábado')

DECLARE @month TABLE (id INT, [name] VARCHAR(100), [full] VARCHAR(100));

INSERT INTO @month (id,[name],[full]) VALUES(1, 'jan', 'janeiro')
INSERT INTO @month (id,[name],[full]) VALUES(2, 'fev', 'fevereiro')
INSERT INTO @month (id,[name],[full]) VALUES(3, 'mar', 'março')
INSERT INTO @month (id,[name],[full]) VALUES(4, 'abr', 'abril')
INSERT INTO @month (id,[name],[full]) VALUES(5, 'mai', 'maio')
INSERT INTO @month (id,[name],[full]) VALUES(6, 'jun', 'junho')
INSERT INTO @month (id,[name],[full]) VALUES(7, 'jul', 'julho')
INSERT INTO @month (id,[name],[full]) VALUES(8, 'ago', 'agosto')
INSERT INTO @month (id,[name],[full]) VALUES(9, 'set', 'setembro')
INSERT INTO @month (id,[name],[full]) VALUES(10, 'out', 'outubro')
INSERT INTO @month (id,[name],[full]) VALUES(11, 'nov', 'novembro')
INSERT INTO @month (id,[name],[full]) VALUES(12, 'dez', 'dezembro')

DECLARE @id_base INT
SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

DECLARE @transaction VARCHAR(100) = NULL
        ,@now DATETIME = GETDATE()

SELECT TOP 1 @transaction=togr.transactionKey
FROM CI_MIDDLEWAY..ticketoffice_gateway_result togr
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart_hist tosch ON togr.id_ticketoffice_shoppingcart=tosch.id
WHERE tosch.codVenda=@codVenda AND togr.transactionKey IS NOT NULL

IF @transaction IS NULL
BEGIN
    SELECT TOP 1 @transaction=pv.cd_numero_autorizacao
    FROM CI_MIDDLEWAY..mw_item_pedido_venda ipv
    INNER JOIN CI_MIDDLEWAY..mw_pedido_venda pv ON ipv.id_pedido_venda=pv.id_pedido_venda
    WHERE ipv.CodVenda=@codVenda AND pv.cd_numero_autorizacao IS NOT NULL
END

SELECT
tosch.id
,ls.Indice seatIndice
,le.ds_local_evento [local]
,le.ds_googlemaps [address]
,e.ds_evento [name]
,(SELECT TOP 1 [name] FROM @weekday WHERE id = DATEPART(dw, a.DatApresentacao)) [weekday]
,(SELECT TOP 1 [full] FROM @weekday WHERE id = DATEPART(dw, a.DatApresentacao)) [weekdayName]
,a.HorSessao [hour]
,(SELECT TOP 1 [name] FROM @month WHERE id = DATEPART(m, a.DatApresentacao)) [month]
,(SELECT TOP 1 [full] FROM @month WHERE id = DATEPART(m, a.DatApresentacao)) [monthName]
,DATEPART(d, a.DatApresentacao) [day]
,DATEPART(yyyy, a.DatApresentacao) [year]
,s.NomSala [roomName]
,s.NomRedSala roomNameOther
,sd.NomObjeto seatNameFull
,SUBSTRING(sd.NomObjeto, 0,CHARINDEX('-', sd.NomObjeto)) [seatRow]
,SUBSTRING(sd.NomObjeto, CHARINDEX('-', sd.NomObjeto) + 1, LEN(sd.NomObjeto)) [seatName]
,ls.CodVenda purchaseCode
,(CASE WHEN tosch.id_pedido_venda IS NULL THEN ipv.id_pedido_venda ELSE tosch.id_pedido_venda END) purchaseCodeInt
,tb.TipBilhete ticket
,fp.ForPagto payment
,tfp.TipForPagto paymentType
,@transaction AS [transaction]
,(CASE WHEN c.Nome IS NULL THEN '-' ELSE c.Nome END) buyer
,(CASE WHEN c.CPF IS NULL THEN '-' ELSE c.CPF END) buyerDoc
,(CASE WHEN eei.insurance_policy IS NULL THEN '' ELSE eei.insurance_policy END) insurance_policy
,(CASE WHEN eei.opening_time IS NULL THEN '' ELSE eei.opening_time END) opening_time
,p.NomResPeca eventResp
,(CASE WHEN tou.[login] IS NULL THEN 'web' ELSE tou.[login] END) [user]
,ROW_NUMBER() OVER (order by tosch.id) countTicket
,CONVERT(VARCHAR(10),l.DatVenda,103) + ' ' + CONVERT(VARCHAR(8),l.DatVenda,114) AS purchase_date
,CONVERT(VARCHAR(10),@now,103) + ' ' + CONVERT(VARCHAR(8),@now,114) AS print_date
,csv.codbar barcode
INTO #result
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSalDetalhe sd ON ls.Indice=sd.Indice AND a.CodSala=sd.CodSala
INNER JOIN CI_MIDDLEWAY..mw_evento e ON p.CodPeca=e.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND ap.CodApresentacao=a.CodApresentacao
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
LEFT JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart_hist tosch ON ls.Indice=tosch.indice AND ap.id_apresentacao=tosch.id_apresentacao
LEFT JOIN CI_MIDDLEWAY..mw_item_pedido_venda ipv ON ipv.Indice=ls.Indice AND ipv.id_apresentacao=ap.id_apresentacao
LEFT JOIN CI_MIDDLEWAY..mw_pedido_venda pv ON ipv.id_pedido_venda=pv.id_pedido_venda
LEFT JOIN tabLancamento l ON ls.CodApresentacao=l.CodApresentacao AND ls.Indice=l.Indice
LEFT JOIN tabTipBilhete tb ON l.CodTipBilhete=tb.CodTipBilhete
LEFT JOIN tabForPagamento fp ON l.CodForPagto=fp.CodForPagto
LEFT JOIN tabTipForPagamento tfp ON fp.CodTipForPagto=tfp.CodTipForPagto
LEFT JOIN tabHisCliente hc ON l.NumLancamento=hc.NumLancamento AND ls.CodApresentacao=hc.CodApresentacao AND ls.Indice=hc.Indice
LEFT JOIN tabCliente c ON hc.Codigo=c.Codigo
LEFT JOIN CI_MIDDLEWAY..ticketoffice_user tou ON tosch.id_ticketoffice_user=tou.id
LEFT JOIN tabControleSeqVenda csv ON ls.Indice=csv.Indice AND ls.CodApresentacao=csv.CodApresentacao
WHERE ls.CodVenda=@codVenda
AND (@indice IS NULL OR ls.Indice=@indice)

SELECT
[id]
,[local]
,[address]
,[name]
,[weekday]
,[weekdayName]
,[hour]
,[month]
,[monthName]
,[day]
,[year]
,[roomName]
,[roomNameOther]
,[seatNameFull]
,[seatRow]
,[seatName]
,[seatIndice]
,[purchaseCode]
,[purchaseCodeInt]
,[ticket]
,[payment]
,[paymentType]
,[transaction]
,[buyer]
,[buyerDoc]
,[insurance_policy]
,[opening_time]
,[eventResp]
,[user]
,[countTicket]
,[purchase_date]
,[print_date]
,[barcode]
,CONVERT(VARCHAR(10),countTicket) + '/' + CONVERT(VARCHAR(10),(SELECT MAX(countTicket) FROM #result)) [howMany]
FROM #result