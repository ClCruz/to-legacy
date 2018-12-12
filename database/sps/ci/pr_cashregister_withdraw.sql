ALTER PROCEDURE dbo.pr_cashregister_withdraw (@id_ticketoffice_user UNIQUEIDENTIFIER
        ,@payment INT
        ,@amount DECIMAL(18,2)
        ,@justificative VARCHAR(250) = NULL)

AS

-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER
--         ,@amount VARCHAR(MAX)
--         ,@justificative VARCHAR(250) = NULL

-- SELECT @id_ticketoffice_user='8cc26a74-7e65-411e-b854-f7b281a46e01'
--         ,@amount='48#10000|52#5000'
--         --,@amount='48#15000|52#5000'
--         ,@justificative=NULL

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#cashregister', 'U') IS NOT NULL
    DROP TABLE #cashregister; 

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

CREATE TABLE #amount (codTipForPagto INT, amount DECIMAL(18,2));

CREATE TABLE #cashregister (codTipForPagto INT, TipForPagto VARCHAR(1000), amount DECIMAL(18,2))

CREATE TABLE #result (codTipForPagto INT, TipForPagto VARCHAR(1000), amount DECIMAL(18,2), amountDeclared DECIMAL(18,2), diff DECIMAL(18,2))

INSERT INTO #cashregister EXEC pr_cashregister_list @id_ticketoffice_user;

DELETE FROM #cashregister WHERE codTipForPagto<>@payment

INSERT INTO #amount (codTipForPagto, amount)
    SELECT @payment, @amount

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

DECLARE @CodSaque INT = NULL
        ,@canDo BIT = 0

SELECT @canDo=1 FROM #result WHERE diff>=0

IF @canDo = 1
BEGIN
	SELECT @CodSaque = (SELECT COALESCE(MAX(CodSaque),0)+1 FROM tabSaque)

	INSERT INTO tabSaque (CodSaque, CodCaixa, CodUsuario, DatOperacao ,DatMovimento, TipSaque, CodMovimento)
  		VALUES(@CodSaque, @CodCaixa, @CodUsuario, GETDATE(), @date,'S', @CodMovimento)


    INSERT INTO tabSaqDetalhe (CodSaque, CodTipForPagto, Valor)
        SELECT @CodSaque, r.codTipForPagto, r.amountDeclared FROM #result r WHERE r.diff>0
END

UPDATE tabMovCaixa SET 
Saldo = (Saldo - COALESCE((select SUM(amountDeclared) from #result WHERE diff>0),0))
-- ,ObsDiferenca = @justificative
-- ,StaMovimento='F'
WHERE DatMovimento = @date
AND CodCaixa = @CodCaixa AND CodMovimento = @CodMovimento AND StaMovimento='A'

SELECT 1 success

