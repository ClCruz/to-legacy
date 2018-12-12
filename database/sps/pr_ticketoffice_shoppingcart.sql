
ALTER PROCEDURE pr_ticketoffice_shoppingcart (@id UNIQUEIDENTIFIER)

AS

SELECT
    tosc.id
    ,CONVERT(VARCHAR(10),tosc.created,103) + ' ' + CONVERT(VARCHAR(8),tosc.created,114) created
    ,tosc.id_ticketoffice_user
    ,tosc.id_event
    ,tosc.id_base
    ,tosc.id_apresentacao
    ,tosc.indice
    ,tosc.quantity
    ,tosc.currentStep
    ,tosc.id_payment_type
    ,tosc.amount
    ,tosc.amount_discount
    ,tosc.amount_topay
FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
WHERE tosc.id_ticketoffice_user=@id