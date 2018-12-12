-- exec pr_search 'sao', @startAt=0
-- select * from search where id_evento=8157
-- GO

CREATE PROCEDURE dbo.pr_geteventbyid(@id INT)

AS

SET NOCOUNT ON;

SELECT DISTINCT
    e.id_evento
    ,RTRIM(LTRIM(e.ds_evento)) ds_evento
    ,eei.cardimage
    ,eei.cardbigimage
    ,eei.[description]
    ,eei.uri
    ,b.ds_nome_teatro
    ,mu.ds_municipio
    ,es.ds_estado
    ,es.sg_estado
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento
INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
INNER JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
INNER JOIN CI_MIDDLEWAY..mw_municipio mu ON le.id_municipio=mu.id_municipio
INNER JOIN CI_MIDDLEWAY..mw_estado es ON mu.id_estado=es.id_estado
WHERE e.id_evento=@id

SET NOCOUNT OFF;