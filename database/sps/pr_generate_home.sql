-- EXEC pr_geteventsforcards 'SANTOS', 'SAO PAULO', 'live_keykeykey'
-- GO
-- select * from home
go
ALTER PROCEDURE dbo.pr_generate_home

AS

-- DECLARE @city VARCHAR(100) = NULL,@state VARCHAR(100) = NULL, @api VARCHAR(100) = 'live_keykeykey'

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#toAdd', 'U') IS NOT NULL
    DROP TABLE #toAdd; 

IF OBJECT_ID('tempdb.dbo.#dont', 'U') IS NOT NULL
    DROP TABLE #dont; 

SELECT
    id_evento
into #dont
FROM home where outofdate=0

SELECT
e.id_evento
,e.ds_evento
,e.codPeca
,le.ds_local_evento ds_nome_teatro
,mu.ds_municipio
,es.ds_estado
,es.sg_estado
,regi.ds_regiao_geografica
,eei.cardimage
,eei.cardbigimage
,eei.imageoriginal
,eei.uri
,(CASE WHEN convert(varchar(5), MIN(ap.dt_apresentacao),103) = convert(varchar(5), MAX(ap.dt_apresentacao),103) THEN convert(varchar(5), MIN(ap.dt_apresentacao),103) ELSE  convert(varchar(5), MIN(ap.dt_apresentacao),103) + ' - ' + convert(varchar(5), max(ap.dt_apresentacao),103) END) datas
,SUBSTRING(
        (
            SELECT ',' + subB.name + '|' + subB.img  AS [text()]
            FROM CI_MIDDLEWAY..mw_evento_badge subEB
            INNER JOIN CI_MIDDLEWAY..badge subB ON subEB.id_badge=subB.id
            WHERE subEB.id_evento=e.id_evento
            ORDER BY subEB.showOrder
            FOR XML PATH ('')
        ), 2, 4000) [badges]
,SUBSTRING(
        (
            SELECT ','+subpc.ds_promocao + '|' + subpa.ds_NomPatrocinador + '|' + subpc.Imag1Promocao + '|' + subpc.Imag2Promocao  AS [text()]
            FROM CI_MIDDLEWAY..mw_controle_evento subce
            LEFT JOIN CI_MIDDLEWAY..mw_promocao_controle subpc ON subce.id_promocao_controle=subpc.id_promocao_controle
            LEFT JOIN CI_MIDDLEWAY..mw_patrocinador subpa ON subpc.id_patrocinador=subpa.id_Patrocinador
            WHERE subce.id_evento=e.id_evento
            AND subpc.dt_inicio_promocao<=GETDATE()
            AND subpc.dt_fim_promocao>=GETDATE()
            FOR XML PATH ('')
        ), 2, 8000) [promotion]
INTO #toAdd
FROM CI_MIDDLEWAY..mw_evento e
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento
INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
INNER JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
INNER JOIN CI_MIDDLEWAY..mw_municipio mu ON le.id_municipio=mu.id_municipio
INNER JOIN CI_MIDDLEWAY..mw_estado es ON mu.id_estado=es.id_estado
LEFT JOIN CI_MIDDLEWAY..mw_regiao_geografica regi ON es.id_regiao_geografica=regi.id_regiao_geografica
LEFT JOIN #dont dt ON e.id_evento=dt.id_evento
WHERE 
    DATEADD(minute, ((eei.minuteBefore)*-1), CONVERT(VARCHAR(10),ap.dt_apresentacao,121) + ' ' + REPLACE(ap.hr_apresentacao, 'h', ':') + ':00.000')>=GETDATE()
    AND e.in_ativo=1
    AND b.in_ativo=1
    AND dt.id_evento IS NULL
    --AND ds_municipio = @city COLLATE Latin1_general_CI_AI
GROUP BY 
e.id_evento
,e.ds_evento
,e.codPeca
,le.ds_local_evento
,mu.ds_municipio
,es.ds_estado
,es.sg_estado
,regi.ds_regiao_geografica
,eei.cardimage
,eei.cardbigimage
,eei.imageoriginal
,eei.uri

DELETE d
FROM home d
INNER JOIN #toAdd tadd ON d.id_evento=tadd.id_evento


INSERT INTO home (outofdate,[id_evento],[ds_evento],[codPeca],[ds_nome_teatro],[ds_municipio],[ds_estado],[sg_estado],[ds_regiao_geografica],[cardimage],[cardbigimage],[imageoriginal],[uri],[dates],[badges],[promotion])
SELECT
0
,id_evento
,ds_evento
,codPeca
,ds_nome_teatro
,ds_municipio
,ds_estado
,sg_estado
,ds_regiao_geografica
,cardimage
,cardbigimage
,imageoriginal
,uri
,datas
,badges
,[promotion]
FROM #toAdd

DELETE FROM home WHERE outofdate=1;