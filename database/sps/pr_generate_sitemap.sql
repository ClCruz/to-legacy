--  pr_generate_sitemap 'https://www.tixs.me'
go
ALTER PROCEDURE dbo.pr_generate_sitemap (@api VARCHAR(100))

AS

--0.6
DECLARE @homeChange VARCHAR(100) = 'always'
        ,@eventChange VARCHAR(100) = 'daily'
        ,@other VARCHAR(100) = 'never'
        ,@domain VARCHAR(1000) = 'https://www.tixs.me'

SET NOCOUNT ON;

DECLARE @id_partner UNIQUEIDENTIFIER
        ,@domainPartner VARCHAR(1000) = NULL

SELECT TOP 1 @id_partner=p.id
            ,@domainPartner=p.domain FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api


IF @domainPartner IS NULL
    SET @domainPartner='https://www.tixs.me';

SET @domain = @domainPartner


IF OBJECT_ID('tempdb.dbo.#helper', 'U') IS NOT NULL
    DROP TABLE #helper; 

CREATE TABLE #helper ([name] varchar(max), created varchar(10));

INSERT INTO #helper ([name], created)
SELECT uri,created  FROM (
SELECT DISTINCT e.id_evento, e.ds_evento, eei.uri, CONVERT(VARCHAR(10),eei.created,120) created
,(CASE WHEN DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE() THEN 1 ELSE 0 END) hasAp
,(CASE WHEN ap.dt_apresentacao <= DATEADD(year, -1, GETDATE()) THEN 1 ELSE 0 END) morethanoneyear
,(CASE WHEN ap.dt_apresentacao <= DATEADD(year, -2, GETDATE()) THEN 1 ELSE 0 END) morethantwoyear
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..partner_database pd ON e.id_base=pd.id_base AND pd.id_partner=@id_partner
LEFT JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento) as t WHERE t.morethantwoyear=0
ORDER BY t.morethanoneyear, t.uri



SELECT '<?xml version="1.0" encoding="UTF-8"?>' as result
UNION ALL
SELECT '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' as result
UNION ALL
SELECT '<url><loc>'+@domain+'/</loc><lastmod>'+CONVERT(VARCHAR(10),GETDATE(),120)+'</lastmod><changefreq>'+@homeChange+'</changefreq><priority>1.0</priority></url>' as result
UNION ALL
SELECT '<url><loc>'+CONCAT(@domain,h.name)+'</loc><lastmod>'+h.created+'</lastmod><changefreq>'+@eventChange+'</changefreq><priority>0.8</priority></url>' as result FROM #helper h
UNION ALL
SELECT '</urlset>' as result