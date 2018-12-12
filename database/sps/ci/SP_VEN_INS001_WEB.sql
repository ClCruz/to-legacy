/*
+=====================================================================================================================+
!  Nº de !   Nº da     ! Data  da   ! Nome do         ! Descricao das Atividades                                      !
!  Ordem ! Solicitacao ! Manutencao ! Programador     !                                                               !
+========+=============+============+=================+===============================================================+
!   1    !    ---      ! 15/09/2010 !  Emerson Capreti! Alteracoes para o projeto MiddleWay						      !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   2    !    ---      ! 21/10/2010 !  Emerson Capreti! Tipo de ingresso com preço fixo 						      !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   3    !    0007     ! 05/11/2010 !  Emerson Capreti! Permite o site informar o caixa respectivo para controle      !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   4    !    0009     ! 23/01/2011 !  Emerson Capreti! Permite o site informar o BIN do Itau					      !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   5    !    040      ! 12/04/2011 ! Edicarlos       ! Aumentado o nome de setor de 20 para 26 caracteres            !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   6    !    062      ! 09/06/2011 ! Edicarlos       ! Incluido rotina para atualizar a tabela de tabControleSeqVenda!
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   7    !    198      ! 06/11/2012 ! Gabriel         ! Aumentado o ID_PEDIDO_IPAGARE de 16 para 36 caracteres        !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   8    !    322      ! 09/09/2013 ! Gabriel         ! - Alterado o @Step de int para varchar(20)                    !
!        !             !            !                 ! - Melhorada a forma de tratamento de erro (try / catch)       !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   9    !    331      ! 18/09/2013 ! Gabriel         ! Adicionado um complemento ao campo tabLogOperação.Operacao    !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   10   !    343      ! 04/10/2013 ! Emerson/Gabriel ! Step 3 movido para o Step 11                                  !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   11   !		       ! 16/10/2013 ! Edicarlos       ! Aumentado o campo @codapresentacao de 4 para 5 no step codbar !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   12   !		       ! 21/10/2013 ! Edicarlos       ! Retornado Step 3 e comentado Step 11 sem codCaixa 255 p/ reavaliação !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   13   !    378      ! 16/12/2013 ! Gabriel         ! Adicionado um loop na gravação do código de barras            !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   14   !             ! 15/10/2014 ! Gabriel         ! - Melhoria no relacionamento do step 3 e 4                    !
!        !             !            !                 ! - Adicionado filtro por CodApresentacao no step 8             !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   15   !             ! 17/11/2014 ! Gabriel         ! Retorno da procedure alterado para output                     !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
!   16   !    641      ! 02/10/2015 ! Gabriel         ! Adicionada tabela de reserva para números de lancamento       !
+--------+-------------+------------+-----------------+---------------------------------------------------------------+
*/
ALTER PROCEDURE [dbo].[SP_VEN_INS001_Web] (
	@Id_Session			varchar(32),		-- codigo da session web
	@id_base			int,				-- base do teatro
	@cd_meio_pagamento	int,				-- codigo do meio de pagamento (mw_meio_pagamento)
	@CodApresentacao  	int,				-- Código da Apresentação
	@DDD				varchar(3),			-- DDD do telefone do cliente
	@Telefone			varchar(20),		-- Telefone do Cliente
	@Nome				varchar(50),		-- Nome do Cliente
	@CPF				varchar(14),		-- CPF do cliente
	@RG					varchar(15),		-- RG do cliente
	@ID_PEDIDO_VENDA	int,				-- codigo do pedido de venda no MW_PEDIDO_VENDA
	@ID_PEDIDO_IPAGARE	VARCHAR(36),		-- retorno do ipagare
	@CD_NUMERO_AUTORIZACAO VARCHAR(50),		-- retorno do ipagare
	@CD_NUMERO_TRANSACAO VARCHAR(50),		-- retorno do ipagare
	@NR_CARTAO_CREDITO	varchar(16) = null,	-- numero cartao credito inteiro
	@codcaixa			tinyint,
	@return_code		tinyint output,
	@error_message 		varchar(500) output
	)   AS


