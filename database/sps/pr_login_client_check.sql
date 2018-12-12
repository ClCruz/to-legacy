-- pr_login_client_check 'blcoccaro@gmail.com'
GO

ALTER PROCEDURE dbo.pr_login_client_check(@email VARCHAR(1000))

AS

SELECT 1 exist
FROM CI_MIDDLEWAY..mw_cliente cli
WHERE lower(cli.cd_email_login)=lower(@email)