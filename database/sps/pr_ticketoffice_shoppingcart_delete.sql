ALTER PROCEDURE dbo.pr_ticketoffice_shoppingcart_delete (@id_ticketoffice_user UNIQUEIDENTIFIER, @indice INT = NULL, @id_payment_type INT = NULL)

AS

IF @indice IS NULL
BEGIN
    DELETE FROM dbo.ticketoffice_shoppingcart WHERE id_ticketoffice_user=@id_ticketoffice_user AND id_payment_type=@id_payment_type
END
ELSE
BEGIN
    DELETE FROM dbo.ticketoffice_shoppingcart WHERE id_ticketoffice_user=@id_ticketoffice_user AND indice=@indice
END