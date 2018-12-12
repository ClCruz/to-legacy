-- pr_cashregister_list '8cc26a74-7e65-411e-b854-f7b281a46e01' --d
ALTER PROCEDURE pr_cashregister_list (@id_ticketoffice_user UNIQUEIDENTIFIER)

AS
-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER ='8cc26a74-7e65-411e-b854-f7b281a46e01'

SET NOCOUNT ON;

DECLARE @data DATETIME
        ,@codCaixa INT
        ,@codUsuario INT
        ,@codMovimento INT
        ,@login VARCHAR(1000)
        ,@name VARCHAR(1000)

SELECT @login=tou.login
        ,@name=tou.name        
FROM CI_MIDDLEWAY..ticketoffice_user tou
WHERE tou.id=@id_ticketoffice_user

SELECT
    @data = DATEADD(dd, DATEDIFF(dd, 0, DatMovimento), 0)
    ,@codCaixa=mc.CodCaixa
    ,@codUsuario=mc.CodUsuario
    ,@codMovimento=mc.Codmovimento
FROM tabMovCaixa mc
INNER JOIN CI_MIDDLEWAY..ticketoffice_user_base toub ON mc.CodCaixa=toub.codCaixa AND mc.CodUsuario=toub.codUsuario
WHERE mc.StaMovimento='A'
AND toub.id_ticketoffice_user=@id_ticketoffice_user

IF OBJECT_ID('tempdb.dbo.#purchase', 'U') IS NOT NULL
    DROP TABLE #purchase; 
IF OBJECT_ID('tempdb.dbo.#withdraw', 'U') IS NOT NULL
    DROP TABLE #withdraw; 

CREATE TABLE #purchase ([date] DATETIME, codCaixa INT, CodTipForPagto INT, TipForPagto VARCHAR(1000), amount DECIMAL(18,2), codMovimento INT)
CREATE TABLE #withdraw ([date] DATETIME, codCaixa INT, CodTipForPagto INT, TipForPagto VARCHAR(1000), amount DECIMAL(18,2), codMovimento INT)

INSERT INTO #purchase ([date],codCaixa,CodTipForPagto,TipForPagto,amount,codMovimento)
SELECT 
    DATEADD(dd, DATEDIFF(dd, 0, l.DatMovimento), 0)
    ,l.CodCaixa
    ,tfp.CodTipForPagto
    ,tfp.TipForPagto
    ,COALESCE (SUM(l.ValPagto), 0)
    ,l.CodMovimento
FROM tabLancamento l 
INNER JOIN tabForPagamento fp ON l.CodForPagto = fp.CodForPagto 
INNER JOIN tabTipForPagamento tfp ON fp.CodTipForPagto = tfp.CodTipForPagto
WHERE l.CodMovimento=@codMovimento AND l.CodUsuario=@codUsuario AND l.CodCaixa=@codCaixa
GROUP BY 
DATEADD(dd, DATEDIFF(dd, 0, l.DatMovimento), 0)
,l.CodCaixa
,tfp.CodTipForPagto
,tfp.TipForPagto
,l.CodMovimento

INSERT INTO #withdraw ([date],codCaixa,CodTipForPagto,TipForPagto,amount,codMovimento)
SELECT 
    DATEADD(dd, DATEDIFF(dd, 0, s.DatMovimento), 0)
    ,s.CodCaixa
    ,sd.CodTipForPagto
    ,tfp.TipForPagto
    ,SUM(COALESCE (sd.Valor, 0))
    ,s.CodMovimento
FROM tabSaque s
INNER JOIN dbo.tabSaqDetalhe sd ON s.CodSaque = sd.CodSaque
INNER JOIN tabTipForPagamento tfp ON sd.CodTipForPagto = tfp.CodTipForPagto
WHERE s.CodMovimento=@codMovimento AND s.CodCaixa=@codCaixa AND s.CodUsuario=@codUsuario
GROUP BY 
    DATEADD(dd, DATEDIFF(dd, 0, s.DatMovimento), 0)
    ,sd.CodTipForPagto
    ,tfp.TipForPagto
    ,s.CodCaixa
    ,s.CodMovimento


SELECT
p.CodTipForPagto
,p.TipForPagto
,SUM(COALESCE(p.amount, 0)-COALESCE(wd.amount, 0)) AS amount
,CONVERT(VARCHAR(10),@data,103) [date]
,@login [login]
,@name [name]
FROM #purchase p
LEFT JOIN #withdraw wd ON p.codCaixa=wd.codCaixa AND p.codMovimento=wd.codMovimento
                            AND p.CodTipForPagto = wd.CodTipForPagto
                            AND p.[date]=wd.[date]
GROUP BY p.CodTipForPagto, p.TipForPagto

