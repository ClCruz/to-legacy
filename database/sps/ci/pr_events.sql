-- pr_events 147
go

CREATE PROCEDURE dbo.pr_events (@id INT = NULL)

AS

SET NOCOUNT ON;

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT DISTINCT
    p.CodPeca
    ,p.NomPeca
    ,p.ValIngresso
    ,p.in_vende_site
    ,(CONVERT(VARCHAR(10),p.DatIniPeca,103) + ' a ' + CONVERT(VARCHAR(10),p.DatFinPeca,103)) [days]
    ,p.TemDurPeca
    ,tp.TipPeca
    ,p.in_obriga_cpf needCPF
    ,p.in_obriga_rg needRG
    ,p.in_obriga_tel needPhone
    ,p.in_obriga_nome needName
    ,eei.cardimage
    ,e.id_evento
FROM tabPeca p
INNER JOIN tabApresentacao a ON p.CodPeca=a.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_evento e ON p.CodPeca=e.CodPeca AND e.id_base=@id_base
LEFT JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
LEFT JOIN tabTipPeca tp ON p.CodTipPeca=tp.CodTipPeca
WHERE p.StaPeca='A' AND a.StaAtivoBilheteria='S' 
AND (@id IS NULL OR p.CodPeca=@id)
AND DATEADD(MINUTE, p.TemDurPeca,(CONVERT(DATETIME,CONVERT(VARCHAR(10),a.DatApresentacao,121) + ' ' + a.HorSessao + ':00.000')))>=GETDATE()
--AND (@id IS NOT NULL OR CONVERT(DATETIME,CONVERT(VARCHAR(10),a.DatApresentacao,121) + ' ' + a.HorSessao + ':00.000')>=GETDATE())
