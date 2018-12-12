CREATE PROCEDURE dbo.pr_login_keepalive (@token VARCHAR(100)) 

AS

UPDATE CI_MIDDLEWAY..mw_cliente SET dt_token_valid=DATEADD(minute,30,GETDATE()) WHERE token=@token