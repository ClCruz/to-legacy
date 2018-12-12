-- exec pr_search @search='inner', @type='search', @startAt=0, @api='live_keykeykey'
-- select * from search where text like '%samba%' where id_evento=8157
-- GO

-- select * from search where id_genre=10

ALTER PROCEDURE dbo.pr_search(@search VARCHAR(100), @type VARCHAR(100), @startAt INT = 0, @howMany INT = 10, @city VARCHAR(100) = NULL, @state VARCHAR(100) = NULL, @api VARCHAR(100))

AS

SET NOCOUNT ON;

-- DECLARE @search VARCHAR(100) = 'comedia'
--         ,@type VARCHAR(100) = 'search_bygenre'
--         ,@startAt INT = 0, @howMany INT = 10
--         ,@api VARCHAR(100) = 'live_keykeykey'

IF @startAt IS NULL
    SET @startAt=0

IF @howMany IS NULL
    SET @howMany=10


DECLARE @searchText VARCHAR(100)
        ,@init INT = @startAt
        ,@end INT = @startAt+@howMany
        ,@top INT = 10+(@startAt+@howMany)
        ,@id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api


SET @searchText = dbo.RemoveSpecialChars(LTRIM(RTRIM(lower(@search) COLLATE SQL_Latin1_General_Cp1251_CS_AS)))


IF OBJECT_ID('tempdb.dbo.#aux', 'U') IS NOT NULL
    DROP TABLE #aux; 
IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

CREATE TABLE #aux (id_evento INT)

IF @type = 'search'
BEGIN
    INSERT INTO #aux (id_evento)
    SELECT s.id_evento
    FROM CI_MIDDLEWAY..search s
    WHERE s.[text] LIKE '%'+@searchText+'%' COLLATE SQL_Latin1_General_Cp1251_CS_AS;
END

IF @type = 'search_bycity'
BEGIN
    INSERT INTO #aux (id_evento)
    SELECT s.id_evento
    FROM CI_MIDDLEWAY..search s
    WHERE s.ds_municipio LIKE @searchText COLLATE SQL_Latin1_General_Cp1251_CS_AS;
END

IF @type = 'search_bystate'
BEGIN
    INSERT INTO #aux (id_evento)
    SELECT s.id_evento
    FROM CI_MIDDLEWAY..search s
    WHERE s.ds_estado LIKE @searchText COLLATE SQL_Latin1_General_Cp1251_CS_AS;
END

IF @type = 'search_bylocal'
BEGIN
    INSERT INTO #aux (id_evento)
    SELECT s.id_evento
    FROM CI_MIDDLEWAY..search s
    WHERE s.ds_nome_teatro LIKE @searchText COLLATE SQL_Latin1_General_Cp1251_CS_AS;
END

IF @type = 'search_bygenre'
BEGIN
    INSERT INTO #aux (id_evento)
    SELECT s.id_evento
    FROM CI_MIDDLEWAY..search s
    WHERE s.ds_genre LIKE @searchText COLLATE SQL_Latin1_General_Cp1251_CS_AS;
END


SELECT DISTINCT
    e.id_evento
    ,RTRIM(LTRIM(e.ds_evento)) ds_evento
    ,eei.cardimage
    ,eei.cardbigimage
    ,eei.[description]
    ,eei.uri
    ,b.ds_nome_teatro
    ,le.ds_local_evento
    ,mu.ds_municipio
    ,es.ds_estado
    ,es.sg_estado
    ,ap.dt_apresentacao
    ,SUBSTRING(
            (
                SELECT ','+subB.name + '|' + subB.img  AS [text()]
                FROM CI_MIDDLEWAY..mw_evento_badge subEB
                INNER JOIN CI_MIDDLEWAY..badge subB ON subEB.id_badge=subB.id
                WHERE subEB.id_evento=e.id_evento
                ORDER BY subEB.showOrder
                FOR XML PATH ('')
            ), 2, 4000) [badges]
,SUBSTRING(
        (
            SELECT ','+subpc.ds_promocao + '|' + subpa.ds_NomPatrocinador + '|' + subpc.Imag1Promocao + '|' + subpc.Imag2Promocao  AS [text()]
            FROM CI_MIDDLEWAY..mw_controle_evento subce
            LEFT JOIN CI_MIDDLEWAY..mw_promocao_controle subpc ON subce.id_promocao_controle=subpc.id_promocao_controle
            LEFT JOIN CI_MIDDLEWAY..mw_patrocinador subpa ON subpc.id_patrocinador=subpa.id_Patrocinador
            WHERE subce.id_evento=e.id_evento
            AND subpc.dt_inicio_promocao<=GETDATE()
            AND subpc.dt_fim_promocao>=GETDATE()
            FOR XML PATH ('')
        ), 2, 8000) [promotion]
INTO #result
    -- ,ROW_NUMBER() OVER (ORDER BY (SELECT e.id_evento)) AS row_number
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento
INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
INNER JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
INNER JOIN CI_MIDDLEWAY..mw_municipio mu ON le.id_municipio=mu.id_municipio
INNER JOIN CI_MIDDLEWAY..mw_estado es ON mu.id_estado=es.id_estado
INNER JOIN CI_MIDDLEWAY..partner_database pd ON e.id_base=pd.id_base AND pd.id_partner=@id_partner
WHERE e.id_evento in (SELECT id_evento FROM #aux)
ORDER BY RTRIM(LTRIM(e.ds_evento));

WITH final AS
(
SELECT DISTINCT
    id_evento
    ,ds_evento
    ,cardimage
    ,cardbigimage
    ,[description]
    ,uri
    ,ds_nome_teatro
    ,ds_local_evento
    ,ds_municipio
    ,ds_estado
    ,sg_estado
    ,badges
    ,promotion
    ,(CASE WHEN convert(varchar(5), MIN(dt_apresentacao),103) = convert(varchar(5), MAX(dt_apresentacao),103) THEN convert(varchar(5), MIN(dt_apresentacao),103) ELSE  convert(varchar(5), MIN(dt_apresentacao),103) + ' - ' + convert(varchar(5), max(dt_apresentacao),103) END) datas
    ,ROW_NUMBER() OVER (ORDER BY (SELECT ds_evento)) AS row_number
FROM #result
GROUP BY
 id_evento
    ,ds_evento
    ,cardimage
    ,cardbigimage
    ,[description]
    ,uri
    ,ds_nome_teatro
    ,ds_local_evento
    ,ds_municipio
    ,ds_estado
    ,sg_estado
    ,badges
    ,promotion
)
SELECT DISTINCT
    id_evento
    ,ds_evento
    ,cardimage
    ,cardbigimage
    ,[description]
    ,uri
    ,ds_nome_teatro
    ,ds_local_evento
    ,ds_municipio
    ,ds_estado
    ,sg_estado
    ,badges
    ,datas
    ,promotion
    ,row_number
FROM final
WHERE row_number BETWEEN @init AND @end
ORDER BY row_number

SET NOCOUNT OFF;