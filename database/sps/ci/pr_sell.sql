--pr_sell @id_ticketoffice_user='8cc26a74-7e65-411e-b854-f7b281a46e01',@id_payment=52
-- select * from CI_MIDDLEWAY..ticketoffice_shoppingcart
-- update CI_MIDDLEWAY..ticketoffice_shoppingcart set id_payment_type=52
-- select * from tabForPagamento where StaForPagto='A'
-- GO

ALTER PROCEDURE pr_sell (@id_ticketoffice_user UNIQUEIDENTIFIER
        ,@id_payment INT
        ,@isComplementoMeia BIT = 0
        ,@codCliente INT = NULL)

AS

SET NOCOUNT ON;


BEGIN TRY

  BEGIN TRANSACTION sell

  DECLARE @NumeroBIN VARCHAR(100) = NULL

IF @codCliente IS NOT NULL
BEGIN
    IF LTRIM(RTRIM(@codCliente)) = '' OR LTRIM(RTRIM(@codCliente)) = 'null'
        SET @codCliente = NULL
END
-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER
--         ,@id_payment INT
--         ,@isComplementoMeia BIT = 0
--         ,@codCliente INT = NULL
--         ,@NumeroBIN VARCHAR(6) = NULL

-- SET @id_ticketoffice_user='8cc26a74-7e65-411e-b854-f7b281a46e01'
-- SET @id_payment=52

DECLARE @now DATETIME = GETDATE()
        ,@codVenda VARCHAR(10) = NULL

DECLARE @cliente_Nome VARCHAR(1000) = '', @cliente_DDD VARCHAR(3) = '', @cliente_Telefone VARCHAR(50) = '', @cliente_Ramal VARCHAR(4) = '', @cliente_CPF VARCHAR(14) = '', @cliente_RG VARCHAR(15) = ''

DECLARE @NomeEmpresa VARCHAR(100)
        ,@Id_Cartao_patrocinado INT
        ,@id_base INT
        ,@idpedidovenda BIGINT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT @NomeEmpresa=NomEmpresa FROM tabEmpresa

UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET id_payment_type=@id_payment WHERE id_ticketoffice_user=@id_ticketoffice_user

IF @codVenda IS NULL
BEGIN
    IF OBJECT_ID('tempdb.dbo.#idpedidovenda', 'U') IS NOT NULL
        DROP TABLE #idpedidovenda; 
    IF OBJECT_ID('tempdb.dbo.#codVendaTemp', 'U') IS NOT NULL
        DROP TABLE #codVendaTemp; 

    DECLARE @userHelp VARCHAR(100) = CONVERT(VARCHAR(100),@id_ticketoffice_user)

    CREATE TABLE #idpedidovenda (id bigint);
    CREATE TABLE #codVendaTemp (codVenda VARCHAR(10));

    INSERT INTO #idpedidovenda EXEC CI_MIDDLEWAY..seqPedidoVenda @userHelp;

    SELECT @idpedidovenda=id FROM #idpedidovenda

    INSERT INTO #codVendaTemp EXEC CI_MIDDLEWAY..seqCodVenda @idpedidovenda;

    SELECT @codVenda=codVenda FROM #codVendaTemp
END

DECLARE @codCaixa INT, @codUsuario INT
        ,@codMovimento INT,@CodTipLancamento INT
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


UPDATE ls
SET ls.StaCadeira='V'
    ,ls.CodVenda=@codVenda
    ,ls.CodUsuario=@codUsuario
    ,ls.CodTipBilheteComplMeia=(CASE WHEN @isComplementoMeia = 1 THEN tosc.id_ticket_type ELSE ls.CodTipBilheteComplMeia END)
    ,ls.CodVendaComplMeia=(CASE WHEN @isComplementoMeia = 1 THEN @codVenda ELSE ls.CodTipBilheteComplMeia END)
    ,ls.StaCadeiraComplMeia=(CASE WHEN @isComplementoMeia = 1 THEN 'V' ELSE ls.CodTipBilheteComplMeia END)
FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON tosc.id_apresentacao=ap.id_apresentacao
INNER JOIN tabApresentacao a ON ap.CodApresentacao=a.CodApresentacao
INNER JOIN tabLugSala ls ON ls.CodApresentacao=a.CodApresentacao AND ls.Indice=tosc.indice
WHERE tosc.id_ticketoffice_user=@id_ticketoffice_user

DECLARE @amountDecimal DECIMAL(18,2)
        ,@amount INT

SELECT @amount=SUM(tosc.amount_topay) FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc --WHERE tosc.id_ticketoffice_user=@id_ticketoffice_user
SET @amountDecimal=CONVERT(DECIMAL(18,2),@amount)/100

UPDATE tabMovCaixa SET Saldo=COALESCE(SALDO+@amountDecimal,0) WHERE CodCaixa=@codCaixa AND StaMovimento='A'

DECLARE @NumLancamento INT
SELECT @NumLancamento = (SELECT COALESCE(MAX(NumLancamento),0)+1 FROM tabLancamento)

INSERT INTO tabLancamento (NumLancamento,CodTipBilhete,CodTipLancamento,CodApresentacao,Indice, 
CodUsuario,CodForPagto,CodCaixa,DatMovimento,QtdBilhete,ValPagto, DatVenda, CodMovimento)
    SELECT  
        @NumLancamento
        ,tosc.id_ticket_type
        ,(CASE WHEN ISNULL(ls.StaCadeiraComplMeia, 'T') = 'M' THEN 4 ELSE 1 END)
        ,a.CodApresentacao
        ,tosc.indice
        ,@codUsuario
        ,tosc.id_payment_type
        ,@codCaixa
        ,CONVERT(SMALLDATETIME,CONVERT(VARCHAR(8), @now,112) + ' ' + LEFT(CONVERT(VARCHAR, @now,114),8))
        ,tosc.quantity
        ,CONVERT(DECIMAL(18,2),tosc.amount_topay)/100
        ,@now
        ,@codMovimento
    FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
    INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON tosc.id_apresentacao=ap.id_apresentacao
    INNER JOIN tabApresentacao a ON ap.CodApresentacao=a.CodApresentacao
    INNER JOIN tabLugSala ls ON a.CodApresentacao=ls.CodApresentacao AND tosc.indice=ls.Indice
    WHERE tosc.id_ticketoffice_user=@id_ticketoffice_user

IF OBJECT_ID('tempdb.dbo.#helper', 'U') IS NOT NULL
    DROP TABLE #helper; 

CREATE TABLE #helper (indice int, id_apresentacao int, codapresentacao int, numLancamento INT NULL, id_shoppingCart UNIQUEIDENTIFIER, CodTipLancamento INT)

SELECT @cliente_Nome=c.Nome,@cliente_DDD=c.DDD, @cliente_Telefone=Telefone, @cliente_Ramal=Ramal, @cliente_CPF=CPF, @cliente_RG=RG FROM tabCliente c WHERE Codigo=@codCliente

INSERT INTO #helper (indice, id_apresentacao, codapresentacao, numLancamento, id_shoppingCart, CodTipLancamento)
    SELECT tosc.indice, tosc.id_apresentacao, a.CodApresentacao, l.NumLancamento, tosc.id, l.CodTipLancamento
    FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
    INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON tosc.id_apresentacao=ap.id_apresentacao
    INNER JOIN tabApresentacao a ON ap.CodApresentacao=a.CodApresentacao
    LEFT JOIN tabLancamento l ON ap.CodApresentacao=l.CodApresentacao AND tosc.indice=l.Indice
    WHERE tosc.id_ticketoffice_user=@id_ticketoffice_user

IF (@codCliente IS NOT NULL)
BEGIN
    INSERT INTO tabHisCliente (Codigo,NumLancamento,CodTipBilhete,CodTipLancamento,CodApresentacao,Indice)
        SELECT
        @codCliente
        ,h.numLancamento
        ,tosc.id_ticket_type
        ,h.CodTipLancamento
        ,h.codapresentacao
        ,h.indice
        FROM #helper h
        INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart tosc ON h.id_shoppingCart=tosc.id
