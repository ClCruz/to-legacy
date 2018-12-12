-- pr_bases '8cc26a74-7e65-411e-b854-f7b281a46e01'
-- select * from ticketoffice_user

ALTER PROCEDURE pr_bases (@id_ticketoffice_user UNIQUEIDENTIFIER = NULL)
AS

SELECT
b.id_base
,b.ds_nome_base_sql
,b.ds_nome_teatro
FROM CI_MIDDLEWAY..mw_base b
LEFT JOIN CI_MIDDLEWAY..ticketoffice_user_base toub ON b.id_base=toub.id_base
WHERE b.in_ativo=1
AND (@id_ticketoffice_user IS NULL OR toub.id_ticketoffice_user=@id_ticketoffice_user)
ORDER BY b.ds_nome_teatro