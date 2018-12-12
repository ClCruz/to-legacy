CREATE PROCEDURE dbo.pr_seat_reservation_only (@id_apresentacao INT, @indice INT, @id VARCHAR(100), @codCliente INT, @codReserva VARCHAR(10))

AS

SET NOCOUNT ON;
-- DECLARE @codPeca INT, @id_apresentacao INT, @indice INT, @id VARCHAR(100), @NIN VARCHAR(10), @minutesToExpire INT

-- SELECT
--     @codPeca=145
--     ,@id_apresentacao=166789
--     ,@indice=80847
--     ,@id='teste'


DECLARE @id_base INT
        ,@id_session VARCHAR(32) = replace(@id,'-','')

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

DECLARE @seatTaken BIT = 0
        ,@seatTakenByPackage BIT = 0
        ,@seatTakenTemp BIT = 0
        ,@seatTakenReserved BIT = 0
        ,@seatTakenBySite BIT = 0
        ,@limitedByPurchase BIT = 0
        ,@limitedByNIN BIT = 0

DECLARE @codError_seatTaken INT = 1
        ,@codError_seatTakenByPackage INT = 2
        ,@codError_seatTakenByReservation INT = 3
        ,@codError_seatTakenByTemp INT = 4
        ,@codError_seatTakenBySite INT = 5
        ,@codError_limitedByPurchase INT = 6
        ,@codError_limitedByNIN INT = 7
        ,@codError_Fail INT = 10

---------------------------------- Check if seat has been taken
SELECT @seatTaken=1 
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON a.CodApresentacao=ap.CodApresentacao
WHERE 
    ap.id_apresentacao=@id_apresentacao AND ls.Indice=@indice AND ls.StaCadeira='V'

IF @seatTaken = 1
BEGIN
    SELECT
        1 as error
        ,'seattaken' info
        ,@codError_seatTaken code
    RETURN;
END
---------------------------------- Check if seat has been taken reserved
SELECT @seatTakenReserved=1 
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON a.CodApresentacao=ap.CodApresentacao
WHERE 
    ap.id_apresentacao=@id_apresentacao AND ls.Indice=@indice AND ls.StaCadeira='R'

IF @seatTakenReserved = 1
BEGIN
    SELECT
        1 as error
        ,'seattaken' info
        ,@codError_seatTakenByReservation code
    RETURN;
END
---------------------------------- Check if seat has been taken reserved
SELECT @seatTakenTemp=1 
FROM tabLugSala ls
INNER JOIN tabApresentacao a ON ls.CodApresentacao=a.CodApresentacao
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON a.CodApresentacao=ap.CodApresentacao
WHERE 
    ap.id_apresentacao=@id_apresentacao AND ls.Indice=@indice AND ls.StaCadeira='T'

IF @seatTakenTemp = 1
BEGIN
    SELECT
        1 as error
        ,'seattaken' info
        ,@codError_seatTakenByTemp code
    RETURN;
END

---------------------------------- Check if seat has been taken by site
SELECT @seatTakenBySite=1 
FROM CI_MIDDLEWAY..mw_reserva r
WHERE r.id_apresentacao=@id_apresentacao AND r.id_cadeira=@indice

IF @seatTakenBySite = 1
BEGIN
    SELECT
        1 as error
        ,'seattaken' info
        ,@codError_seatTakenBySite code
    RETURN;
END


---------------------------------- Check if seat has been taken
SELECT @seatTakenByPackage=1 
FROM CI_MIDDLEWAY..mw_pacote_reserva r
INNER JOIN CI_MIDDLEWAY..mw_pacote_apresentacao a ON r.id_pacote=a.id_pacote
WHERE a.id_apresentacao=@id_apresentacao AND r.id_cadeira=@indice

IF @seatTakenByPackage = 1
BEGIN
    SELECT
        1 as error
        ,'seattaken' info
        ,@codError_seatTakenByPackage code
    RETURN;
END

DECLARE @reserved INT = 1
        ,@totalByPurchase INT = 0

SELECT TOP 1
    @totalByPurchase=e.qt_ingr_por_pedido
