CREATE PROCEDURE dbo.pr_login_legacy_keepalive (@id INT) 

AS

UPDATE CI_MIDDLEWAY..mw_cliente SET dt_token_valid=DATEADD(minute,30,GETDATE()) WHERE id_cliente=@id