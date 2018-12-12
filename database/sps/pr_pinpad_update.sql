
ALTER PROCEDURE dbo.pr_pinpad_update (@key VARCHAR(50),@pinpad_acquirerResponseCode VARCHAR(100),@pinpad_transactionId VARCHAR(100),@pinpad_executed BIT,@pinpad_error BIT,@pinpad_cancel BIT,@pinpad_ok BIT,@pinpad_fail BIT,@codVenda VARCHAR(10))

AS

UPDATE CI_MIDDLEWAY..ticketoffice_pinpad
SET
pinpad_acquirerResponseCode=@pinpad_acquirerResponseCode
,pinpad_transactionId=@pinpad_transactionId
,pinpad_executed=@pinpad_executed
,pinpad_error=@pinpad_error
,pinpad_cancel=@pinpad_cancel
,pinpad_ok=@pinpad_ok
,pinpad_fail=@pinpad_fail
,codVenda=@codVenda
WHERE [key]=@key

INSERT INTO CI_MIDDLEWAY..ticketoffice_gateway_result (id_ticketoffice_user, id_ticketoffice_shoppingcart, transactionKey, id_gateway)
SELECT TOP 1 topp.id_ticketoffice_user, tosc.id, topp.pinpad_transactionId, 6
FROM CI_MIDDLEWAY..ticketoffice_pinpad topp
INNER JOIN CI_MIDDLEWAY..ticketoffice_shoppingcart_hist tosc ON topp.codVenda=tosc.codVenda
WHERE [key]=@key