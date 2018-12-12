--pr_print_ticket 'MWRKVIEABA'
GO
ALTER PROCEDURE dbo.pr_print_reservation(@codReserva VARCHAR(10)
        ,@indice INT = NULL)

AS

SET NOCOUNT ON;

IF @indice=''
    SET @indice=NULL

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

DECLARE @weekday TABLE (id INT, [name] VARCHAR(100), [full] VARCHAR(100));

INSERT INTO @weekday (id, [name],[full]) VALUES(1, 'dom', 'domingo')
INSERT INTO @weekday (id, [name],[full]) VALUES(2, 'seg', 'segunda-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(3, 'ter', 'terça-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(4, 'qua', 'quarta-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(5, 'qui', 'quinta-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(6, 'sex', 'sexta-feira')
INSERT INTO @weekday (id, [name],[full]) VALUES(7, 'sab', 'sábado')

DECLARE @month TABLE (id INT, [name] VARCHAR(100), [full] VARCHAR(100));

INSERT INTO @month (id,[name],[full]) VALUES(1, 'jan', 'janeiro')
INSERT INTO @month (id,[name],[full]) VALUES(2, 'fev', 'fevereiro')
INSERT INTO @month (id,[name],[full]) VALUES(3, 'mar', 'março')
INSERT INTO @month (id,[name],[full]) VALUES(4, 'abr', 'abril')
INSERT INTO @month (id,[name],[full]) VALUES(5, 'mai', 'maio')
INSERT INTO @month (id,[name],[full]) VALUES(6, 'jun', 'junho')
INSERT INTO @month (id,[name],[full]) VALUES(7, 'jul', 'julho')
INSERT INTO @month (id,[name],[full]) VALUES(8, 'ago', 'agosto')
INSERT INTO @month (id,[name],[full]) VALUES(9, 'set', 'setembro')
INSERT INTO @month (id,[name],[full]) VALUES(10, 'out', 'outubro')
INSERT INTO @month (id,[name],[full]) VALUES(11, 'nov', 'novembro')
INSERT INTO @month (id,[name],[full]) VALUES(12, 'dez', 'dezembro')

DECLARE @id_base INT
SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

DECLARE @now DATETIME = GETDATE()

SELECT
ls.Indice seatIndice
,le.ds_local_evento [local]
,le.ds_googlemaps [address]
,e.ds_evento [name]
,(SELECT TOP 1 [name] FROM @weekday WHERE id = DATEPART(dw, a.DatApresentacao)) [weekday]
,(SELECT TOP 1 [full] FROM @weekday WHERE id = DATEPART(dw, a.DatApresentacao)) [weekdayName]
,a.HorSessao [hour]
,(SELECT TOP 1 [name] FROM @month WHERE id = DATEPART(m, a.DatApresentacao)) [month]
,(SELECT TOP 1 [full] FROM @month WHERE id = DATEPART(m, a.DatApresentacao)) [monthName]
,DATEPART(d, a.DatApresentacao) [day]
,DATEPART(yyyy, a.DatApresentacao) [year]
,s.NomSala [roomName]
,s.NomRedSala roomNameOther
,sd.NomObjeto seatNameFull
,SUBSTRING(sd.NomObjeto, 0,CHARINDEX('-', sd.NomObjeto)) [seatRow]
,SUBSTRING(sd.NomObjeto, CHARINDEX('-', sd.NomObjeto) + 1, LEN(sd.NomObjeto)) [seatName]
,ls.CodReserva reservationCode
,(CASE WHEN c.Nome IS NULL THEN '-' ELSE c.Nome END) buyer
,(CASE WHEN c.CPF IS NULL THEN '-' ELSE c.CPF END) buyerDoc
,(CASE WHEN eei.insurance_policy IS NULL THEN '' ELSE eei.insurance_policy END) insurance_policy
,(CASE WHEN eei.opening_time IS NULL THEN '' ELSE eei.opening_time END) opening_time
,p.NomResPeca eventResp
,(CASE WHEN tou.[login] IS NULL THEN 'web' ELSE tou.[login] END) [user]
,ROW_NUMBER() OVER (order by ls.Indice) countTicket
,CONVERT(VARCHAR(10),@now,103) + ' ' + CONVERT(VARCHAR(8),@now,114) AS print_date
,(CONCAT('(',c.DDD,')',c.Telefone,(CASE WHEN c.Ramal IS NOT NULL AND c.Ramal != '' THEN ' - ' ELSE '' END),ISNULL(c.Ramal,''))) phone
INTO #result
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN tabSala s ON a.CodSala=s.CodSala
INNER JOIN tabSalDetalhe sd ON ls.Indice=sd.Indice AND a.CodSala=sd.CodSala
INNER JOIN CI_MIDDLEWAY..mw_evento e ON p.CodPeca=e.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND ap.CodApresentacao=a.CodApresentacao
INNER JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
INNER JOIN tabResCliente rc ON ls.Indice=rc.Indice AND ls.CodReserva=rc.CodReserva
INNER JOIN CI_MIDDLEWAY..ticketoffice_user tou ON CAST(SUBSTRING(ls.id_session, 1, 8) + '-' + SUBSTRING(ls.id_session, 9, 4) + '-' + SUBSTRING(ls.id_session, 13, 4) + '-' + SUBSTRING(ls.id_session, 17, 4) + '-' + SUBSTRING(ls.id_session, 21, 12) AS UNIQUEIDENTIFIER)=tou.id
INNER JOIN tabCliente c ON rc.CodCliente=c.Codigo
INNER JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
WHERE ls.CodReserva=@codReserva AND ls.StaCadeira='R'
AND (@indice IS NULL OR ls.Indice=@indice)

SELECT
[local]
,[address]
,[name]
,[weekday]
,[weekdayName]
,[hour]
,[month]
,[monthName]
,[day]
,[year]
,[roomName]
,[roomNameOther]
,[seatNameFull]
,[seatRow]
,[seatName]
,[seatIndice]
,[reservationCode]
,[buyer]
,[buyerDoc]
,[insurance_policy]
,[opening_time]
,[eventResp]
,[user]
,[countTicket]
,[print_date]
,[phone]
,CONVERT(VARCHAR(10),countTicket) + '/' + CONVERT(VARCHAR(10),(SELECT MAX(countTicket) FROM #result)) [howMany]
FROM #result