DECLARE @NumLancamento  int,
		@CodVenda		varchar(10),		-- Código da Venda
        @CodLog 		int,
		@Step 			varchar(20),
		@CodTipBilhete  smallint,
		@CodTipBilheteBIN smallint,
		@Indice			int, 
		@Preco			money,
		@NomObjeto		varchar(6),
		@NomSetor		varchar(26),
		@PerDesconto	float,
		@TipBilhete		varchar(20),
		@DescVlr		money,
		@DescPerc		money,
		@CodForPagto	int,
		@BandeiraCartao	varchar(30),		-- Bandeira que o cliente utilizou para pagar
		@codusuario		tinyint,
		@codcliente		int,
		@CodEventoPatrocinado int,
		@CodProdutoPatrocinador int,
		@Datapresentacao	datetime,
		@BINCartao		varchar(16),
		@CodPeca		int,
		@Id_Cartao_patrocinado int,
		@NumeroBINAux varchar(16),
		@CodSetor tinyint,
		@HorSessao char(5),
		@numseq int,
		@codbar varchar(32)
 
SET NOCOUNT ON



select @codusuario = 255 -- usuario web

select 	@BandeiraCartao = ds_meio_pagamento 
from	ci_middleway..mw_meio_pagamento
where	cd_meio_pagamento = @cd_meio_pagamento


SET @Step = 1
exec sp_ven_retorna_codvenda @id_pedido_venda, @CodVenda output

begin try


SET @Step = 2
-- captura o @CodForPagto do De/para para ficar correto no TSP
select @codforpagto = codforpagto 
from 
	ci_middleway..mw_meio_pagamento	mp
	inner join
	ci_middleway..mw_meio_pagamento_forma_pagamento mpfp
	on  mpfp.id_base = @id_base 
	and mpfp.id_meio_pagamento = mp.id_meio_pagamento
where
	mp.cd_meio_pagamento = @cd_meio_pagamento


if @codforpagto is null 
	select top 1 @codforpagto = codforpagto from tabforpagamento where staforpagto = 'A' and (ForPagto like '%CARTÃO DE CRÉDITO' or codforpagto = 1) order by CodForPagto desc


SET @Step = 3
-- ajusta a tablugsala de acordo com a MW_RESERVA
update tablugsala
set	codtipbilhete = b.codtipbilhete,
	BINCartao = cd_binitau
from 
	tablugsala	s
	inner join
	ci_middleway..mw_reserva	r
	on	r.id_session collate Latin1_General_CI_AS  = s.id_session
	and r.id_cadeira   = s.indice
	inner join
	ci_middleway..mw_apresentacao a
	on a.id_apresentacao = r.id_apresentacao
	and a.codapresentacao = s.codapresentacao
	inner join
	ci_middleway..mw_apresentacao_bilhete b
	on	b.id_apresentacao_bilhete = r.id_apresentacao_bilhete
where
	s.id_session = @id_session
and s.codapresentacao = @codapresentacao
and s.stacadeira = 'T'


SET @Step = 4
-- ajusta o codigo da venda na mw_item_pedido_venda
update ci_middleway..mw_item_pedido_venda
set	codvenda = @codvenda
from 
	tablugsala	s
	inner join
	ci_middleway..mw_reserva	r
	on	r.id_session collate Latin1_General_CI_AS  = s.id_session
	and r.id_cadeira   = s.indice
	inner join
	ci_middleway..mw_apresentacao a
	on a.id_apresentacao = r.id_apresentacao
	and a.codapresentacao = s.codapresentacao
	inner join
	ci_middleway..mw_item_pedido_venda i
	on	i.id_reserva = r.id_reserva
where
	s.id_session = @id_session
and s.codapresentacao = @codapresentacao



-- Verifica o BIN do cartao na MW_RESERVA(se houver)
select @BINCartao = @NR_CARTAO_CREDITO
select @NR_CARTAO_CREDITO = left(@NR_CARTAO_CREDITO,6) + '******' + right(@NR_CARTAO_CREDITO,4)



SET @Step = 5
-- ajusta o codigo da venda na mw_item_pedido_venda
update ci_middleway..mw_pedido_venda
	set id_pedido_ipagare = @ID_PEDIDO_IPAGARE,
		cd_numero_autorizacao = @CD_NUMERO_AUTORIZACAO,
		cd_numero_transacao = @CD_NUMERO_TRANSACAO,
		in_situacao = 'F',
		cd_bin_cartao = @NR_CARTAO_CREDITO
