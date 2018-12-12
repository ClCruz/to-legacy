
ALTER PROCEDURE dbo.pr_pinpad_get (@key VARCHAR(50))

AS
SELECT id_ticketoffice_user
,[key]
,id_base,base
,amount
,codPeca,id_apresentacao,id_evento
,pinpad_acquirerResponseCode,pinpad_transactionId,pinpad_executed,pinpad_error,pinpad_cancel,pinpad_ok,pinpad_fail
,codVenda
,id_payment
,codCliente
FROM CI_MIDDLEWAY..ticketoffice_pinpad
WHERE [key]=@key