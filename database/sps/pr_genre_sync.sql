CREATE PROCEDURE dbo.pr_genre_sync (@id_base INT)

AS

SET NOCOUNT ON;

-- DECLARE @id_base INT = 213

IF OBJECT_ID('tempdb.dbo.#helper', 'U') IS NOT NULL
    DROP TABLE #helper; 

IF OBJECT_ID('tempdb.dbo.#toAdd', 'U') IS NOT NULL
    DROP TABLE #toAdd; 

IF OBJECT_ID('tempdb.dbo.#toAddNew', 'U') IS NOT NULL
    DROP TABLE #toAddNew;

create table #helper (id int , name varchar(100))

create table #toAdd (id int , name varchar(100))

create table #toAddNew (id int , [name] varchar(100), [status] VARCHAR(100))

DECLARE @toExecute NVARCHAR(MAX)

SELECT @toExecute = N'INSERT INTO #helper (id, name) SELECT codTipPeca, LOWER(LTRIM(RTRIM(TipPeca))) FROM ' + ds_nome_base_sql + '..tabTipPeca' from CI_MIDDLEWAY..mw_base where id_base=@id_base

EXEC sp_executesql @toExecute

INSERT INTO #toAdd (id, [name])
SELECT g.id, g.name
FROM CI_MIDDLEWAY..genre g

DELETE d 
FROM #toAdd d
INNER JOIN #helper h ON RTRIM(LTRIM(h.name))=RTRIM(LTRIM(LOWER(d.name))) COLLATE SQL_Latin1_General_Cp1251_CS_AS

DECLARE @maxId_CI INT
SELECT @maxId_CI=(MAX(id)+1) FROM #helper

INSERT INTO #toAddNew (id, [name], [status])
SELECT 
(ROW_NUMBER() OVER (ORDER BY [name])+@maxId_CI) id
,[name]
,'A'
FROM #toAdd

SELECT @toExecute = N'INSERT INTO ' + ds_nome_base_sql + '..tabTipPeca (codTipPeca, TipPeca, StaTipPeca) SELECT id, [name], [status] FROM #toAddNew' from CI_MIDDLEWAY..mw_base where id_base=@id_base

EXEC sp_executesql @toExecute

DECLARE @has BIT
        ,@maxId INT

SELECT @maxId=MAX(id) FROM CI_MIDDLEWAY..genre

SELECT @has = 1 FROM CI_MIDDLEWAY..genre_sync WHERE id_base=@id_base

IF @has = 1
BEGIN
    UPDATE CI_MIDDLEWAY..genre_sync SET last_id=@maxId, sync=GETDATE() WHERE id_base=@id_base
END
ELSE
BEGIN
    INSERT INTO CI_MIDDLEWAY..genre_sync (id_base, last_id, sync)
        SELECT @id_base, @maxId, GETDATE()
END