where
	id_pedido_venda = @id_pedido_venda



SET @Step = 52
DECLARE C2 cursor local for 		
	SELECT 
		tabLugSala.CodTipBilhete, 
		tabApresentacao.DatApresentacao,
		tabApresentacao.CodApresentacao, 
		tabApresentacao.CodPeca,
		tabLugSala.Indice, 
		case when isnull(tabtipbilhete.vl_preco_fixo,0) > 0 then
			(tabTipBilhete.vl_preco_fixo * (100 - tabTipBilhete.PerDesconto) / 100) 
		else	
			(tabApresentacao.ValPeca * (100 - tabSetor.PerDesconto) / 100 * (100 - tabTipBilhete.PerDesconto) / 100) 
		end as Preco,
		tabSalDetalhe.NomObjeto,
		tabSetor.NomSetor,
		tabSetor.PerDesconto,
		tabTipBilhete.TipBilhete
	FROM
		tabLugSala 
		INNER JOIN 
		tabSalDetalhe 	ON tabLugSala.Indice          = tabSalDetalhe.Indice  
		INNER JOIN 
		tabApresentacao ON tabLugSala.CodApresentacao = tabApresentacao.CodApresentacao
		INNER JOIN 
		tabTipBilhete 	ON tabLugSala.CodTipBilhete   = tabTipBilhete.CodTipBilhete
		INNER JOIN 
		tabSetor 	ON tabSalDetalhe.CodSala      = tabSetor.CodSala 
			       AND tabSalDetalhe.CodSetor     = tabSetor.CodSetor
	WHERE
		tabLugSala.Id_Session = @Id_Session
	AND tabapresentacao.codapresentacao = @codapresentacao
	AND	(tabLugSala.StaCadeira = 'T' OR tabLugSala.StaCadeira = 'M') 


-- Verifica se o cliente ja esta cadastrado, se nao, cadastra
if not exists (select 1 from tabcliente where cpf = @CPF)
	begin
		--select @codcliente = isnull(max(codigo)+1,1) from tabcliente
		
		SET @Step = 53

		insert into tabcliente (nome, rg, cpf, ddd, telefone,stacliente)
		values (@nome, @RG, @CPF, @DDD, @Telefone,'A')	

        SET @codcliente=SCOPE_IDENTITY()
	end
else
	select @codcliente = codigo from tabcliente where cpf = @CPF




SET @Step = 99
-- Recupera o novo Numero de Lancamento
while (exists (select 1 from tabLancamento where NumLancamento = @NumLancamento) OR @NumLancamento is null)
begin

	insert into reserva_NumLancamento default values

	SELECT @NumLancamento = SCOPE_IDENTITY()
	
end




SET @Step = 6
--Atualiza a tabela de Comprovantes
INSERT INTO tabComprovante
	(CodVenda,
	TipDocumento,
	NomSala,
	Nome,
	Numero,
	DatValidade,
	DDD,
	Telefone,
	Ramal,
	CPF,
	RG,
	ForPagto,
	NomUsuario,
	StaImpressao,
	NomEmpresa,
	CodCliente,
	CodApresentacao,
	CodPeca)
select
	@CodVenda,
	'V',
	s.NomSala,
	@Nome,
	@NR_CARTAO_CREDITO,
	'0000',
	@DDD,
	@Telefone,
	null,
	@CPF,
	@RG,
	@BandeiraCartao,
	'WEB',
	0,
	left(@Nome,30),
	@codcliente,
	@CodApresentacao,
	a.CodPeca
from
	tabapresentacao	a
	inner join
	tabsala			s
	on	s.codsala = a.codsala
where
	a.codapresentacao = @codapresentacao



open C2

fetch next from C2 into 	
	@CodTipBilhete,
	@DatApresentacao,
	@CodApresentacao, 
	@CodPeca,
	@Indice,
	@Preco,
	@NomObjeto,
	@NomSetor,
	@PerDesconto,
	@TipBilhete

