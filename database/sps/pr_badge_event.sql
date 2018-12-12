CREATE PROCEDURE dbo.pr_badge_event (@id_evento INT)

AS

SELECT
b.id
,b.name
,b.img
FROM CI_MIDDLEWAY..mw_evento_badge eb
INNER JOIN CI_MIDDLEWAY..badge b ON eb.id_badge=b.id
WHERE eb.id_evento=@id_evento