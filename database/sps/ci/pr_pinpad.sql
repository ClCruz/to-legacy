--exec sp_executesql N'EXEC pr_pinpad @P1,@P2,@P3,@P4,@P5,@P6',N'@P1 nvarchar(4000),@P2 nvarchar(4000),@P3 nvarchar(4000),@P4 nvarchar(4000),@P5 nvarchar(4000),@P6 nvarchar(4000)',N'8CC26A74-7E65-411E-B854-F7B281A46E01',N'209',N'1',N'167231',N'53',N'null'
--exec sp_executesql N'EXEC pr_pinpad @P1,@P2,@P3,@P4,@P5,@P6',N'@P1 nvarchar(4000),@P2 nvarchar(4000),@P3 nvarchar(4000),@P4 nvarchar(4000),@P5 nvarchar(4000),@P6 nvarchar(4000)',N'8CC26A74-7E65-411E-B854-F7B281A46E01',N'44',N'151',N'167079',N'53',N'18266'
--select * from CI_MIDDLEWAY..ticketoffice_pinpad
ALTER PROCEDURE dbo.pr_pinpad (@id_ticketoffice_user UNIQUEIDENTIFIER,@id_base INT,@codPeca INT,@id_apresentacao INT, @id_payment INT, @codCliente INT = NULL)

AS

-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER = '8CC26A74-7E65-411E-B854-F7B281A46E01'
-- ,@id_base INT = 44
-- ,@codPeca INT = 151
-- ,@id_apresentacao INT = 167079
-- , @id_payment INT = 53
-- , @codCliente INT = 18266


SET NOCOUNT ON;

DECLARE @base VARCHAR(1000)
        ,@id_evento INT
        ,@key VARCHAR(50)
        ,@amount INT

SET @key = REPLACE(CONVERT(VARCHAR(50),newid()),'-','')

SELECT @base=b.ds_nome_base_sql
FROM CI_MIDDLEWAY..mw_base b where b.id_base=@id_base

SELECT TOP 1 @id_evento=a.id_apresentacao
FROM CI_MIDDLEWAY..mw_apresentacao a where a.id_apresentacao=@id_apresentacao

SELECT @amount=SUM(tosc.amount_topay)
FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
WHERE tosc.id_ticketoffice_user=@id_ticketoffice_user AND tosc.id_base=@id_base AND id_apresentacao=@id_apresentacao AND id_event=@codPeca
--AND tosc.id_pedido_venda=@id_pedido_venda AND tosc.codVenda=@codVenda

INSERT INTO CI_MIDDLEWAY..ticketoffice_pinpad (id_ticketoffice_user
,[key]
,id_base,base
,amount
,codPeca,id_apresentacao,id_evento
,pinpad_acquirerResponseCode,pinpad_transactionId,pinpad_executed,pinpad_error,pinpad_cancel,pinpad_ok,pinpad_fail
,codVenda
,id_payment
,codCliente)
VALUES (@id_ticketoffice_user
,@key
,@id_base,@base
,@amount
,@codPeca,@id_apresentacao,@id_evento
,0,0,0,0,0,0,0
,NULL
,@id_payment, @codCliente)

SELECT @key [key]