-- pr_generate_search

ALTER PROCEDURE dbo.pr_generate_search

AS

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#toAdd', 'U') IS NOT NULL
    DROP TABLE #toAdd; 

IF OBJECT_ID('tempdb.dbo.#dont', 'U') IS NOT NULL
    DROP TABLE #dont; 

SELECT
    id_evento
into #dont
FROM search where outofdate=0

DECLARE @city VARCHAR(100) = NULL,@state VARCHAR(100) = NULL

DECLARE @nowOrder DATETIME = DATEADD(day,15, GETDATE())

SELECT DISTINCT
e.id_evento
,dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(e.ds_evento) COLLATE SQL_Latin1_General_Cp1251_CS_AS))) ds_evento
,dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(le.ds_local_evento) COLLATE SQL_Latin1_General_Cp1251_CS_AS))) ds_nome_teatro
,dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(mu.ds_municipio) COLLATE SQL_Latin1_General_Cp1251_CS_AS))) ds_municipio
,dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(es.sg_estado) COLLATE SQL_Latin1_General_Cp1251_CS_AS))) sg_estado
,dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(es.ds_estado) COLLATE SQL_Latin1_General_Cp1251_CS_AS))) ds_estado
,dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(g.name) COLLATE SQL_Latin1_General_Cp1251_CS_AS))) ds_genre
,(CASE WHEN ds_municipio = @city COLLATE SQL_Latin1_General_Cp1251_CS_AS THEN 1
                WHEN ds_municipio != @city COLLATE SQL_Latin1_General_Cp1251_CS_AS
                     AND es.sg_estado = @state COLLATE SQL_Latin1_General_Cp1251_CS_AS THEN 2
                WHEN ap.dt_apresentacao<=@nowOrder THEN 3
                ELSE 4 END) orderhelper
,0 outofdate
,e.ds_evento ds_eventoOriginal
,le.ds_local_evento ds_nome_teatroOriginal
,mu.ds_municipio ds_municipioOriginal
,es.sg_estado sg_estadoOriginal
,es.ds_estado ds_estadoOriginal
,g.name ds_genreOriginal
,eei.uri
,eei.cardimage
,e.id_base
,eei.id_genre
,e.id_local_evento
,le.id_municipio
,es.id_estado
INTO #toAdd
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento
INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
INNER JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
INNER JOIN CI_MIDDLEWAY..mw_municipio mu ON le.id_municipio=mu.id_municipio
INNER JOIN CI_MIDDLEWAY..mw_estado es ON mu.id_estado=es.id_estado
LEFT JOIN CI_MIDDLEWAY..genre g ON eei.id_genre=g.id
LEFT JOIN #dont dt ON e.id_evento=dt.id_evento
LEFT JOIN CI_MIDDLEWAY..mw_regiao_geografica regi ON es.id_regiao_geografica=regi.id_regiao_geografica
WHERE 
    DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE()
    AND e.in_ativo=1
    AND b.in_ativo=1
    AND dt.id_evento IS NULL
--    AND ds_municipio = @city COLLATE Latin1_general_CI_AI
ORDER BY (CASE WHEN ds_municipio = @city COLLATE SQL_Latin1_General_Cp1251_CS_AS THEN 1
                WHEN ds_municipio != @city COLLATE SQL_Latin1_General_Cp1251_CS_AS
                     AND es.sg_estado = @state COLLATE SQL_Latin1_General_Cp1251_CS_AS THEN 2
                WHEN ap.dt_apresentacao<=@nowOrder THEN 3
                ELSE 4 END)

DELETE d
FROM search d
INNER JOIN #toAdd tadd ON d.id_evento=tadd.id_evento

INSERT INTO search (id_evento, outofdate, [text], ds_evento, ds_nome_teatro, ds_municipio,sg_estado,ds_estado, ds_eventoOriginal, ds_nome_teatroOriginal, ds_municipioOriginal,sg_estadoOriginal,ds_estadoOriginal, uri, cardimage, id_base, ds_genre, id_genre, id_cidade, id_estado, id_local, ds_genreOriginal)
SELECT DISTINCT id_evento, outofdate, (ds_evento + ' ' + ISNULL(ds_nome_teatro,'') + ' ' + ISNULL(ds_municipio,'') + ' ' + ISNULL(sg_estado,'') + ' ' + ISNULL(ds_estado,'') + ' ' + ISNULL(ds_genre,'')), ds_evento, ds_nome_teatro, ds_municipio,sg_estado,ds_estado, ds_eventoOriginal, ds_nome_teatroOriginal, ds_municipioOriginal,sg_estadoOriginal,ds_estadoOriginal, uri, cardimage, id_base, ds_genre, id_genre, id_municipio, id_estado, id_local_evento, ds_genreOriginal from #toAdd
WHERE (ds_evento + ' ' + ISNULL(ds_nome_teatro,'') + ' ' + ISNULL(ds_municipio,'') + ' ' + ISNULL(sg_estado,'') + ' ' + ISNULL(ds_estado,'')) IS NOT NULL

DELETE FROM search WHERE outofdate=1;