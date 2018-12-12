
CREATE PROCEDURE pr_ticketoffice_shoppingcart_tickettype (@id_ticketoffice_user UNIQUEIDENTIFIER, @indice INT, @id_ticket_type INT = NULL)

AS

-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER, @indice INT, @id_ticket_type INT = NULL

-- SET @id_ticketoffice_user='8CC26A74-7E65-411E-B854-F7B281A46E01'
-- SET @indice=87248
-- SET @id_ticket_type=151

SET NOCOUNT ON;

DECLARE @amount DECIMAL(19,2)
        ,@PerDesconto DECIMAL(19,2)
        ,@PerDescontoTB DECIMAL(19,2)
        , @total INT

SELECT
    @amount=CONVERT(INT,REPLACE(CONVERT(VARCHAR(30),(CONVERT(DECIMAL(19,2),a.ValPeca))),'.',''))
    ,@PerDesconto=(se.PerDesconto/100)
FROM CI_MIDDLEWAY..mw_apresentacao ap
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart tosc ON ap.id_apresentacao=tosc.id_apresentacao AND tosc.indice=@indice
INNER JOIN tabApresentacao a ON ap.CodApresentacao=a.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSetor se ON s.CodSala=se.CodSala
WHERE tosc.id_ticketoffice_user=@id_ticketoffice_user


IF @id_ticket_type IS NOT NULL
BEGIN
    SELECT
        @PerDescontoTB=(PerDesconto/100)
    FROM tabTipBilhete
    WHERE CodTipBilhete=@id_ticket_type

    SET @amount=@amount/100
    SET @amount=@amount-(@amount*@PerDesconto)
    SET @amount=@amount-(@amount*@PerDescontoTB)

    SET @total = CONVERT(INT,REPLACE(CONVERT(VARCHAR(30),(CONVERT(DECIMAL(19,2),@amount))),'.',''))
    UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET id_ticket_type=@id_ticket_type, amount_topay=@total WHERE id_ticketoffice_user=@id_ticketoffice_user AND indice=@indice
END
ELSE 
BEGIN
    SET @amount=@amount/100
    SET @amount=@amount-(@amount*@PerDesconto)
    SET @total = CONVERT(INT,REPLACE(CONVERT(VARCHAR(30),(CONVERT(DECIMAL(19,2),@amount))),'.',''))

    UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET id_ticket_type=NULL, amount_topay=@total WHERE id_ticketoffice_user=@id_ticketoffice_user AND indice=@indice
END