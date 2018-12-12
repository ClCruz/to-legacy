ALTER PROCEDURE dbo.pr_checkbin (@key VARCHAR(100)
        ,@bin VARCHAR(10))

AS
-- DECLARE @id_ticketoffice_user UNIQUEIDENTIFIER
--         ,@bin VARCHAR(10)

SET NOCOUNT ON;

DECLARE @checkBin BIT = 0
        ,@success BIT = 1


SELECT TOP 1 @checkBin = 1
FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON tosc.id_apresentacao=ap.id_apresentacao
INNER JOIN tabTipBilhete tb ON tosc.id_ticket_type=tb.CodTipBilhete
INNER JOIN CI_MIDDLEWAY..mw_promocao_controle pc ON tb.id_promocao_controle=pc.id_promocao_controle and ap.dt_apresentacao BETWEEN pc.dt_inicio_promocao AND pc.dt_fim_promocao
INNER JOIN CI_MIDDLEWAY..ticketoffice_pinpad topi ON tosc.id_ticketoffice_user=topi.id_ticketoffice_user
WHERE topi.[key]=@key

IF @checkBin = 1
    SET @success = 0


IF @checkBin = 1
BEGIN
    SELECT TOP 1 @success = 1
    FROM CI_MIDDLEWAY..ticketoffice_shoppingcart tosc
    INNER JOIN CI_MIDDLEWAY..mw_apresentacao ap ON tosc.id_apresentacao=ap.id_apresentacao
    INNER JOIN tabTipBilhete tb ON tosc.id_ticket_type=tb.CodTipBilhete
    INNER JOIN CI_MIDDLEWAY..mw_promocao_controle pc ON tb.id_promocao_controle=pc.id_promocao_controle and ap.dt_apresentacao BETWEEN pc.dt_inicio_promocao AND pc.dt_fim_promocao
    INNER JOIN CI_MIDDLEWAY..mw_cartao_patrocinado cp ON pc.id_patrocinador=cp.id_patrocinador
    INNER JOIN CI_MIDDLEWAY..ticketoffice_pinpad topi ON tosc.id_ticketoffice_user=topi.id_ticketoffice_user
    WHERE topi.[key]=@key
    AND cp.cd_bin=@bin
END

SELECT @checkBin [check], @success [success]