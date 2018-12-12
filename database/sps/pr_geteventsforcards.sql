-- EXEC pr_geteventsforcards 'SANTOS', 'SAO PAULO', 'live_185e1621cf994a99ba945fe9692d4bf6d66ef03a1fcc47af8ac909dbcea53fb5'
-- GO

ALTER PROCEDURE dbo.pr_geteventsforcards (@city VARCHAR(100) = NULL,@state VARCHAR(100) = NULL, @api VARCHAR(100) = NULL)

AS

--DECLARE @city VARCHAR(100) = NULL,@state VARCHAR(100) = NULL, @api VARCHAR(100) = 'live_keykeykey'

SET NOCOUNT ON;

DECLARE @nowOrder DATETIME = DATEADD(day,15, GETDATE())
        ,@top INT = 50
        ,@id_partner UNIQUEIDENTIFIER

SELECT TOP 1 @id_partner=p.id FROM CI_MIDDLEWAY..[partner] p WHERE p.[key]=@api OR p.key_test=@api

SELECT top (@top)
h.id_evento
,h.ds_evento
,h.codPeca
,h.ds_nome_teatro
,h.ds_municipio
,h.ds_estado
,h.sg_estado
,h.ds_regiao_geografica
,(CASE WHEN h.ds_municipio = @city COLLATE Latin1_general_CI_AI THEN 1
                WHEN h.ds_municipio != @city COLLATE Latin1_general_CI_AI
                     AND h.sg_estado = @state COLLATE Latin1_general_CI_AI THEN 2
                WHEN min(ap.dt_apresentacao)<=@nowOrder THEN 3
                ELSE 4 END) orderhelper
,h.cardimage
,h.cardbigimage
,h.imageoriginal
,h.uri
,h.dates
,h.[badges]
,h.[promotion]
,eei.id_genre
,g.name genreName
FROM home h
INNER JOIN CI_MIDDLEWAY..mw_evento e ON h.id_evento=e.id_evento
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento
INNER JOIN CI_MIDDLEWAY..partner_database pd ON e.id_base=pd.id_base AND pd.id_partner=@id_partner
LEFT JOIN CI_MIDDLEWAY..genre g ON eei.id_genre=g.id
WHERE 
    DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE()
    AND e.in_ativo=1
    --AND ds_municipio = @city COLLATE Latin1_general_CI_AI
GROUP BY 
h.id_evento
,h.ds_evento
,h.codPeca
,h.ds_nome_teatro
,h.ds_municipio
,h.ds_estado
,h.sg_estado
,h.ds_regiao_geografica
,h.cardimage
,h.cardbigimage
,h.imageoriginal
,h.uri
,h.dates
,h.badges
,h.promotion
,eei.id_genre
,g.name

ORDER BY (CASE WHEN h.ds_municipio = @city COLLATE Latin1_general_CI_AI THEN 1
                WHEN h.ds_municipio != @city COLLATE Latin1_general_CI_AI
                     AND h.sg_estado = @state COLLATE Latin1_general_CI_AI THEN 2
                WHEN min(ap.dt_apresentacao)<=@nowOrder THEN 3
                ELSE 4 END)