while @@fetch_status = 0
BEGIN
  	
	Select  @DescPerc = isnull(sum(@Preco * case TTLB.icdebcre when 'D' then (isnull(TTBTL.valor,0)/100) else (isnull(TTBTL.valor,0)/100) * -1 end),0)
	FROM 
		tabTipBilhTipLcto	TTBTL
	INNER JOIN
		tabTiplanctoBilh	TTLB
		ON  TTLB.codtiplct  = TTBTL.codtiplct
		and TTLB.icpercvlr  = 'P'
		and TTLB.icusolcto != 'B'
		and TTLB.inativo    = 'A'
	WHERE
		TTBTL.codtipbilhete = @codtipbilhete
	and 	TTBTL.dtinivig      = (Select max(TTBTL1.dtinivig) 
				 from tabTipBilhTipLcto  TTBTL1,
				      tabTipLanctoBilh   TTLB1
				where TTBTL1.codtipbilhete = TTBTL.codtipbilhete
				  and TTBTL1.codtiplct     = TTBTL.codtiplct
				  and TTBTL1.dtinivig     <= getdate()
				  and TTBTL1.inativo       = 'A'
				  and TTLB1.codtiplct     = TTBTL1.codtiplct
				  and TTLB1.IcPercVlr     = 'P'
				  and TTLB1.icusolcto    != 'B'
				  and TTLB1.inativo       = 'A')
	and 	TTBTL.inativo        = 'A'


	Select
		@DescVlr = isnull(sum(case TTLB.icdebcre when 'D' then isnull(TTBTL.valor,0) else isnull(TTBTL.valor,0) * -1 end),0)
	FROM 
		tabTipBilhTipLcto	TTBTL
	INNER JOIN
		tabTiplanctoBilh	TTLB
		ON  TTLB.codtiplct  = TTBTL.codtiplct
		and TTLB.icpercvlr  = 'V'
		and TTLB.icusolcto != 'B'
		and TTLB.inativo    = 'A'
	WHERE
		TTBTL.codtipbilhete = @codtipbilhete
	and 	TTBTL.dtinivig      = (Select max(TTBTL1.dtinivig) 
				 from tabTipBilhTipLcto  TTBTL1,
				      tabTipLanctoBilh   TTLB1
				where TTBTL1.codtipbilhete = TTBTL.codtipbilhete
				  and TTBTL1.codtiplct     = TTBTL.codtiplct
				  and TTBTL1.dtinivig     <= getdate()
				  and TTBTL1.inativo       = 'A'
				  and TTLB1.codtiplct     = TTBTL1.codtiplct
				  and TTLB1.IcPercVlr     = 'V'
				  and TTLB1.icusolcto    != 'B'
				  and TTLB1.inativo       = 'A')
	and 	TTBTL.inativo        = 'A'

	SET @Step = 7
	-- Insere um Lancamento na tabLancamento
   	INSERT INTO tabLancamento 
		(NumLancamento, 
		CodTipBilhete, 
		CodTipLancamento, 
		CodApresentacao, 
		Indice, 
		CodUsuario, 
		CodForPagto, 
		CodCaixa, 
		DatMovimento, 
		QtdBilhete, 
		ValPagto, 
		DatVenda, 
		CodMovimento)
	Values (@NumLancamento, 
		@CodTipBilhete, 
		1, 
		@CodApresentacao, 
		@Indice, 
		@codusuario, 
		@CodForPagto,
		@codcaixa,
		getdate(), 
		1, 
		isnull(@Preco, 0) + @DescVlr + @DescPerc,
		GETDATE(), 
		null)



	

	-- GERAR CODBARRA
	SELECT		
		@CodSetor = D.CODSETOR,		
		@HorSessao = A.HORSESSAO,		
		@codcaixa = L.CODCAIXA
	FROM 
		TABLANCAMENTO L
	INNER JOIN	
		TABSALDETALHE D ON D.INDICE = L.INDICE
	INNER JOIN
		TABAPRESENTACAO A ON A.CODAPRESENTACAO = L.CODAPRESENTACAO
	WHERE 
		L.NUMLANCAMENTO = @NumLancamento AND
		L.INDICE = @INDICE AND 
		L.CODAPRESENTACAO = @CodApresentacao AND
		L.CODTIPBILHETE = @CodTipBilhete AND
		L.CODTIPLANCAMENTO = 1

	-- by Emerson Capreti - 10/09/2010 - Sequencia numerica de controle do ingresso
	IF exists (select 1 from sysobjects where type = 'U' and name = 'tabControleSeqVenda')
	BEGIN

		DECLARE @timeout datetime;
		SET @timeout = DATEADD(SECOND, 5, GETDATE());

		-- This will loop until BREAK is called, or until a timeout.
		WHILE (GETDATE() < @timeout)
		BEGIN
			BEGIN TRY
							
				SELECT @numseq = max(numseq)+1 from tabControleSeqVenda where codapresentacao = @CodApresentacao

				IF @numseq is null
					SELECT @numseq = 1
				
				SELECT @codbar = right('0000000'+convert(varchar,@codapresentacao),7)
						--+convert(char(1), @CodSetor)
						+right('00000'+convert(varchar(5),@Indice),5)					
						--+right(convert(varchar(8),@DatApresentacao,112),4)
						--+right('0000'+replace(convert(varchar(5),@HorSessao),':',''),4)
						--+right('00000'+convert(varchar(4),@CodTipBilhete),3)					
						+right('0000000000'+convert(varchar(4),@numseq),10)

						-- select @codbar codbar, @Indice indice, @CodApresentacao apresentacao, @numseq

				-- STATUSINGRESSO -> L = LIBERADO PARA PASSAR NA CATRACA
				INSERT INTO tabControleSeqVenda (codapresentacao, indice, numseq, codbar, statusingresso)
					VALUES (@CodApresentacao, @Indice, @numseq, @codbar, 'L')

				SET @Step = ''
				BREAK

			END TRY
			BEGIN CATCH
				-- armazerna os dados do erro para o caso de sair do loop via timeout
