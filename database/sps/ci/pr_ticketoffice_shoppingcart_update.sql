
CREATE PROCEDURE pr_ticketoffice_shoppingcart_update (@id_ticketoffice_user UNIQUEIDENTIFIER, @currentStep VARCHAR(10) = NULL, @id_payment_type INT = NULL, @quantity INT = NULL, @id_ticket_type INT = NULL)

AS

SET NOCOUNT ON;

IF @currentStep IS NOT NULL
BEGIN
    UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET currentStep=@currentStep, updated=getdate() WHERE id_ticketoffice_user=@id_ticketoffice_user
END

IF @id_payment_type IS NOT NULL
BEGIN
    UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET quantity=@quantity WHERE id_ticketoffice_user=@id_ticketoffice_user AND id_payment_type=@id_payment_type
END

IF @id_ticket_type IS NOT NULL
BEGIN
    DECLARE @perDesconto DECIMAL(18,2)

    SELECT
        @perDesconto=PerDesconto
    FROM tabTipBilhete
    WHERE CodTipBilhete=151

    SELECT
    *
    FROM CI_MIDDLEWAY..ticketoffice_shoppingcart 
    WHERE id_ticketoffice_user=@id_ticketoffice_user

    UPDATE CI_MIDDLEWAY..ticketoffice_shoppingcart SET id_ticket_type=@id_ticket_type WHERE id_ticketoffice_user=@id_ticketoffice_user AND id_payment_type=@id_payment_type
END