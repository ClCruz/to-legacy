
ALTER PROCEDURE dbo.pr_seat_reservation_cancel (@codReseva VARCHAR(10), @Indice INT = NULL)

AS

SET NOCOUNT ON;


DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

IF OBJECT_ID('tempdb.dbo.#indice', 'U') IS NOT NULL
    DROP TABLE #indice; 

IF OBJECT_ID('tempdb.dbo.#helper', 'U') IS NOT NULL
    DROP TABLE #helper; 

CREATE TABLE #indice (indice int);

CREATE TABLE #helper (id_apresentacao INT, id_evento INT, CodApresentacao INT, CodPeca INT, Indice INT, CodReserva VARCHAR(10));

IF @Indice IS NULL OR @Indice = 0
BEGIN
    INSERT INTO #indice (indice)
        SELECT ls.Indice
        FROM tabLugSala ls
        WHERE ls.CodReserva=@codReseva COLLATE SQL_Latin1_General_Cp1251_CS_AS AND StaCadeira='R'
END
ELSE
BEGIN
    INSERT INTO #indice (indice)
        SELECT @Indice
END


INSERT INTO #helper (id_apresentacao, id_evento, CodApresentacao, CodPeca, Indice, CodReserva)
SELECT ap.id_apresentacao, ap.id_evento, a.CodApresentacao, a.CodPeca, ls.indice, ls.CodReserva
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_evento e ON p.CodPeca=e.CodPeca AND e.id_base=@id_base
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON e.id_evento=ap.id_evento AND a.CodApresentacao=ap.CodApresentacao
WHERE
    ls.Indice IN (SELECT indice FROM #indice)
    AND ls.StaCadeira='R'

DELETE d
FROM CI_MIDDLEWAY..mw_reserva d
INNER JOIN #helper h ON d.id_apresentacao=h.id_apresentacao AND d.id_cadeira=h.Indice

DELETE d
FROM tabLugSala d
INNER JOIN #helper h ON d.CodApresentacao=h.CodApresentacao AND d.CodReserva=h.CodReserva COLLATE SQL_Latin1_General_Cp1251_CS_AS AND d.Indice=h.Indice AND d.StaCadeira='R'

SELECT 1 success