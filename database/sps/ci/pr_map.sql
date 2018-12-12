-- pr_map 166826

go
CREATE PROCEDURE dbo.pr_map (@id_apresentacao INT)

AS

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

SELECT DISTINCT 
p.CodPeca
,p.NomPeca
,a.CodApresentacao
,CONVERT(VARCHAR(10),a.DatApresentacao,103) DatApresentacao
,a.HorSessao
,ISNULL(s.FotoImagemSite,'{DEFAULT}') FotoImagemSite
,s.AlturaSite
,s.LarguraSite
,s.IngressoNumerado
,(SELECT COUNT(*) FROM tabLugSala sub WHERE sub.CodApresentacao=a.CodApresentacao) seatsPurchased
,(SELECT 
    COUNT(*) 
    FROM tabSala sub 
    INNER JOIN tabSalDetalhe subSd ON sub.CodSala=subSd.CodSala AND subSd.TipObjeto = 'C'
    WHERE sub.CodSala=a.CodSala) seatsTotal
INTO #result
FROM tabPeca p
INNER JOIN tabApresentacao a ON p.CodPeca=a.CodPeca
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON ap.CodApresentacao=a.CodApresentacao
INNER JOIN CI_MIDDLEWAY..mw_evento e ON ap.id_evento=e.id_evento
WHERE ap.id_apresentacao=@id_apresentacao

SELECT
r.CodPeca
,r.NomPeca
,r.CodApresentacao
,r.DatApresentacao
,r.HorSessao
,r.FotoImagemSite
,r.AlturaSite
,r.LarguraSite
,r.IngressoNumerado
,r.seatsPurchased
,r.seatsTotal
,(r.seatsTotal-r.seatsPurchased) seatsAvailable
,(CASE WHEN (r.seatsTotal-r.seatsPurchased) > 99 THEN 99 ELSE r.seatsTotal-r.seatsPurchased END) maxSeatsAvailableToBuy
FROM #result r