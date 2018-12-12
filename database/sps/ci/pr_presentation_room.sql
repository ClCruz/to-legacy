-- pr_presentation_room 1


ALTER PROCEDURE dbo.pr_presentation_room (@codPeca INT)

AS

-- DECLARE @codPeca INT = 147

SET NOCOUNT ON;

DECLARE @id_base INT
SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT DISTINCT
    s.NomSala
    -- ,se.NomSetor
    ,a.CodSala
FROM tabApresentacao a
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_evento e ON e.id_base=@id_base AND e.CodPeca=p.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND ap.CodApresentacao=a.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSetor se ON a.CodSala=se.codSala
WHERE a.CodPeca=@codPeca
AND DATEADD(MINUTE, p.TemDurPeca,(CONVERT(DATETIME,CONVERT(VARCHAR(10),a.DatApresentacao,121) + ' ' + a.HorSessao + ':00.000')))>=GETDATE()
GROUP BY s.NomSala, se.NomSetor,a.CodSala
ORDER BY s.NomSala