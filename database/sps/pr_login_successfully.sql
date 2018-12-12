ALTER PROCEDURE dbo.pr_login_successfully(@login VARCHAR(1000), @token VARCHAR(1000))

AS

UPDATE CI_MIDDLEWAY..ticketoffice_user SET lastLogin=GETDATE(), currentToken=@token, tokenValidUntil=DATEADD(minute,30,GETDATE()) WHERE lower(login)=lower(@login) AND active=1