SELECT
        ERROR_NUMBER() AS ErrorNumber,
        ERROR_SEVERITY() AS ErrorSeverity,
        ERROR_STATE() AS ErrorState,
        ERROR_PROCEDURE() AS ErrorProcedure,
        ERROR_LINE() AS ErrorLine,
        ERROR_MESSAGE() AS ErrorMessage


				SET @Step = 'Gerar codBar web'
				DECLARE @ErMessage NVARCHAR(2048), @ErSeverity INT, @ErState INT
				SELECT @ErMessage = ERROR_MESSAGE(), @ErSeverity = ERROR_SEVERITY(), @ErState = ERROR_STATE()
				
				-- Throttle the loop for 1 seconds.
				WAITFOR DELAY '00:00:01';
			END CATCH
		END

		-- se sair do loop via timeout mandar o erro para o próximo catch
		IF @Step != ''
		BEGIN
			RAISERROR(@ErMessage, @ErSeverity, @ErState)
		END

	END

  	-- Insere um histórico de cliente caso o @CodCliente nao seja NULL  		
	IF (NOT @CodCliente IS NULL)  AND  (@CodCliente <> 0)
		begin
		SET @Step = 71
   		INSERT INTO tabHisCliente 
			(Codigo, 
			NumLancamento, 
			CodTipBilhete, 
			CodTipLancamento, 
			CodApresentacao, 
			Indice)
          	values (@CodCliente, 
			@NumLancamento, 
			@CodTipBilhete, 
			1, 
			@CodApresentacao, 
			@Indice)
		end



	-- Verifica se existe evento promocional para a peca
	select  @Id_Cartao_patrocinado = cp.id_cartao_patrocinado,
			@CodTipBilheteBIN = p.CodTipBilheteBIN
	from 
		tabapresentacao a
		inner join
		tabpeca	p
		on	p.codpeca = a.codpeca
		inner join
		ci_middleway..mw_evento_patrocinado ep 
		on  ep.codpeca = a.codpeca
		and convert(varchar, datapresentacao,112) between convert(varchar, ep.dt_inicio,112) and convert(varchar, ep.dt_fim ,112)

		inner join 
		ci_middleway..mw_cartao_patrocinado cp 
		on cp.id_cartao_patrocinado = ep.id_cartao_patrocinado 
		and cp.cd_bin = left(@BINCartao,6)

		inner join 
		ci_middleway..mw_base b 
		on b.id_base = ep.id_base 
		and b.ds_nome_base_sql = DB_NAME() 

		where a.codapresentacao = @CodApresentacao

	if @CodTipBilheteBIN = @CodTipBilhete 
		select @NumeroBINAux = @BINCartao 
	else
		select @NumeroBINAux = null
	
    
    SET @Step = 8
	--Atualiza a tabela de Ingressos
	INSERT INTO tabIngresso
		(Indice,
		CodVenda,
		NomObjeto,
		NomPeca,
		NomRedPeca,
		DatApresentacao,
		HorSessao,
		Elenco,
		Autor,
		Diretor,
		NomRedSala,
		TipBilhete,
		ValPagto,
		CodCaixa,
		Login,
		NomResPeca,
		CenPeca,
		NomSetor,
		DatVenda,
		Qtde,
		PerDesconto,
		StaImpressao,
		CodSala,
		Id_Cartao_patrocinado,
		BINCartao)
	select  @Indice, 
			@CodVenda, 
			@NomObjeto, 
			left(NomPeca, 35),
			left(NomRedPeca, 35),
			DatApresentacao, 
			HorSessao, 
			left(Elenco,50),
			left(Autor, 50),
			left(Diretor, 50),
			left(NomRedSala, 6),
			@TipBilhete,  
			@Preco,
			@codcaixa, 
			'web', 
			left(NomResPeca,6),
			CenPeca,
			left(NomSetor,20),
			getdate(), 
			1, 
			@PerDesconto, 
			0, 
			s.CodSala,
			@Id_Cartao_patrocinado,
			@NumeroBINAux
	from 
		tablugsala		ls
		inner join
		tabapresentacao	a
		on	a.codapresentacao = ls.codapresentacao
		inner join
		tabsala			s
		on	s.codsala = a.codsala
		inner join
		tabpeca			p
		on	p.codpeca = a.codpeca
		inner join
		tabsaldetalhe	d
		on	d.indice = ls.indice
		inner join
		tabtipbilhete	tb
		on	tb.codtipbilhete = ls.codtipbilhete
		inner join
		tabsetor		ts
		on	ts.codsetor = d.codsetor
		and	ts.codsala = s.codsala
	where 
		ls.id_session = @id_session
	and d.indice = @indice
	and ls.codapresentacao = @CodApresentacao




	SET @Step = 9
	/* Insere os lancamentos relacionados com o tipo de bilhete do tipo Percentual */
	INSERT INTO tabIngressoAgregados (CodVenda, Indice, CodTipLct, Valor)
	Select
		@CodVenda, 
		@Indice,
		TTBTL.codtiplct,
		@Preco * case TTLB.icdebcre when 'D' then (TTBTL.valor/100) else (TTBTL.valor/100) * -1 end
	FROM 
		tabTipBilhTipLcto	TTBTL
	INNER JOIN
		tabTiplanctoBilh	TTLB
		ON  TTLB.codtiplct  = TTBTL.codtiplct
		and TTLB.icpercvlr  = 'P'
		and TTLB.icusolcto != 'B'
		and TTLB.inativo    = 'A'
	WHERE
		TTBTL.codtipbilhete = @codtipbilhete
	and 	TTBTL.dtinivig      = (Select max(TTBTL1.dtinivig) 
				 from tabTipBilhTipLcto  TTBTL1,
				      tabTipLanctoBilh   TTLB1
				where TTBTL1.codtipbilhete = TTBTL.codtipbilhete
				  and TTBTL1.codtiplct     = TTBTL.codtiplct
				  and TTBTL1.dtinivig     <= getdate()
				  and TTBTL1.inativo       = 'A'
				  and TTLB1.codtiplct     = TTBTL1.codtiplct
				  and TTLB1.IcPercVlr     = 'P'
				  and TTLB1.icusolcto    != 'B'
				  and TTLB1.inativo       = 'A')
	and 	TTBTL.inativo        = 'A'



	SET @Step = 10
	/* Insere os lancamentos relacionados com o tipo de bilhete do tipo Valor */
	INSERT INTO tabIngressoAgregados (CodVenda, Indice, CodTipLct, Valor)
	Select
		@CodVenda, 
		@Indice,
		TTBTL.codtiplct,
		case TTLB.icdebcre when 'D' then (TTBTL.valor) else (TTBTL.valor) * -1 end
	FROM 
		tabTipBilhTipLcto	TTBTL
	INNER JOIN
		tabTiplanctoBilh	TTLB
		ON  TTLB.codtiplct  = TTBTL.codtiplct
		and TTLB.icpercvlr  = 'V'
		and TTLB.icusolcto != 'B'
		and TTLB.inativo    = 'A'
	WHERE
		TTBTL.codtipbilhete = @codtipbilhete
	and 	TTBTL.dtinivig      = (Select max(TTBTL1.dtinivig) 
				 from tabTipBilhTipLcto  TTBTL1,
				      tabTipLanctoBilh   TTLB1
				where TTBTL1.codtipbilhete = TTBTL.codtipbilhete
				  and TTBTL1.codtiplct     = TTBTL.codtiplct
				  and TTBTL1.dtinivig     <= getdate()
				  and TTBTL1.inativo       = 'A'
				  and TTLB1.codtiplct     = TTBTL1.codtiplct
				  and TTLB1.IcPercVlr     = 'V'
				  and TTLB1.icusolcto    != 'B'
				  and TTLB1.inativo       = 'A')
	and 	TTBTL.inativo        = 'A'




	fetch next from C2 into 	
		@CodTipBilhete,
		@DatApresentacao,
		@CodApresentacao, 
		@CodPeca,
		@Indice,
		@Preco,
		@NomObjeto,
		@NomSetor,
		@PerDesconto,
		@TipBilhete

