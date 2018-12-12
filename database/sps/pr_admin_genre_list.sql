CREATE PROCEDURE dbo.pr_admin_genre_list

AS

SELECT
id
,[name]
FROM CI_MIDDLEWAY..genre
WHERE active=1
ORDER BY [name]