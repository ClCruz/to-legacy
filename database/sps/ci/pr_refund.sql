
-- select * from tabLugSala where CodApresentacao in (SELECT CodApresentacao FROM tabApresentacao where CodPeca=146)
-- select * from CI_MIDDLEWAY..ticketoffice_gateway_result
-- select * from CI_MIDDLEWAY..ticketoffice_pinpad
--select * from CI_MIDDLEWAY..ticketoffice_shoppingcart
--pr_refund '8cc26a74-7e65-411e-b854-f7b281a46e01', '164FOOABCO', 0, '87517,87518'
/*
   select *
    FROM tabLugSala d
    WHERE d.CodVenda='1E7EDOABEO'
    
select * from CI_MIDDLEWAY..ticketoffice_shoppingcart_hist where codVenda='1E7EDOABEO'
*/

go


CREATE PROCEDURE dbo.pr_refund (
    @id_ticketoffice_user UNIQUEIDENTIFIER
    ,@codVenda VARCHAR(10)
    ,@all BIT = 0
    ,@indiceList VARCHAR(MAX) = NULL)

AS

-- DECLARE    @id_ticketoffice_user UNIQUEIDENTIFIER = '8cc26a74-7e65-411e-b854-f7b281a46e01'
--     ,@all BIT = 0
--     ,@codVenda VARCHAR(10) = '1Q4BGGACOO'
--     ,@indiceList VARCHAR(MAX) = '87709,87710'


SET NOCOUNT ON;

DECLARE @id_base INT
        ,@amount INT
        ,@now DATETIME = GETDATE()

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

IF OBJECT_ID('tempdb.dbo.#indice', 'U') IS NOT NULL
    DROP TABLE #indice; 

IF OBJECT_ID('tempdb.dbo.#helper', 'U') IS NOT NULL
    DROP TABLE #helper; 

CREATE TABLE #indice (indice int);

CREATE TABLE #helper (id_apresentacao INT, id_event INT, CodApresentacao INT, CodPeca INT, CodSala INT, id_evento INT, id_payment_type INT, id_ticket_type INT, amount INT, NumLancamento INT NULL, Indice INT);

IF @all = 1
BEGIN
    INSERT INTO #indice (indice)
        SELECT ls.Indice
        FROM tabLugSala ls
        WHERE ls.CodVenda=@codVenda AND StaCadeira='V'
END

IF @indiceList IS NOT NULL
BEGIN
    INSERT INTO #indice (indice)
        SELECT Item FROM dbo.splitString(@indiceList, ',')

    DECLARE @totalDB INT
            ,@totalInHands INT

    SELECT @totalDB=COUNT(*)
    FROM tabLugSala ls
    WHERE ls.CodVenda=@codVenda AND StaCadeira='V'

    SELECT @totalInHands=COUNT(*)
    FROM #indice ls

    IF @totalDB<=@totalInHands
        SET @all=1
END

DECLARE @total INT
SELECT @total = COUNT(*) FROM #indice

