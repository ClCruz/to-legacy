ALTER PROCEDURE dbo.pr_partner (@id UNIQUEIDENTIFIER)

AS

SELECT
id
,CONVERT(VARCHAR(10),created,103) + ' ' + CONVERT(VARCHAR(8),created,114) created
,[key]
,key_test
,[name]
,active
,CONVERT(VARCHAR(10),dateStart,103) dateStart
,CONVERT(VARCHAR(10),dateEnd,103) dateEnd
,domain
FROM [partner]
WHERE id=@id