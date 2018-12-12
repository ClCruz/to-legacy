--pr_event 'inner_circle_cota_de_ingressos_22678', 'live_keykeykey'

CREATE PROCEDURE dbo.pr_event_meta (@key VARCHAR(100))

AS

-- DECLARE @key VARCHAR(100) = 'inner_circle_cota_de_ingressos_22678'
--         ,@api VARCHAR(100) = 'live_keykeykey'


DECLARE @keyHelper VARCHAR(100) = '/evento/' + @key

SELECT TOP 1
eei.id_evento
,e.CodPeca
,e.id_base
FROM CI_MIDDLEWAY..mw_evento_extrainfo eei
INNER JOIN CI_MIDDLEWAY..mw_evento e ON eei.id_evento=e.id_evento
INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
WHERE eei.uri=@keyHelper
ORDER BY eei.id_evento DESC