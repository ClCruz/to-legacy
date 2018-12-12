ALTER PROCEDURE dbo.pr_generate_robots (@api VARCHAR(100))

AS

SET NOCOUNT ON;

DECLARE @id_partner UNIQUEIDENTIFIER
        ,@domainPartner VARCHAR(1000) = NULL


SELECT TOP 1 @id_partner=p.id
            ,@domainPartner=p.domain FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api

IF OBJECT_ID('tempdb.dbo.#helper', 'U') IS NOT NULL
    DROP TABLE #helper; 

CREATE TABLE #helper ([name] varchar(max));

INSERT INTO #helper ([name])
SELECT 'Allow: '+t.uri FROM (
SELECT DISTINCT e.id_evento, e.ds_evento, eei.uri
,(CASE WHEN DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE() THEN 1 ELSE 0 END) hasAp
,(CASE WHEN ap.dt_apresentacao <= DATEADD(year, -1, GETDATE()) THEN 1 ELSE 0 END) morethanoneyear
,(CASE WHEN ap.dt_apresentacao <= DATEADD(year, -2, GETDATE()) THEN 1 ELSE 0 END) morethantwoyear
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..partner_database pd ON e.id_base=pd.id_base AND pd.id_partner=@id_partner
LEFT JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento) as t WHERE t.morethantwoyear=0
ORDER BY t.morethanoneyear, t.uri

DECLARE @domain VARCHAR(1000) = 'https://www.tixs.me'

IF @domainPartner IS NULL
    SET @domainPartner='https://www.tixs.me';

SET @domain = @domainPartner

SELECT 'User-Agent: *' as result
UNION ALL
SELECT 'Disallow: /ticketoffice' as result
UNION ALL
SELECT 'Noindex: /ticketoffice' as result
UNION ALL
SELECT 'Sitemap: '+@domain+'/sitemap.xml' as result
UNION ALL
SELECT [name] as result FROM #helper