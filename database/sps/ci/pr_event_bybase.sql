-- pr_event_bybase 3244
GO

ALTER PROCEDURE dbo.pr_event_bybase (@codPeca INT)

AS

-- DECLARE @codPeca INT = 3244

SET NOCOUNT ON;

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

DECLARE @valMin DECIMAL(18,2)
        ,@valMax DECIMAL(18,2)
        ,@valores VARCHAR(1000)

SELECT @valMin=MIN(a.ValPeca) FROM tabApresentacao a WHERE a.codPeca=@codPeca
SELECT @valMax=MAX(a.ValPeca) FROM tabApresentacao a WHERE a.codPeca=@codPeca

IF @valMax=@valMin
    SET @valores = 'Único - R$ ' + CONVERT(VARCHAR(20),@valMin)
ELSE 
    SET @valores = 'R$ ' + CONVERT(VARCHAR(20),@valMin) + ' a ' + 'R$ ' + CONVERT(VARCHAR(20),@valMax)

SELECT TOP 1
p.CodPeca
,p.NomPeca
,e.ds_evento
,p.CodTipPeca
,tp.TipPeca
,p.CenPeca
,le.ds_local_evento
,le.ds_googlemaps [address]
,b.ds_nome_teatro
,@valores valores
,e.id_evento
,eei.[description]
,eei.cardimage
,eei.cardbigimage
,SUBSTRING(
        (
            SELECT ','+subB.name + '|' + subB.img  AS [text()]
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
,e.id_base
,eei.meta_description
,eei.meta_keyword
,m.ds_municipio
,es.sg_estado
,(m.ds_municipio + '/' + es.sg_estado) badge_city_text
FROM tabPeca p
INNER JOIN CI_MIDDLEWAY..mw_evento e ON p.CodPeca=e.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
LEFT JOIN tabTipPeca tp ON p.CodTipPeca=tp.CodTipPeca
LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
LEFT JOIN CI_MIDDLEWAY..mw_municipio m ON le.id_municipio=m.id_municipio
LEFT JOIN CI_MIDDLEWAY..mw_estado es ON m.id_estado=es.id_estado
WHERE p.CodPeca=@codPeca