END

CLOSE C2
DEALLOCATE C2


-- Necessário avaliar script comentado para uso posterior, é necessário a verificação do caixa 255 devido ao controle de complemento de meia
--SET @Step = 11
-- -- Atualiza a tabLugSala
--update tablugsala
--set	BINCartao = cd_binitau,
--	CodVenda   = @CodVenda, 
--	StaCadeira = 'V',
--	CodUsuario = @CodUsuario,
--	CodCaixa   = @CodCaixa,
--	Id_session = null
--from 
--	tablugsala	s
--	inner join
--	ci_middleway..mw_reserva	r
--	on	r.id_session collate Latin1_General_CI_AS  = s.id_session
--	and r.id_cadeira   = s.indice
--	inner join
--	ci_middleway..mw_apresentacao_bilhete b
--	on	b.id_apresentacao_bilhete = r.id_apresentacao_bilhete
--where
--	s.id_session = @id_session
--and s.codapresentacao = @codapresentacao
--and s.stacadeira = 'T'	


SET @Step = 11
 -- Atualiza a tabLugSala
UPDATE tabLugSala 
SET CodVenda   = @CodVenda, 
	StaCadeira = 'V',
	CodUsuario = @CodUsuario,
	CodCaixa   = @CodCaixa,
	Id_session = null
