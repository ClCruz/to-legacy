
ALTER PROCEDURE dbo.pr_adm_events (@id_base INT, @api VARCHAR(100))

AS

SET NOCOUNT ON;

DECLARE @id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api


SELECT
e.id_evento
,e.ds_evento
,e.CodPeca
,(CASE WHEN eei.id_evento IS NULL THEN 1 ELSE 0 END) needed
-- ,eei.address
-- ,eei.[description]
-- ,eei.uri
-- ,eei.ticketsPerPurchase
-- ,eei.minuteBefore
-- ,'' [image]
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..partner_database pdb ON e.id_base=pdb.id_base
LEFT JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
WHERE pdb.id_partner=@id_partner
AND e.id_base=@id_base
ORDER by e.ds_evento