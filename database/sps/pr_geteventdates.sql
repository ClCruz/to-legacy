-- exec pr_geteventdates 111
-- select * from search where id_evento=8157
-- GO

ALTER PROCEDURE dbo.pr_geteventdates(@id INT)

AS

SET NOCOUNT ON;

SELECT
ap.id_apresentacao
,CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') dt_apresentacao
,hr_apresentacao
FROM CI_MIDDLEWAY..mw_apresentacao ap
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON ap.id_evento=eei.id_evento
WHERE 
ap.id_evento=@id
AND ap.in_ativo=1
AND DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE()
ORDER BY CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':')
SET NOCOUNT OFF;