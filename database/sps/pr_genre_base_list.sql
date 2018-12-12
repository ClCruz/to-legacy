--dbo.pr_genre_base_list 'live_185e1621cf994a99ba945fe9692d4bf6d66ef03a1fcc47af8ac909dbcea53fb5'

ALTER PROCEDURE dbo.pr_genre_base_list (@api VARCHAR(100))

AS

SET NOCOUNT ON;

DECLARE @id_partner UNIQUEIDENTIFIER
SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api

DECLARE @lastId INT
SELECT @lastId = MAX(id) FROM CI_MIDDLEWAY..genre;

SELECT
b.id_base
,b.ds_nome_base_sql
,b.ds_nome_teatro
,(CASE WHEN gs.id_base IS NULL THEN 0 ELSE ( CASE WHEN gs.last_id <> @lastId THEN 0 ELSE 1 END ) END) active
FROM CI_MIDDLEWAY..mw_base b
LEFT JOIN CI_MIDDLEWAY..partner_database pdb ON b.id_base=pdb.id_base AND pdb.id_partner=@id_partner
LEFT JOIN CI_MIDDLEWAY..genre_sync gs ON b.id_base=gs.id_base
WHERE b.in_ativo=1