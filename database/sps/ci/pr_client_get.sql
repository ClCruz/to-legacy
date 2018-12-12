--
ALTER PROCEDURE dbo.pr_client_get (@cpf VARCHAR(14), @code INT = NULL)

AS

-- DECLARE @cpf VARCHAR(14)

-- SET @cpf='131.537.614-85'

DECLARE @cpfAux VARCHAR(14)

SET @cpfAux=REPLACE(REPLACE(@cpf, '-', ''), '.', '')

SELECT
c.Codigo
,REPLACE(REPLACE(c.CPF, '-', ''), '.', '') cpfclean
,c.Nome
,c.Sexo
,c.DatNascimento
,c.RG
,c.CPF
,c.Endereco
,c.Numero
,c.Complemento
,c.Bairro
,c.Cidade
,c.UF
,c.CEP
,c.DDD
,c.Telefone
,c.Ramal
,c.DDDCelular
,c.Celular
,c.DDDComercial
,c.TelComercial
,c.RamComercial
,c.MalDireta
,c.EMail
,c.StaCliente
,c.Assinatura
,c.CardBin
,c.created
,cli.id_cliente
,cli.cd_cep
,cli.cd_cpf
,cli.cd_email_login
,cli.cd_password
,cli.cd_rg
,cli.ds_bairro
,cli.ds_celular
,cli.ds_cidade
,cli.ds_compl_endereco
,cli.ds_ddd_celular
,cli.ds_ddd_telefone
,cli.ds_endereco
,cli.ds_nome
,cli.ds_sobrenome
,cli.ds_telefone
,cli.dt_inclusao
,cli.dt_nascimento
,cli.id_doc_estrangeiro
,cli.id_estado
,cli.in_assinante
,cli.in_concorda_termos
,cli.in_recebe_info
,cli.in_recebe_sms
,cli.in_sexo
,cli.nr_endereco
,es.ds_estado
,es.sg_estado
FROM tabCliente c
LEFT JOIN CI_MIDDLEWAY..mw_cliente cli ON REPLACE(REPLACE(c.CPF, '-', ''), '.', '')=REPLACE(REPLACE(cli.cd_cpf, '-', ''), '.', '') COLLATE SQL_Latin1_General_CP1_CI_AS
LEFT JOIN CI_MIDDLEWAY..mw_estado es ON cli.id_estado=es.id_estado
WHERE
(@code IS NULL OR c.Codigo=@code)
AND (@cpf = '' OR REPLACE(REPLACE(c.CPF, '-', ''), '.', '')=@cpfAux COLLATE SQL_Latin1_General_CP1_CI_AS)