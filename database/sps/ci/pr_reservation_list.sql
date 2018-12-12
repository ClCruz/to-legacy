ALTER PROCEDURE dbo.pr_reservation_list (@nin VARCHAR(14) = NULL, @codReserva VARCHAR(10) = NULL, @id_apresentacao INT = NULL)

AS

-- DECLARE @nin VARCHAR(14) = NULL
--         ,@codReserva VARCHAR(10) = NULL

-- SELECT @nin='53625487861'
--         ,@codReserva=NULL--'RJ64ODHBDF'

SET NOCOUNT ON;

IF (@nin = '')
    SET @nin=NULL
ELSE
    SET @nin = REPLACE(REPLACE(@nin,'-',''),'.','')

IF (@codReserva = '')
    SET @codReserva=NULL

IF (@id_apresentacao = '')
    SET @id_apresentacao=NULL

DECLARE @weekday TABLE (id INT, [name] VARCHAR(100));

INSERT INTO @weekday (id, name) VALUES(1, 'dom')
INSERT INTO @weekday (id, name) VALUES(2, 'seg')
INSERT INTO @weekday (id, name) VALUES(3, 'ter')
INSERT INTO @weekday (id, name) VALUES(4, 'qua')
INSERT INTO @weekday (id, name) VALUES(5, 'qui')
INSERT INTO @weekday (id, name) VALUES(6, 'sex')
INSERT INTO @weekday (id, name) VALUES(7, 'sab')

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

SELECT
ls.Indice
,ls.CodReserva
,ls.StaCadeira
,c.Nome
,c.CPF
,c.RG
,c.DDD
,c.Telefone
,c.Ramal
,c.EMail
,sd.NomObjeto
,s.NomSala
,a.DatApresentacao
,a.HorSessao
,p.NomPeca
,le.ds_local_evento
,(SELECT TOP 1 [name] FROM @weekday WHERE id = DATEPART(dw, a.DatApresentacao)) weekdayName
,CONVERT(VARCHAR(2),DATEPART(dd, a.DatApresentacao)) + '/' + CONVERT(VARCHAR(2),DATEPART(mm, a.DatApresentacao)) [day]
,CONVERT(VARCHAR(4),DATEPART(yyyy, a.DatApresentacao)) [year]
,a.CodApresentacao
,e.id_evento
,ap.id_apresentacao
FROM tabLugSala ls
INNER JOIN tabResCliente rc ON ls.Indice=rc.Indice AND ls.CodReserva=rc.CodReserva
INNER JOIN tabCliente c ON rc.CodCliente=c.Codigo
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSalDetalhe sd ON s.CodSala=sd.CodSala AND ls.Indice=sd.Indice
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_evento e ON p.CodPeca=e.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND ap.CodApresentacao=ls.CodApresentacao
WHERE 
(@nin IS NULL OR c.CPF=@nin)
AND (@codReserva IS NULL OR ls.CodReserva=@codReserva)
AND (@id_apresentacao IS NULL OR ap.id_apresentacao=@id_apresentacao)
AND ls.StaCadeira='R'
ORDER BY c.Nome, p.NomPeca, s.NomSala, sd.NomObjeto