ALTER PROCEDURE dbo.pr_ticketoffice_user_base_list (@id UNIQUEIDENTIFIER)

AS

-- select * from CI_MIDDLEWAY..ticketoffice_user_base
--  delete from CI_MIDDLEWAY..ticketoffice_user_base where id_ticketoffice_user='93b93f5d-133a-4464-a57f-3c532bb33c59'
-- delete from CI_TIXSME..tabCaixa where CodCaixa=2
-- delete from CI_TIXSME..tabUsuario where CodUsuario=3
-- DECLARE @id UNIQUEIDENTIFIER

SELECT
b.id_base
,b.ds_nome_base_sql
,b.ds_nome_teatro
,(CASE WHEN pdb.id IS NULL THEN 0 ELSE pdb.active END) active
FROM CI_MIDDLEWAY..mw_base b
LEFT JOIN CI_MIDDLEWAY..ticketoffice_user_base pdb ON b.id_base=pdb.id_base AND pdb.id_ticketoffice_user=@id
WHERE b.in_ativo=1