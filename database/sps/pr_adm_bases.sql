ALTER PROCEDURE dbo.pr_adm_bases (@api VARCHAR(100))

AS

SET NOCOUNT ON;

DECLARE @id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api


SELECT
b.id_base
,b.ds_nome_base_sql
,b.ds_nome_teatro
FROM CI_MIDDLEWAY..mw_base b
INNER JOIN CI_MIDDLEWAY..partner_database pdb ON b.id_base=pdb.id_base
WHERE b.in_ativo=1
AND pdb.id_partner=@id_partner
ORDER by ds_nome_teatro