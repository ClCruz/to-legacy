ALTER PROCEDURE dbo.pr_cashregister_close (@id_ticketoffice_user UNIQUEIDENTIFIER
        ,@amount VARCHAR(MAX)
        ,@justificative VARCHAR(250) = NULL)

AS

-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER
--         ,@amount VARCHAR(MAX)
--         ,@justificative VARCHAR(250) = NULL

-- SELECT @id_ticketoffice_user='8CC26A74-7E65-411E-B854-F7B281A46E01'
--         ,@amount='48#60000'
--         --,@amount='48#15000|52#5000'
--         ,@justificative=''
SET NOCOUNT ON;

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

IF OBJECT_ID('tempdb.dbo.#cashregister', 'U') IS NOT NULL
    DROP TABLE #cashregister; 

IF OBJECT_ID('tempdb.dbo.#amount', 'U') IS NOT NULL
    DROP TABLE #amount; 

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

CREATE TABLE #amount (codTipForPagto INT, amount DECIMAL(18,2));

CREATE TABLE #cashregister (codTipForPagto INT, TipForPagto VARCHAR(1000), amount DECIMAL(18,2), [date] VARCHAR(10))

CREATE TABLE #result (codTipForPagto INT, TipForPagto VARCHAR(1000), amount DECIMAL(18,2), amountDeclared DECIMAL(18,2), diff DECIMAL(18,2))

INSERT INTO #cashregister EXEC pr_cashregister_list @id_ticketoffice_user;

-- select * from #cashregister
IF @amount IS NOT NULL AND @amount <> ''
BEGIN
    INSERT INTO #amount (codTipForPagto, amount)
    SELECT SUBSTRING(Item,1,CHARINDEX('#',Item)-1), CONVERT(DECIMAL(18,2),SUBSTRING(Item,CHARINDEX('#',Item)+1,LEN(Item)))/100 FROM dbo.splitString(@amount, '|')
END

INSERT INTO #result (codTipForPagto, TipForPagto, amount, amountDeclared, diff)
SELECT
    cr.codTipForPagto
    ,cr.TipForPagto
    ,cr.amount
    ,a.amount
    ,cr.amount-a.amount
FROM #cashregister cr
INNER JOIN #amount a ON cr.codTipForPagto=a.codTipForPagto

DECLARE @codCaixa INT, @codUsuario INT, @codMovimento INT, @date DATETIME
SELECT
    @codCaixa=codCaixa
    ,@codUsuario=codUsuario
FROM CI_MIDDLEWAY..ticketoffice_user_base
WHERE id_ticketoffice_user=@id_ticketoffice_user;

SELECT @codMovimento=Codmovimento
        ,@date=DatMovimento
FROM tabMovCaixa
WHERE CodCaixa=@codCaixa AND CodUsuario=@codUsuario AND StaMovimento='A'

DECLARE @CodSaqueDiff INT = NULL
        ,@codSaqueNormal INT = NULL
        ,@hasDiff BIT = 0
        ,@hasNormal BIT = 0

SELECT @hasDiff=1 FROM #result WHERE diff<>0
SELECT @hasNormal=1 FROM #result WHERE diff=0

INSERT INTO CI_MIDDLEWAY..ticketoffice_cashregister_closed (id_ticketoffice_user,close_date,codTipForPagto,TipForPagto,amount,amountDeclared,diff,id_base)
SELECT
    @id_ticketoffice_user
    ,@date
    ,cr.codTipForPagto
    ,cr.TipForPagto
    ,cr.amount
    ,a.amount
    ,cr.amount-a.amount
    ,@id_base
FROM #cashregister cr
INNER JOIN #amount a ON cr.codTipForPagto=a.codTipForPagto



-- SELECT @CodSaqueDiff, r.codTipForPagto, r.diff FROM #result r WHERE r.diff<>0
-- SELECT @codSaqueNormal, r.codTipForPagto, r.amountDeclared FROM #result r WHERE r.diff=0
-- SELECT @codSaqueNormal, r.codTipForPagto, r.amountDeclared FROM #result r WHERE r.diff<>0
-- RETURN;

IF @hasDiff = 1
BEGIN
	SELECT @CodSaqueDiff = (SELECT COALESCE(MAX(CodSaque),0)+1 FROM tabSaque)

	INSERT INTO tabSaque (CodSaque, CodCaixa, CodUsuario, DatOperacao ,DatMovimento, TipSaque, CodMovimento)
  		VALUES(@CodSaqueDiff, @CodCaixa, @CodUsuario, GETDATE(), @date,'D', @CodMovimento)


    INSERT INTO tabSaqDetalhe (CodSaque, CodTipForPagto, Valor)
        SELECT @CodSaqueDiff, r.codTipForPagto, r.diff FROM #result r WHERE r.diff<>0
END

SELECT @codSaqueNormal = (SELECT COALESCE(MAX(CodSaque),0)+1 FROM tabSaque)

INSERT INTO tabSaque (CodSaque, CodCaixa, CodUsuario, DatOperacao ,DatMovimento, TipSaque, CodMovimento)
    VALUES(@codSaqueNormal, @CodCaixa, @CodUsuario, GETDATE(), @date,'F', @CodMovimento)

INSERT INTO tabSaqDetalhe (CodSaque, CodTipForPagto, Valor)
    SELECT @codSaqueNormal, r.codTipForPagto, r.amountDeclared FROM #result r WHERE r.diff=0

IF @hasDiff = 1
BEGIN
    INSERT INTO tabSaqDetalhe (CodSaque, CodTipForPagto, Valor)
        SELECT @codSaqueNormal, r.codTipForPagto, r.amountDeclared FROM #result r WHERE r.diff<>0
END

UPDATE tabMovCaixa SET 
Saldo = (Saldo - COALESCE((select SUM(amount) from #result),0))
,ObsDiferenca = @justificative
,StaMovimento='F'
WHERE DatMovimento = @date
AND CodCaixa = @CodCaixa AND CodMovimento = @CodMovimento AND StaMovimento='A'

SELECT 1 success

