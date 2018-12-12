-- pr_presentation 107

ALTER PROCEDURE dbo.pr_presentation (@codPeca INT)

AS

-- DECLARE @codPeca INT = 1

SET NOCOUNT ON;

DECLARE @id_base INT
SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

DECLARE @weekday TABLE (id INT, [name] VARCHAR(100));

INSERT INTO @weekday (id, name) VALUES(1, 'dom')
INSERT INTO @weekday (id, name) VALUES(2, 'seg')
INSERT INTO @weekday (id, name) VALUES(3, 'ter')
INSERT INTO @weekday (id, name) VALUES(4, 'qua')
INSERT INTO @weekday (id, name) VALUES(5, 'qui')
INSERT INTO @weekday (id, name) VALUES(6, 'sex')
INSERT INTO @weekday (id, name) VALUES(7, 'sab')

SELECT
    DATEPART(dw, a.DatApresentacao) [weekday]
    ,(SELECT TOP 1 [name] FROM @weekday WHERE id = DATEPART(dw, a.DatApresentacao)) weekdayName
    ,RIGHT('00'+CONVERT(VARCHAR(2),DATEPART(dd, a.DatApresentacao)),2) + '/' + RIGHT('00' + CONVERT(VARCHAR(2),DATEPART(mm, a.DatApresentacao)),2) [day]
    ,CONVERT(VARCHAR(4),DATEPART(yyyy, a.DatApresentacao)) [year]
    ,p.NomPeca
    ,le.ds_local_evento
    ,m.ds_municipio
    ,est.ds_estado
    ,est.sg_estado
    ,a.CodApresentacao
    ,ap.id_apresentacao
    ,s.NomSala
    -- ,se.NomSetor
    ,a.ValPeca
    ,a.CodSala
    ,a.HorSessao
FROM tabApresentacao a
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_evento e ON e.id_base=@id_base AND e.CodPeca=p.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND ap.CodApresentacao=a.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
--INNER JOIN tabSetor se ON a.CodSala=se.codSala
LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
LEFT JOIN CI_MIDDLEWAY..mw_municipio m ON le.id_municipio=m.id_municipio
LEFT JOIN CI_MIDDLEWAY..mw_estado est ON m.id_estado=est.id_estado
WHERE a.CodPeca=@codPeca
AND DATEADD(MINUTE, p.TemDurPeca,(CONVERT(DATETIME,CONVERT(VARCHAR(10),a.DatApresentacao,121) + ' ' + a.HorSessao + ':00.000')))>=GETDATE()
ORDER BY a.DatApresentacao, a.HorSessao, a.ValPeca