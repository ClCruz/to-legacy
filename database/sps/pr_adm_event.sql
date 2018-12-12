-- select top 100 * from CI_MIDDLEWAY..mw_evento_extrainfo order by id_evento desc
-- pr_adm_event 22666, 'live_keykeykey'
-- select * from mw_evento_extrainfo where uri='/evento/humor_stand_up_comedy_guarulhos'
-- select * from search where id_evento=22039
-- select * from mw_evento_extrainfo where id_evento>22600

go

ALTER PROCEDURE dbo.pr_adm_event (@id_evento INT, @api VARCHAR(100))

AS

SET NOCOUNT ON;

DECLARE @id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api

DECLARE @uri VARCHAR(1000) = NULL

SELECT @uri=eei.uri FROM CI_MIDDLEWAY..mw_evento_extrainfo eei WHERE eei.id_evento=@id_evento

IF @uri IS NULL OR @uri = ''
BEGIN
    SELECT @uri = '/evento/' + replace(replace(lower(dbo.RemoveSpecialChars(e.ds_evento collate SQL_Latin1_General_Cp1251_CS_AS)),'-',''),' ', '_')
        + '_' + replace(replace(lower(dbo.RemoveSpecialChars((CASE WHEN le.id_local_evento IS NULL THEN b.ds_nome_teatro ELSE le.ds_local_evento END) collate SQL_Latin1_General_Cp1251_CS_AS)),'-',''),' ', '_')
        + '_' + CONVERT(VARCHAR(10),e.id_evento)
    FROM CI_MIDDLEWAY..mw_evento e
    INNER JOIN CI_MIDDLEWAY..mw_base b on e.id_base=b.id_base
    LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
    WHERE e.id_evento=@id_evento

    UPDATE CI_MIDDLEWAY..mw_evento_extrainfo SET uri=@uri WHERE id_evento=@id_evento;
END

SELECT TOP 1
e.id_evento
,e.ds_evento
,e.CodPeca
,(CASE WHEN eei.id_evento IS NULL THEN 1 ELSE 0 END) needed
,le.ds_googlemaps [address]
,eei.[description]
,eei.uri
,eei.ticketsPerPurchase
,eei.minuteBefore
,'' [image]
,eei.cardimage
,eei.meta_description
,eei.meta_keyword
,eei.id_genre
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..partner_database pdb ON e.id_base=pdb.id_base
LEFT JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
WHERE pdb.id_partner=@id_partner
AND e.id_evento=@id_evento
ORDER by e.ds_evento