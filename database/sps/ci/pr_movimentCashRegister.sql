--pr_movimentCashRegister '8cc26a74-7e65-411e-b854-f7b281a46e01', '2018-11-01', 1

ALTER PROCEDURE dbo.pr_movimentCashRegister (@id_ticketoffice_user UNIQUEIDENTIFIER
        ,@date DATETIME
        ,@CodMovimento INT)

AS

-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER
--         ,@date DATETIME
--         ,@CodMovimento INT

-- SELECT @id_ticketoffice_user='8cc26a74-7e65-411e-b854-f7b281a46e01'
--     ,@date = '2018-11-01'
--     ,@CodMovimento=1

SET NOCOUNT ON;

DECLARE @id_base INT

SELECT @id_base=id_base FROM CI_MIDDLEWAY..mw_base where ds_nome_base_sql=DB_NAME()

IF OBJECT_ID('tempdb.dbo.#result', 'U') IS NOT NULL
    DROP TABLE #result; 

CREATE TABLE #result (CodCaixa INT,CodMovimento INT,DatApresentacao DATETIME,DatHorApresentacao VARCHAR(50),DatMovimento DATETIME,HorSessao VARCHAR(50),IdOperacao INT,NomPeca VARCHAR(1000),Operacao VARCHAR(100),Qtde INT, Tipo VARCHAR(1000), TipSaque VARCHAR(1000), Valor DECIMAL(18,2))

INSERT INTO #result (CodCaixa,CodMovimento,DatApresentacao,DatHorApresentacao,DatMovimento,HorSessao,IdOperacao,NomPeca,Operacao,Qtde, Tipo, TipSaque, Valor)
SELECT 
v.CodCaixa
,v.CodMovimento
,v.DatApresentacao
,v.DatHorApresentacao
,v.DatMovimento
,v.HorSessao
,v.IdOperacao
,v.NomPeca
,v.Operacao
,v.Qtde
,v.Tipo
,v.TipSaque
,v.Valor
FROM VW_MOV001 v 
INNER JOIN CI_MIDDLEWAY.dbo.ticketoffice_user_base toub ON v.CodCaixa=toub.codCaixa
WHERE toub.id_base=@id_base
AND toub.id_ticketoffice_user=@id_ticketoffice_user
AND DATEADD(dd, DATEDIFF(dd, 0, v.DatMovimento), 0)=DATEADD(dd, DATEDIFF(dd, 0, @date), 0)
AND CodMovimento=@CodMovimento

INSERT INTO #result (CodCaixa,CodMovimento,DatApresentacao,DatHorApresentacao,DatMovimento,HorSessao,IdOperacao,NomPeca,Operacao,Qtde, Tipo, TipSaque, Valor)
SELECT 
v.CodCaixa
,v.CodMovimento
,v.DatApresentacao
,v.DatHorApresentacao
,v.DatMovimento
,v.HorSessao
,v.IdOperacao
,v.NomPeca
,v.Operacao
,v.Qtde
,v.Tipo
,v.TipSaque
,v.Valor
FROM VW_MOV002 v 
INNER JOIN CI_MIDDLEWAY.dbo.ticketoffice_user_base toub ON v.CodCaixa=toub.codCaixa
WHERE toub.id_base=@id_base
AND toub.id_ticketoffice_user=@id_ticketoffice_user
AND DATEADD(dd, DATEDIFF(dd, 0, v.DatMovimento), 0)=DATEADD(dd, DATEDIFF(dd, 0, @date), 0)
AND CodMovimento=@CodMovimento

SELECT 
newid() id
,v.CodCaixa
,v.CodMovimento
,v.DatApresentacao
,v.DatHorApresentacao
,v.DatMovimento
,v.HorSessao
,v.IdOperacao
,v.NomPeca
,v.Operacao
,v.Qtde
,v.Tipo
,v.TipSaque
,v.Valor
,CONVERT(INT,(v.Valor*100)) ValorInt
FROM #result v
ORDER BY 
    v.IdOperacao ASC, 
    v.TipSaque DESC,  
    v.Tipo ASC