FROM tabApresentacao a
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON a.CodApresentacao=ap.CodApresentacao
INNER JOIN CI_MIDDLEWAY..mw_evento e ON ap.id_evento=e.id_evento
WHERE ap.id_apresentacao=@id_apresentacao

SELECT 
    @reserved=COUNT(*)
FROM CI_MIDDLEWAY..mw_reserva r
WHERE r.id_apresentacao=@id_apresentacao AND r.id_session=@id AND r.id_cadeira <> @indice

IF @reserved>@totalByPurchase
BEGIN
    SELECT
        1 as error
        ,'purchase' info
        ,@codError_limitedByPurchase code
    RETURN;
END

IF @NIN IS NOT NULL
BEGIN
    DECLARE @reservedCPF INT = 1
            ,@purchasedCPF INT = 0
            ,@totalByCPF INT = 0
    SELECT 
        @purchasedCPF=COUNT(*)
        ,@totalByCPF=ISNULL(MAX(ISNULL(p.qt_ingressos_por_cpf,0)),0)
    FROM tabCliente c
    INNER JOIN tabHisCliente hc ON c.Codigo=hc.Codigo
    INNER JOIN tabApresentacao a ON hc.CodApresentacao=a.CodApresentacao
    INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON a.CodApresentacao=ap.CodApresentacao
    INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
    WHERE ap.id_apresentacao=@id_apresentacao AND c.CPF=@NIN

    IF @totalByCPF > 0 AND @purchasedCPF>=@totalByCPF
    BEGIN
        SELECT
            1 as error
            ,'cpf' info
            ,@codError_limitedByNIN code
        RETURN;
    END
    
END

DECLARE @ds_setor VARCHAR(100)
        ,@ds_cadeira VARCHAR(100)
        ,@codApresentacao INT

SELECT
    @ds_cadeira=sd.NomObjeto
    ,@ds_setor=s.NomSala
    ,@codApresentacao=a.CodApresentacao
FROM tabSalDetalhe sd
INNER JOIN tabApresentacao a ON sd.CodSala=a.CodSala
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON a.CodApresentacao=ap.CodApresentacao
INNER JOIN tabSala s ON sd.CodSala=s.CodSala
WHERE ap.id_apresentacao=@id_apresentacao AND sd.Indice=@indice

DECLARE @codCaixa INT
        ,@codUsuario INT

SELECT
    @codCaixa=tub.codCaixa
    ,@codUsuario=tub.codUsuario
FROM CI_MIDDLEWAY..ticketoffice_user_base tub
WHERE tub.id_ticketoffice_user=@id
AND tub.id_base=@id_base

IF @codCliente IS NULL
BEGIN
    INSERT INTO CI_MIDDLEWAY..mw_reserva (ID_APRESENTACAO,ID_CADEIRA,DS_CADEIRA,DS_SETOR,ID_SESSION,DT_VALIDADE) 
    VALUES (@id_apresentacao,@indice,@ds_cadeira,@ds_setor,@id_session,DATEADD(MI, @minutesToExpire, GETDATE()))
END

INSERT INTO TABLUGSALA (CODAPRESENTACAO,INDICE,CODTIPBILHETE,CODCAIXA,CODVENDA,STAIMPRESSAO,STACADEIRA,CODUSUARIO,CODRESERVA,ID_SESSION)
VALUES (@codApresentacao, @indice, NULL,@codCaixa,NULL, 0, 'T', @codUsuario, NULL, @id_session)

IF @codCliente IS NOT NULL
BEGIN
    INSERT INTO tabResCliente (codCliente,CodREserva,Indice,TipLancamento)
        SELECT @CodCliente ,@CodReserva , Indice, 1  FROM tablugsala WHERE CodCaixa = @CodCaixa and (stacadeira = 'T' or stacadeira = 'M')

    UPDATE tabLugSala 
    SET	StaCadeira = 'R', 
        CodUsuario = @CodUsuario ,
        CodReserva = @CodReserva
    WHERE (CodCaixa = @CodCaixa) AND (StaCadeira = 'T' OR StaCadeira = 'M')
END

SELECT
    0 error
    ,'' info
    ,NULL code