WHERE 
			CodApresentacao = @CodApresentacao
	AND     StaCadeira      = 'T'
	AND		Id_Session      = @Id_Session
	

 -- Grava Log de Operação
SELECT @CodLog = (SELECT COALESCE(MAX(IdLogOperacao),0)+1 FROM tabLogOperacao)

SET @Step = 12
INSERT INTO tabLogOperacao (IdLogOperacao, DatOperacao, CodUsuario, Operacao) VALUES (@CodLog, GETDATE(), @CodUsuario, LEFT('Venda de Ingressos pela WEB Middleway - ID Pedido Venda: ' + convert(varchar(20), @ID_PEDIDO_VENDA) + ' - Cod.Apresentacao: ' + convert(varchar(20), @CodApresentacao) + ' - Cod.Venda: ' + convert(varchar(20), @CodVenda), 500))



SET @Step = 13
INSERT INTO tabLogOpeDetalhe (IdLogOperacao, Indice, NumLancamento, TipLancamento) 
	SELECT @CodLog, Indice, @NumLancamento, 1 FROM tabLugSala 
		WHERE CodCaixa = @CodCaixa  
			AND CodApresentacao = @CodApresentacao   
			AND (StaCadeira = 'T' or StaCadeira = 'M')
			AND	tabLugSala.Id_Session = @Id_Session




SET @Step = 14
--Atualiza os detalhes do Pagto
INSERT INTO tabDetPagamento (CodForPagto, NumLancamento, Agencia, Numero, DatValidade,Observacao )
		VALUES(@CodForPagto, @NumLancamento, null, @NR_CARTAO_CREDITO, '0000',null)





SET NOCOUNT OFF


SELECT @return_code = 0


end try
begin catch


  	SET NOCOUNT OFF
	SELECT @return_code = @Step, @error_message = left(ERROR_MESSAGE(), 500)

	
end catch

RETURN