INSERT INTO #helper (id_apresentacao, id_event, CodApresentacao, CodPeca, CodSala, id_evento, id_payment_type, id_ticket_type, amount, NumLancamento, Indice)
SELECT tosch.id_apresentacao, tosch.id_event, a.CodApresentacao, a.CodPeca, a.CodSala, e.id_evento, tosch.id_payment_type, tosch.id_ticket_type, tosch.amount_topay, l.NumLancamento, tosch.indice
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_evento e ON p.CodPeca=e.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND a.CodApresentacao=ap.CodApresentacao
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart_hist tosch ON ls.Indice=tosch.indice AND ap.id_apresentacao=tosch.id_apresentacao AND p.CodPeca=tosch.id_event AND tosch.id_base=@id_base
LEFT JOIN tabLancamento l ON l.CodApresentacao = ls.CodApresentacao AND l.Indice = ls.Indice and codtiplancamento NOT IN (4,2)
WHERE
    ls.Indice IN (SELECT indice FROM #indice)

SELECT @amount=SUM(h.amount) FROM #helper h

DECLARE @codCaixa INT, @codUsuario INT, @codMovimento INT
        ,@name VARCHAR(1000), @login VARCHAR(1000)
SELECT
    @codCaixa=codCaixa
    ,@codUsuario=codUsuario
    ,@name=u.name
    ,@login=u.[login]
FROM CI_MIDDLEWAY..ticketoffice_user_base toub
INNER JOIN CI_MIDDLEWAY..ticketoffice_user u ON toub.id_ticketoffice_user=u.id
WHERE id_ticketoffice_user=@id_ticketoffice_user;

SELECT @codMovimento=Codmovimento
FROM tabMovCaixa
WHERE CodCaixa=@codCaixa AND CodUsuario=@codUsuario AND StaMovimento='A'

DECLARE @CodCliente INT
SELECT @CodCliente=c.CodCliente FROM tabComprovante c WHERE c.CodVenda=@codVenda

-- select @total, @CodCliente, @codMovimento,@codCaixa
--select * from #helper
-- return;

IF @total>0
BEGIN

    UPDATE csv
        SET statusingresso='E'
    FROM tabControleSeqVenda csv 
    INNER JOIN #helper h ON csv.CodApresentacao=h.CodApresentacao
    WHERE
        csv.Indice IN (SELECT indice FROM #indice)

    UPDATE tabMovCaixa 
        SET Saldo = COALESCE(Saldo - (CONVERT(DECIMAL(18,2),@amount)/100),0)
    WHERE CodCaixa = @CodCaixa
    AND StaMovimento = 'A'

    DELETE d
    FROM tabIngressoAgregados d
    INNER JOIN #indice h ON d.Indice=h.indice
    WHERE d.CodVenda=@codVenda

    DELETE d
    -- select *
    FROM tabIngresso d
    INNER JOIN #indice h ON d.Indice=h.indice
    WHERE d.CodVenda=@codVenda

    IF @all = 1
    BEGIN
        DELETE d
        FROM tabcomprovante d
        WHERE d.CodVenda=@codVenda
    END

    DELETE d
    FROM tabLugSala d
    INNER JOIN #helper h ON d.CodApresentacao=h.CodApresentacao AND d.Indice=h.Indice
    WHERE d.CodVenda=@codVenda

    IF @all = 1
    BEGIN
        DELETE d
        FROM CI_MIDDLEWAY..ticketoffice_gateway_result d
        INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart_hist tosch ON d.id_ticketoffice_shoppingcart=tosch.id
        INNER JOIN #indice i ON tosch.indice=i.indice
        WHERE tosch.codVenda=@codVenda
    END

    DELETE d
    FROM CI_MIDDLEWAY..ticketoffice_shoppingcart_hist d
    INNER JOIN #indice i ON d.indice=i.indice
    WHERE d.codVenda=@codVenda
    
    INSERT INTO tabLancamento (NumLancamento, CodTipBilhete, CodTipLancamento, CodApresentacao, Indice,CodUsuario, CodForPagto, CodCaixa, DatMovimento, QtdBilhete, ValPagto, DatVenda, CodMovimento)
        SELECT NumLancamento, CodTipBilhete, 2, CodApresentacao, l.Indice, @CodUsuario, CodForPagto, @CodCaixa, @now, -1, COALESCE(ValPagto,0)*-1, GETDATE(), @CodMovimento
        FROM tabLancamento l
        INNER JOIN #indice i ON l.Indice=i.indice
        WHERE NumLancamento in (SELECT NumLancamento FROM #helper)

    IF @CodCliente IS NOT NULL
        INSERT INTO tabHisCliente (Codigo, NumLancamento, CodTipBilhete, CodTipLancamento, CodApresentacao, Indice)
            SELECT @CodCliente, l.NumLancamento, l.CodTipBilhete, 2, l.CodApresentacao, l.Indice
            FROM tabLancamento l
            WHERE l.NumLancamento in (SELECT NumLancamento FROM #helper)
            AND l.NumLancamento NOT IN (SELECT sub.NumLancamento FROM tabHisCliente sub WHERE sub.NumLancamento=l.NumLancamento AND sub.Indice=l.Indice AND sub.CodApresentacao=l.CodApresentacao )
    -- VALUES (@CodCliente, @NumLancamento, @CodTipBilhete, 2, @CodApresentacao, @Indice)

    DECLARE @transactionKey VARCHAR(MAX)
    SELECT TOP 1
        @transactionKey=togr.transactionKey
    FROM CI_MIDDLEWAY..ticketoffice_gateway_result togr
    INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart_hist tosc ON togr.id_ticketoffice_shoppingcart=tosc.id
    WHERE tosc.codVenda=@codVenda

    SELECT
        1 success
        ,@transactionKey [key]
        ,@amount amount
    return;
END

SELECT 0 success
