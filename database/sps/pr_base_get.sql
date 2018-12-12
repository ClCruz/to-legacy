CREATE PROCEDURE dbo.pr_base_get (@id_base INT)

AS

SELECT TOP 1 ds_nome_base_sql
FROM CI_MIDDLEWAY..mw_base
WHERE id_base=@id_base