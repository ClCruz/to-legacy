-- pr_sell_type '8cc26a74-7e65-411e-b854-f7b281a46e01', 52
go

ALTER PROCEDURE dbo.pr_sell_type (@id_ticketoffice_user UNIQUEIDENTIFIER, @id_payment INT) 

AS

SET NOCOUNT ON;

DECLARE @nextStep VARCHAR(100)
        ,@isMoney BIT
        ,@isFree BIT
        ,@isCreditCard BIT
        ,@isDebitCard BIT
        ,@PagarMe BIT

UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET id_payment_type=@id_payment WHERE id_ticketoffice_user=@id_ticketoffice_user

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

IF (@isCreditCard = 1 OR @isDebitCard = 1) AND @PagarMe = 1
BEGIN
    SET @nextStep = 'pinpad'
END
ELSE
BEGIN
    SET @nextStep = 'direct'
END


SELECT @nextStep nextStep, @isMoney isMoney, @isCreditCard isCreditCard, @isDebitCard isDebitCard, @isFree isFree, @PagarMe PagarMe