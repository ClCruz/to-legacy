--
-- pr_split 235, 125
CREATE PROCEDURE dbo.pr_split (@codPeca INT, @id_base INT)

AS

-- DECLARE @codPeca INT
--         ,@id_base INT

-- SELECT @codPeca=146
--         ,@id_base=44

SELECT 
r.recipient_id
,rs.nr_percentual_split
,rs.liable
,rs.charge_processing_fee
,rs.percentage_credit_web
,rs.percentage_debit_web
,rs.percentage_boleto_web
,rs.percentage_credit_box_office
,rs.percentage_debit_box_office
,(CASE r.cd_cpf_cnpj WHEN '11665394000113' THEN 1 ELSE 0 END) IsTicketPay
FROM tabPeca tb
INNER JOIN CI_MIDDLEWAY..mw_evento e ON tb.CodPeca = e.CodPeca
INNER JOIN CI_MIDDLEWAY..mw_produtor p ON p.id_produtor = tb.id_produtor and p.in_ativo = 1
INNER JOIN CI_MIDDLEWAY..mw_regra_split rs ON rs.id_produtor = p.id_produtor and rs.id_evento = e.id_evento
INNER JOIN CI_MIDDLEWAY..mw_recebedor r ON rs.id_recebedor = r.id_recebedor and r.in_ativo = 1
WHERE tb.CodPeca = @codPeca and e.id_base=@id_base and rs.in_ativo = 1
ORDER BY (CASE r.cd_cpf_cnpj WHEN '11665394000113' THEN 1 ELSE 0 END)