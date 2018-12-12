--exec sp_executesql N'EXEC pr_login @P1',N'@P1 varchar(8000)','blc'
select token, dt_token_valid, cd_email_login from CI_MIDDLEWAY..mw_cliente where cd_email_login='blcoccaro@gmail.com'
update CI_MIDDLEWAY..mw_cliente set cd_password='ca9e88ecec91bedd4b6ffe0257663db9' where cd_email_login='blcoccaro@gmail.com'
GO

CREATE PROCEDURE dbo.pr_login_client(@email VARCHAR(1000))

AS

SELECT
cli.cd_cpf
,cli.cd_email_login email
,cli.id_cliente id
,cli.cd_password
,cli.cd_rg
,cli.ds_celular
,cli.ds_nome + ' ' + cli.ds_sobrenome [name]
,cli.dt_nascimento
,0 operator
FROM CI_MIDDLEWAY..mw_cliente cli
WHERE lower(cli.cd_email_login)=lower(@email)