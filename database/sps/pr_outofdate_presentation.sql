
ALTER PROCEDURE dbo.pr_outofdate_presentation

AS

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#search', 'U') IS NOT NULL
    DROP TABLE #search; 
IF OBJECT_ID('tempdb.dbo.#home', 'U') IS NOT NULL
    DROP TABLE #home; 

SELECT DISTINCT
e.id_evento
,MAX((CASE WHEN DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE() THEN 1 ELSE 0 END)) hasNext
INTO #search
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..search s ON e.id_evento=s.id_evento
WHERE e.id_evento=22666
GROUP BY e.id_evento

DELETE FROM #search WHERE hasNext=1

UPDATE u
SET u.outofdate=1
FROM search u
INNER JOIN #search tdelete ON u.id_evento=tdelete.id_evento

SELECT
e.id_evento
,MAX((CASE WHEN DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE() THEN 1 ELSE 0 END)) hasNext
INTO #home
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..home s ON e.id_evento=s.id_evento
GROUP BY e.id_evento

DELETE FROM #home WHERE hasNext=1

UPDATE u
SET u.outofdate=1
FROM home u
INNER JOIN #home tdelete ON u.id_evento=tdelete.id_evento