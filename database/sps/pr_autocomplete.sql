-- exec pr_autocomplete 'come', @api='live_keykeykey'
-- select * from search where text like '%rubi%'
GO

ALTER PROCEDURE dbo.pr_autocomplete(@search VARCHAR(100), @city VARCHAR(100) = NULL, @state VARCHAR(100) = NULL, @api VARCHAR(100) = NULL)

AS

-- DECLARE @search VARCHAR(100)
--         ,@api VARCHAR(100) = 'live_keykeykey'
-- SET @search ='rubi';

DECLARE @searchText VARCHAR(100)
        ,@id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api

SET @searchText = dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(@search) COLLATE SQL_Latin1_General_Cp1251_CS_AS)))

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#tomerge', 'U') IS NOT NULL
    DROP TABLE #tomerge; 

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

CREATE TABLE #result (id_evento int NULL, [description] varchar(max), [type] varchar(50), [order] int, notselectable bit, uri varchar(1000), cardimage varchar(1000), id INT NULL)

SELECT
    s.id
    ,s.created
    ,s.[text]
    ,s.outofdate
    ,s.id_evento
    ,s.ds_evento
    ,s.ds_nome_teatro
    ,s.ds_municipio
    ,s.sg_estado
    ,s.ds_estado
    ,s.ds_genre
    ,s.ds_eventoOriginal
    ,s.ds_nome_teatroOriginal
    ,s.ds_municipioOriginal
    ,s.sg_estadoOriginal
    ,s.ds_estadoOriginal
    ,s.ds_genreOriginal
    ,s.uri
    ,s.cardImage
    ,s.id_cidade
    ,s.id_estado
    ,s.id_genre
    ,s.id_local
INTO #tomerge
FROM search s
INNER JOIN CI_MIDDLEWAY..partner_database pd ON s.id_base=pd.id_base AND pd.id_partner=@id_partner
WHERE s.[text] LIKE '%'+@searchText+'%'

--Event
INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT NULL, 'Eventos', 'event', 0, 1, NULL, NULL, NULL

INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT TOP 10 id_evento, RTRIM(LTRIM(ds_eventoOriginal)), 'event', 0, 0, uri, cardImage, NULL
FROM #tomerge
WHERE [ds_evento] LIKE '%'+@searchText+'%'
GROUP BY id_evento, ds_evento, ds_eventoOriginal, uri, cardimage
ORDER BY ds_eventoOriginal

IF @@ROWCOUNT = 0
    DELETE FROM #result where [type]='event' AND notselectable=1
--Local

INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT NULL, 'Locais', 'local', 1, 1, NULL, NULL, NULL

INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT TOP 10 NULL, ds_nome_teatroOriginal, 'local', 1,0, NULL, NULL, id_local
FROM #tomerge
WHERE [ds_nome_teatro] LIKE '%'+@searchText+'%'
GROUP BY ds_nome_teatro, ds_nome_teatroOriginal, id_local
ORDER BY ds_nome_teatroOriginal

IF @@ROWCOUNT = 0
    DELETE FROM #result where [type]='local' AND notselectable=1

--CITY
INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT NULL, 'Cidades', 'city', 2, 1, NULL, NULL, NULL

INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT TOP 10 NULL, ds_municipioOriginal, 'city', 2,0, NULL, NULL, id_cidade
FROM #tomerge
WHERE [ds_municipio] LIKE '%'+@searchText+'%'
GROUP BY ds_municipio, ds_municipioOriginal, id_cidade
ORDER BY ds_municipioOriginal

IF @@ROWCOUNT = 0
    DELETE FROM #result where [type]='city' AND notselectable=1

--genre
INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT NULL, 'Gênero', 'genre', 3, 1, NULL, NULL, NULL

INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT TOP 10 NULL, ds_genreOriginal, 'genre', 3,0, NULL, NULL, id_genre
FROM #tomerge
WHERE [ds_genre] LIKE '%'+@searchText+'%'
GROUP BY ds_genre, ds_genreOriginal, id_genre
ORDER BY ds_genreOriginal

IF @@ROWCOUNT = 0
    DELETE FROM #result where [type]='genre' AND notselectable=1

--STATE
INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT NULL, 'Estados', 'state', 4, 1, NULL, NULL, NULL

INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage, id)
SELECT TOP 10 NULL, sg_estadoOriginal, 'state', 4,0, NULL, NULL, id_estado
FROM #tomerge
WHERE [sg_estado] LIKE '%'+@searchText+'%'
GROUP BY sg_estado, sg_estadoOriginal, id_estado
ORDER BY sg_estadoOriginal

IF @@ROWCOUNT = 0
    DELETE FROM #result where [type]='state' AND notselectable=1

--STATE LONG
-- INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage)
-- SELECT NULL, 'Estados', 'stateLong', 5, 1, NULL, NULL

-- INSERT INTO #result (id_evento, [description], [type], [order], [notselectable], uri, cardImage)
-- SELECT TOP 10 NULL, ds_estadoOriginal, 'stateLong',5,0, NULL, NULL
-- FROM #tomerge
-- WHERE [ds_estado] LIKE '%'+@searchText+'%'
-- GROUP BY ds_estado, ds_estadoOriginal

-- IF @@ROWCOUNT = 0
--     DELETE FROM #result where [type]='stateLong' AND notselectable=1

SELECT r.id_evento, r.[description], r.[type], r.notselectable, uri, cardimage, id from #result r ORDER BY r.[order], r.notselectable DESC, r.[description]