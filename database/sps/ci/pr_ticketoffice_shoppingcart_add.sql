CREATE PROCEDURE pr_ticketoffice_shoppingcart_add (@currentStep varchar(10), @id_ticketoffice_user UNIQUEIDENTIFIER, @id_event INT, @id_base INT, @id_apresentacao INT,@indice INT, @quantity INT, @id_payment_type INT = NULL, @amount INT = NULL, @amount_discount INT = NULL, @amount_topay INT = NULL)

AS

SET NOCOUNT ON;

DECLARE @exist BIT = 0
        ,@PerDesconto DECIMAL(19,2) = 0

SELECT @exist=1 FROM CI_MIDDLEWAY..ticketoffice_shoppingcart WHERE id_ticketoffice_user=@id_ticketoffice_user AND indice IS NULL AND @id_payment_type IS NOT NULL AND id_payment_type=@id_payment_type

IF @amount IS NULL
BEGIN
    SELECT
        @amount=CONVERT(INT,REPLACE(CONVERT(VARCHAR(30),(CONVERT(DECIMAL(19,2),a.ValPeca))),'.',''))
        ,@PerDesconto=se.PerDesconto
    FROM CI_MIDDLEWAY..mw_apresentacao ap
    INNER JOIN tabApresentacao a ON ap.CodApresentacao=a.CodApresentacao
    INNER JOIN tabSala s ON a.CodSala=s.CodSala
    INNER JOIN tabSetor se ON s.CodSala=se.CodSala
    WHERE ap.id_apresentacao=@id_apresentacao

    IF @amount_topay IS NULL
    BEGIN
        SET @amount_topay=@amount-((@PerDesconto/100)*@amount)
    END
END


IF @exist=1
BEGIN
    EXEC pr_ticketoffice_shoppingcart_update @id_ticketoffice_user, @currentStep, @id_payment_type, @quantity
END
ELSE
BEGIN
    INSERT INTO CI_MIDDLEWAY..ticketoffice_shoppingcart (id_ticketoffice_user
    ,id_event
    ,id_base
    ,id_apresentacao
    ,indice
    ,quantity
    ,currentStep
    ,id_payment_type
    ,amount
    ,amount_discount
    ,amount_topay)
    VALUES (@id_ticketoffice_user, @id_event, @id_base, @id_apresentacao, @indice, @quantity, @currentStep, @id_payment_type, @amount, @amount_discount, @amount_topay)
END