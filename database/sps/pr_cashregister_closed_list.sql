
ALTER PROCEDURE dbo.pr_cashregister_closed_list 
    (@id_ticketoffice_user UNIQUEIDENTIFIER
    ,@id_base INT)

AS

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

DECLARE @lastDate DATETIME

SELECT TOP 1 @lastDate=close_date FROM CI_MIDDLEWAY..ticketoffice_cashregister_closed WHERE id_ticketoffice_user=@id_ticketoffice_user AND id_base=@id_base ORDER BY close_date DESC

SELECT
tou.[login]
,tou.name
,tocrc.id
,b.ds_nome_banco
,b.ds_nome_teatro
,b.ds_nome_base_sql
,tocrc.id_base
,tocrc.id_ticketoffice_user
,tocrc.TipForPagto
,tocrc.codTipForPagto
,tocrc.created
,tocrc.close_date
,tocrc.amount
,tocrc.amountDeclared
,tocrc.diff
INTO #result
FROM CI_MIDDLEWAY..ticketoffice_cashregister_closed tocrc
INNER JOIN CI_MIDDLEWAY..ticketoffice_user tou ON tocrc.id_ticketoffice_user=tou.id
INNER JOIN CI_MIDDLEWAY..mw_base b ON tocrc.id_base=b.id_base
WHERE tocrc.id_ticketoffice_user=@id_ticketoffice_user
AND tocrc.id_base=@id_base
AND tocrc.close_date=@lastDate
ORDER BY tocrc.TipForPagto


SELECT 
    [login]
    ,[name]
    ,id
    ,ds_nome_banco
    ,ds_nome_teatro
    ,ds_nome_base_sql
    ,id_base
    ,id_ticketoffice_user
    ,TipForPagto
    ,codTipForPagto
    ,CONVERT(VARCHAR(10),created,103) created
    ,CONVERT(VARCHAR(10),close_date,103) close_date
    ,amount
    ,amountDeclared
    ,diff
    ,(SELECT SUM(amount) FROM #result) amountTotal
    ,(SELECT SUM(amountDeclared) FROM #result) amountDeclaredTotal
    ,(SELECT SUM(diff) FROM #result) diffTotal
FROM #result
