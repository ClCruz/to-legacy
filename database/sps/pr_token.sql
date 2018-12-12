--exec sp_executesql N'EXEC pr_login @P1',N'@P1 varchar(8000)','blc'
GO

ALTER PROCEDURE dbo.pr_token(@token VARCHAR(1000))

AS

SELECT
tou.active
,tou.email
,tou.id
,CONVERT(VARCHAR(10),tou.lastLogin,103) + ' ' + CONVERT(VARCHAR(8),tou.lastLogin,114) lastLogin
,tou.[login]
,tou.name
,tou.[password]
,CONVERT(VARCHAR(10),tou.tokenValidUntil,121) + ' ' + CONVERT(VARCHAR(8),tou.tokenValidUntil,114) tokenValidUntil
,(CASE WHEN tou.tokenValidUntil >= GETDATE() THEN 1 ELSE 0 END) isValid
,1 operator
FROM CI_MIDDLEWAY..ticketoffice_user tou
WHERE lower(tou.currentToken)=lower(@token)