END

INSERT INTO tabComprovante
	(CodVenda,TipDocumento,NomSala,
	Nome,Numero,DatValidade,
	DDD,Telefone,Ramal,
    CPF,RG,ForPagto,
	NomUsuario,StaImpressao,NomEmpresa,
	CodCliente,CodApresentacao,CodPeca)
SELECT TOP 1
    @codVenda,'V',s.NomSala
    ,@cliente_Nome,@NumeroBIN,''
    ,@cliente_DDD,@cliente_Telefone,@cliente_Ramal
    ,@cliente_CPF,@cliente_RG, tosc.id_payment_type
    ,@name,0,@NomeEmpresa
    ,@codCliente,h.codapresentacao,a.CodPeca
FROM #helper h
INNER JOIN tabApresentacao a ON h.codapresentacao=a.CodApresentacao
INNER JOIN tabPeca p ON a.CodPeca=p.codPeca
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart tosc ON h.id_shoppingCart=tosc.id

INSERT INTO tabControleSeqVenda (codapresentacao, indice, numseq, codbar, statusingresso)
SELECT 
    h.codapresentacao
    ,h.indice
    ,1
    ,
right('00000'+convert(varchar,h.codapresentacao),5)
+convert(char(1), sd.CodSetor)
+right(convert(varchar(8),a.DatApresentacao,112),4)
+right('0000'+replace(convert(varchar(5),a.HorSessao),':',''),4)
+right('00000'+convert(varchar(4),tosc.id_ticket_type),3)					
+right('00000'+convert(varchar(10),h.indice),5)
    ,'L'
FROM #helper h
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart tosc ON h.id_shoppingCart=tosc.id
INNER JOIN tabApresentacao a ON h.codapresentacao=a.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSalDetalhe sd ON sd.Indice=h.indice AND sd.CodSala=a.CodSala


INSERT INTO tabIngresso	(Indice,CodVenda,NomObjeto
,NomPeca,NomRedPeca,DatApresentacao
,HorSessao,Elenco,Autor
,Diretor,NomRedSala,TipBilhete
,ValPagto,CodCaixa,[Login]
,NomResPeca,CenPeca,NomSetor
,DatVenda,Qtde,PerDesconto
,StaImpressao,CodSala,Id_Cartao_patrocinado
,BINCartao)
SELECT 
h.indice,@codVenda
,SUBSTRING(sd.NomObjeto,1,10)
,SUBSTRING(p.NomPeca,1,35),SUBSTRING(p.NomRedPeca,1,35),a.DatApresentacao
,SUBSTRING(a.HorSessao,1,5),SUBSTRING(p.Elenco,1,50),SUBSTRING(p.Autor,1,50)
,SUBSTRING(p.Diretor,1,50),SUBSTRING(s.NomRedSala,1,6),SUBSTRING(tb.TipBilhete,1,20)
,(CONVERT(DECIMAL(18,2),tosc.amount_topay)/100),@codCaixa,SUBSTRING(@login, 0, 10)
,SUBSTRING(p.NomResPeca,0,6),p.CenPeca,SUBSTRING(se.NomSetor,1,26)
,@now, tosc.quantity,tb.PerDesconto
,0,s.CodSala,(SELECT TOP 1 ep.id_cartao_patrocinado FROM CI_MIDDLEWAY..mw_evento_patrocinado ep WHERE ep.CodPeca=a.CodPeca AND ep.id_base=@id_base AND convert(varchar, a.datapresentacao,112) between convert(varchar, ep.dt_inicio,112) and convert(varchar, ep.dt_fim ,112))-- ep.id_cartao_patrocinado
,@NumeroBIN
FROM #helper h
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart tosc ON h.id_shoppingCart=tosc.id
INNER JOIN tabApresentacao a ON h.codapresentacao=a.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSalDetalhe sd ON s.CodSala=sd.CodSala AND h.indice=sd.Indice
INNER JOIN tabSetor se ON sd.CodSetor=se.CodSetor AND a.CodSala=se.CodSala
INNER JOIN tabPeca p ON tosc.id_event=p.CodPeca
INNER JOIN tabTipBilhete tb ON tosc.id_ticket_type=tb.CodTipBilhete

