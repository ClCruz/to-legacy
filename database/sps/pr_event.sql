--pr_event 'inner_circle_cota_de_ingressos_22678', 'live_keykeykey'

ALTER PROCEDURE dbo.pr_event (@key VARCHAR(100), @api VARCHAR(100))

AS

-- DECLARE @key VARCHAR(100) = 'inner_circle_cota_de_ingressos_22678'
--         ,@api VARCHAR(100) = 'live_keykeykey'


DECLARE @keyHelper VARCHAR(100) = '/evento/' + @key
        ,@id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api

SELECT TOP 1
eei.id_evento
,eei.cardimage
,eei.cardbigimage
,eei.uri
,eei.[description] COLLATE SQL_Latin1_General_CP1_CI_AS AS [description]
,eei.ticketsPerPurchase
,eei.minuteBefore
,e.CodPeca
,e.ds_evento
,e.id_base
FROM CI_MIDDLEWAY..mw_evento_extrainfo eei
INNER JOIN CI_MIDDLEWAY..mw_evento e ON eei.id_evento=e.id_evento
INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
INNER JOIN CI_MIDDLEWAY..partner_database pd ON e.id_base=pd.id_base AND pd.id_partner=@id_partner
WHERE eei.uri=@keyHelper
ORDER BY eei.id_evento DESC