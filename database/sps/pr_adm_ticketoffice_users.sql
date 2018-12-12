
ALTER PROCEDURE dbo.pr_adm_ticketoffice_users (@api VARCHAR(100) = NULL)

AS

SET NOCOUNT ON;

DECLARE @id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api

SELECT
    tou.id
    ,tou.name
    ,tou.[login]
    ,tou.email
    ,tou.active
    ,CONVERT(VARCHAR(10),tou.created,103) + ' ' + CONVERT(VARCHAR(8),tou.created,114) [created]
    ,CONVERT(VARCHAR(10),tou.updated,103) + ' ' + CONVERT(VARCHAR(8),tou.updated,114) [updated]
FROM CI_MIDDLEWAY..ticketoffice_user tou
ORDER by tou.name