DECLARE @CodLog INT
SELECT @CodLog = (SELECT COALESCE(MAX(IdLogOperacao),0)+1 FROM tabLogOperacao)
INSERT INTO tabLogOperacao (IdLogOperacao, DatOperacao, CodUsuario, Operacao) 
SELECT TOP 1
@CodLog
,@now
,@codUsuario
,'Venda de Ingressos - espetáculo '+ p.NomPeca + '  Dt.:' + convert(varchar(10),@now,103) + ' Cod.Venda:' + @codVenda
FROM #helper h
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart tosc ON h.id_shoppingCart=tosc.id
INNER JOIN tabPeca p ON tosc.id_event=p.CodPeca

UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET id_pedido_venda=@idpedidovenda, codVenda=@codVenda WHERE @id_ticketoffice_user=@id_ticketoffice_user

INSERT INTO CI_MIDDLEWAY..ticketoffice_shoppingcart_hist (id,created, id_shoppingcart_old,id_ticketoffice_user,id_event,id_base,id_apresentacao,indice,quantity,currentStep,id_payment_type,amount,amount_discount,amount_topay,updated,id_ticket_type,codVenda,id_pedido_venda, sell_date)
SELECT newid(),created, id,id_ticketoffice_user,id_event,id_base,id_apresentacao,indice,quantity,currentStep,id_payment_type,amount,amount_discount,amount_topay,updated,id_ticket_type,codVenda,id_pedido_venda, @now FROM CI_MIDDLEWAY..ticketoffice_shoppingcart WHERE id_ticketoffice_user=@id_ticketoffice_user


DECLARE @nextStep VARCHAR(100)
        ,@isMoney BIT
        ,@isFree BIT
        ,@isCreditCard BIT
        ,@isDebitCard BIT
        ,@PagarMe BIT

SELECT TOP 1
@isMoney=(CASE WHEN tfp.ClassifPagtoSAP = 'DI' THEN 1 ELSE 0 END)
,@isFree=(CASE WHEN tfp.ClassifPagtoSAP = 'CV' THEN 1 ELSE 0 END)
,@isCreditCard=(CASE WHEN tfp.ClassifPagtoSAP = 'CC' THEN 1 ELSE 0 END)
,@isDebitCard=(CASE WHEN tfp.ClassifPagtoSAP = 'CD' THEN 1 ELSE 0 END)
,@PagarMe=(CASE WHEN fp.StaPagarMe = 'S' THEN 1 ELSE 0 END)
FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
INNER JOIN tabForPagamento fp ON tosc.id_payment_type=fp.CodForPagto
INNER JOIN tabTipForPagamento tfp ON fp.CodTipForPagto=tfp.CodTipForPagto
WHERE tosc.id_ticketoffice_user=@id_ticketoffice_user

EXEC CI_MIDDLEWAY..pr_ticketoffice_shoppingcart_clear @id_ticketoffice_user

IF (@isCreditCard = 1 OR @isDebitCard = 1) AND @PagarMe = 1
BEGIN
    SET @nextStep = 'charge'
END
ELSE
BEGIN
    SET @nextStep = 'close'
END

SELECT @codVenda codVenda, @idpedidovenda id_pedido_venda, @nextStep nextStep, @isMoney isMoney, @isCreditCard isCreditCard, @isDebitCard isDebitCard, @isFree isFree, @PagarMe PagarMe


  COMMIT TRANSACTION sell
END TRY
BEGIN CATCH 
  IF (@@TRANCOUNT > 0)
   BEGIN
      ROLLBACK TRANSACTION sell
   END 
    SELECT
        ERROR_NUMBER() AS ErrorNumber,
        ERROR_SEVERITY() AS ErrorSeverity,
        ERROR_STATE() AS ErrorState,
        ERROR_PROCEDURE() AS ErrorProcedure,
        ERROR_LINE() AS ErrorLine,
        ERROR_MESSAGE() AS ErrorMessage
END CATCH