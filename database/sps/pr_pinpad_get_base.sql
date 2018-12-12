CREATE PROCEDURE dbo.pr_pinpad_get_base (@key VARCHAR(100))

AS

SELECT
p.id_base
FROM CI_MIDDLEWAY..ticketoffice_pinpad p
WHERE p.[key]=@key
