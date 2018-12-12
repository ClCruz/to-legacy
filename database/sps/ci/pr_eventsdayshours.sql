-- pr_eventsdayshours 147, '31/10/2018'
-- GO

-- select CONVERT(DECIMAL(19,2),CONVERT(DECIMAL(19,4),50)-CONVERT(DECIMAL(19,4),50)* (CONVERT(DECIMAL(19,4),75)/100))
-- GO

CREATE PROCEDURE dbo.pr_eventsdayshours (@codPeca INT, @datePresentation VARCHAR(10))

AS

-- DECLARE @codPeca INT, @datePresentation VARCHAR(10)

-- SELECT @codPeca=146, @datePresentation='24/10/2018'

SET NOCOUNT ON;

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT DISTINCT
    a.codApresentacao
    ,ap.id_apresentacao
    ,CONVERT(VARCHAR(10),a.DatApresentacao,103) DatApresentacao
    ,a.HorSessao
    ,s.NomSala
    ,s.NomRedSala
    ,s.IngressoNumerado
    ,a.ValPeca
    ,se.PerDesconto
    ,CONVERT(DECIMAL(19,2),(CONVERT(DECIMAL(19,4),a.ValPeca)-(CONVERT(DECIMAL(19,4),a.ValPeca)*(CONVERT(DECIMAL(19,4),se.PerDesconto/100))))) cost
FROM tabPeca p
INNER JOIN tabApresentacao a ON p.CodPeca=a.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_evento e ON e.CodPeca=p.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND a.CodApresentacao=ap.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSetor se ON s.CodSala=se.CodSala
WHERE p.CodPeca=@codPeca
AND CONVERT(VARCHAR(10),a.DatApresentacao,103)=@datePresentation
AND DATEADD(MINUTE, p.TemDurPeca,(CONVERT(DATETIME,CONVERT(VARCHAR(10),a.DatApresentacao,121) + ' ' + a.HorSessao + ':00.000')))>=GETDATE()
ORDER BY a.HorSessao
