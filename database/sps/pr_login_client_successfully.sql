ALTER PROCEDURE dbo.pr_login_client_successfully(@id INT, @token VARCHAR(1000))

AS

UPDATE CI_MIDDLEWAY..mw_cliente SET token=@token, dt_token_valid=DATEADD(minute,30,GETDATE()) WHERE id_cliente=@id
