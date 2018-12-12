/*
+==========================================================================================================================+
!  Nº de !   Nº da     ! Data  da   ! Nome do           ! Descricao das Atividades                                         !
!  Ordem ! Solicitacao ! Manutencao ! Programador       !                                                                  !
+========+=============+============+===================+==================================================================+
!   1    !    436      ! 23/03/2004 !  Emerson Capreti  ! Incluir debitos/creditos diversos na tabIngressoAgregados        !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   2    !    1369     ! 12/02/2008 ! Marciano S.C      ! Modificação no tipo de dado  @CodTipBilhete tinyint p/ smallint  !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   3    !    --       ! 10/09/2010 ! Emerson Capreti   ! Alimentar tabela de controle sequencial de ingressos             !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   4    !   0009      ! 20/11/2010 ! Emerson Capreti   ! BIN Itau												           !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   5    !   0029      ! 05/04/2011 ! Jefferson Ferreira! Alterado a sequência do código de barra para:                    !
!		 !			   !			!					! Cod.Apresentação Setor Mês/Dia Hr/Min Tipo Bilhete Num.Sequencial!
!		 !			   !			!					!    0001            1    1014    1400     001           12345     !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   6    !    040      ! 12/04/2011 ! Edicarlos Barbosa ! Aumentado o nome de setor de 20 para 26 caracteres               !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   7    !    223      ! 24/05/2013 ! Edicarlos Barbosa ! Criado a variável @CodTipLancamento							   !
!		 !			   !			!					! Adicionado o select para recuperar o CodTipLancamento			   !
!		 !			   !			!					! Incluído a variável @CodTipLancamento no Insert da tabLancamento !
!		 !			   !			!					! Adicionado a condição tabLugSala.StaCadeiraComplMeia = 'V' no	   ! 	
!		 !			   !			!					! select que retorna os ingressos vendidos.						   !	
!		 !			   !			!					! Adicionado IF com Update para atualizar a tabLugSala quando	   !	
!		 !			   !			!					! @CodTipLancamento igual a 4	 								   !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   8    !    223      ! 27/06/2013 ! Edicarlos Barbosa ! Adicionado @CodCaixaVendaAnt nos parâmetros da procedure		   !
!		 !			   !			!					! Adicionado CASE para CodCaixa no SELECT inicial da procedure     !
!		 !			   !			!					! Adicionado condição p/ CodApresentacao no select inicial         !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   9    !		       ! 16/10/2013 ! Edicarlos	Barbosa	! Aumentado o campo @codapresentacao de 4 para 5 no step codbar	   !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   10   !	 418       ! 22/05/2014 !Jeffersib Ferreira	! Incluido o codigo da venda na gravação do log da tablogoperacao   !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
!   11   !	 482       ! 01/12/2016 !Carlos Soares     	! Alterado datatype  do campo  nomobjeto de 6 para 9 caracteres    !
+--------+-------------+------------+-------------------+------------------------------------------------------------------+
*/
ALTER PROCEDURE dbo.SP_VEN_INS001 (
	@CodVenda			varchar(10),	-- Código da Venda
	@NomPeca			varchar(35),	-- Nome da Peça
	@NomRedPeca			varchar(35),	-- Nome Reduzido da Peça
	@CodApresentacao  	int,			-- Código da Apresentação
	@DatApresentacao	datetime,		-- Data da Apresentação
	@HorSessao			char(5),		-- Hora da Apresentação
	@Elenco				varchar(50),	-- Elenco da Peça
	@Autor				varchar(50),	-- Autor	da Peça
	@Diretor			varchar(50),	-- Diretor da Peça
	@NomRedSala			varchar(6),		-- Nome Reduzido da Sala
	@CodCaixa			int,			-- Código do Caixa
	@CodUsuario  		tinyint,		-- Código do Usuário
	@Login				varchar(10),	-- Login do Usuário
	@NomUsuario			varchar(30),	-- Nome Completo do Usuário
	@CodMovimento 		int,			-- Código do Movimento do Caixa
	@DatMovimento		smalldatetime,	-- Data do Movimento do Caixa
	@StrLog 			varchar(500),	-- Log de Operação
	@NomResPeca			varchar(6),		-- Nome do Responsável pela Peça
	@CenPeca			tinyint,		-- Censura da Peça
	@CodForPagto  		tinyint,		-- Código da Forma de Pagamento
	@ForPagto			varchar(30),	-- Forma de Pagamento
	@ValPagto  			money,			-- Valor total do Pagamento
	@Agencia  			varchar(5),		-- Agência do Banco no caso do pagamento por cheque
	@CodCliente  		int,			-- Código do cliente
	@Nome				varchar(50),	-- Nome do Cliente
	@NumeroBIN  		varchar(16),	-- Número do Cartão de Crédito do Cliente
	@DatValidade  		varchar(7),		-- Data ou Código de Validade do Cartão de Crédito
	@DDD				varchar(3),		-- DDD da localidade do cliente
	@Telefone			varchar(20),	-- Telefone do Cliente
	@Ramal				varchar(4),		-- Ramal do Cliente
	@CPF				varchar(14),	-- CPF do Cliente
	@RG					char(15),		-- RG do Cliente
	@Observacao 		varchar(255),	-- Observação sobre a venda
	@CodSala			smallint,		-- Código da Sala
	@NomSala			varchar(30),	-- Nome da Sala
	@NomEmpresa			varchar(100),	-- Nome da Empresa
	@CodPeca			int,			-- Código da Peça
	@CodCaixaVendaAnt	int	= null		-- Código do Caixa da Venda Anterior utilizado em Complemento de Meia
)   AS

DECLARE 
	@NumLancamento		int,
    @CodLog 			int,
	@Step 				varchar(20),
	@CodTipBilhete		smallint,
	@CodTipBilheteBIN	smallint,
	@Indice				int, 
	@Preco				money,
	@NomObjeto			varchar(9),
	@NomSetor			varchar(26),
	@PerDesconto		float,
	@TipBilhete			varchar(20),
	@DescVlr			money,
	@DescPerc			money,
	@DtMovHora			smalldatetime,
	@NumSeq				int,
	@codsetor			smallint,
	@codbar				varchar(32),
	@Id_Cartao_patrocinado int,
	@NumeroBINAux		varchar(16),
	@CodTipLancamento	int



SET NOCOUNT ON

select @DtMovHora = convert(smalldatetime, convert(varchar(8),@DatMovimento,112) + ' ' + left(convert(varchar, getdate(),114),8))

IF @CodCaixaVendaAnt IS NULL
BEGIN
	SET @CodCaixaVendaAnt = 0
END

DECLARE C1 cursor for 			
	SELECT 
		CASE WHEN ISNULL(tabLugSala.CodTipBilheteComplMeia, 0) = 0 THEN tabLugSala.CodTipBilhete ELSE tabLugSala.CodTipBilheteComplMeia END CodTipBilhete, 		
		tabApresentacao.CodApresentacao, 
		tabLugSala.Indice, 
		case when isnull(tabtipbilhete.vl_preco_fixo,0) > 0 then
			(tabTipBilhete.vl_preco_fixo * (100 - tabTipBilhete.PerDesconto) / 100) 
		else	
			(tabApresentacao.ValPeca * (100 - tabSetor.PerDesconto) / 100 * (100 - tabTipBilhete.PerDesconto) / 100) 
		end as Preco,
		tabSalDetalhe.NomObjeto,
		tabsetor.codsetor,
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
		tabTipBilhete 	ON CASE WHEN ISNULL(tabLugSala.CodTipBilheteComplMeia, 0) = 0 THEN tabLugSala.CodTipBilhete ELSE tabLugSala.CodTipBilheteComplMeia END   = tabTipBilhete.CodTipBilhete
		INNER JOIN 
		tabSetor 	ON tabSalDetalhe.CodSala      = tabSetor.CodSala 
			       AND tabSalDetalhe.CodSetor     = tabSetor.CodSetor
	WHERE
		(tabLugSala.CodCaixa = CASE WHEN @CodCaixaVendaAnt = 0 THEN @CodCaixa ELSE @CodCaixaVendaAnt END)
	AND		 tabLugSala.CodApresentacao = @CodApresentacao
	AND    ((tabLugSala.StaCadeira = 'T' OR tabLugSala.StaCadeira = 'M') OR (isnull(tabLugSala.StaCadeiraComplMeia, 'T') = 'M' AND tabLugSala.StaCadeira = 'V'))


-- Recupera o novo Numero de Lancamento
SELECT @NumLancamento = (SELECT COALESCE(MAX(NumLancamento),0)+1 FROM tabLancamento)


BEGIN TRANSACTION 


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
Values (@CodVenda,
	'V',
	@NomSala,
	@Nome,
	@NumeroBIN,
	@DatValidade,
	@DDD,
	@Telefone,
	@Ramal,
	@CPF,
	@RG,
	@ForPagto,
	@NomUsuario,
	0,
	@NomEmpresa,
	@CodCliente,
	@CodApresentacao,
	@CodPeca)
IF @@ERROR <> 0 
	BEGIN
		SET @Step = '2'
		GOTO ERRO
	END



open C1

fetch next from C1 into 	
	@CodTipBilhete,
	@CodApresentacao, 
	@Indice,
	@Preco,
	@NomObjeto,
	@CodSetor,
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
				  and TTBTL1.dtinivig     <= @DatMovimento
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
				  and TTBTL1.dtinivig     <= @DatMovimento
				  and TTBTL1.inativo       = 'A'
				  and TTLB1.codtiplct     = TTBTL1.codtiplct
				  and TTLB1.IcPercVlr     = 'V'
				  and TTLB1.icusolcto    != 'B'
				  and TTLB1.inativo       = 'A')
	and 	TTBTL.inativo        = 'A'
	
	-- Define o Código do tipo de lançamento
	SELECT @CodTipLancamento = CASE WHEN ISNULL(STACADEIRACOMPLMEIA, 'T') = 'M' THEN 4 ELSE 1 END 
	FROM TABLUGSALA 
	WHERE CODAPRESENTACAO = @CodApresentacao AND INDICE = @Indice
	
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
		@CodTipLancamento, 
		@CodApresentacao, 
		@Indice, 
		@CodUsuario, 
		@CodForPagto, 
		@CodCaixa, 
		@DtMovHora, 
		1, 
		isnull(@Preco, 0) + @DescVlr + @DescPerc,
		GETDATE(), 
		@CodMovimento)
	IF @@ERROR <> 0 
		BEGIN
			SET @Step = '1'
			CLOSE C1
			DEALLOCATE C1
			GOTO ERRO
		END

-- by Emerson Capreti - 10/09/2010 - Sequencia numerica de controle do ingresso
	if exists (select 1 from sysobjects where type = 'U' and name = 'tabControleSeqVenda')
		begin

			select @numseq = max(numseq)+1 from tabControleSeqVenda where codapresentacao = @CodApresentacao

			if @numseq is null
				select @numseq = 1
			
			-- by Jefferson Ferreira - 05/04/2011 - Alterado a Sequencia numerica do codigo do barra de controle do ingresso
			/*select @codbar = right('0000'+convert(varchar,@codapresentacao),4)
					+right(convert(varchar(8),@DatApresentacao,112),4)
					+right('0000'+replace(convert(varchar(5),@HorSessao),':',''),4)
					+right('00000'+convert(varchar(4),@numseq),5)
					+convert(char(1), @CodSetor)
					+right('00000'+convert(varchar(4),@CodTipBilhete),3)
					-- +right('00000'+convert(varchar(5), convert(int, (isnull(@Preco, 0) + @DescVlr + @DescPerc)*100)),5)			
            */

			select @codbar = right('00000'+convert(varchar,@codapresentacao),5)
					+convert(char(1), @CodSetor)
					+right(convert(varchar(8),@DatApresentacao,112),4)
					+right('0000'+replace(convert(varchar(5),@HorSessao),':',''),4)
					+right('00000'+convert(varchar(4),@CodTipBilhete),3)					
					+right('00000'+convert(varchar(4),@numseq),5)

			-- statusingresso -> L = liberado para passar na catraca
			insert into tabControleSeqVenda (codapresentacao, indice, numseq, codbar, statusingresso)
				values (@CodApresentacao, @Indice, @numseq, @codbar, 'L')

			IF @@ERROR <> 0 
				BEGIN
					SET @Step = 'gera codbar'
					CLOSE C1
					DEALLOCATE C1
					GOTO ERRO
				END

		end


  	-- Insere um histórico de cliente caso o @CodCliente nao seja NULL  		
	IF (NOT @CodCliente IS NULL)  AND  (@CodCliente <> 0)
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
			@CodTipLancamento, 
			@CodApresentacao, 
			@Indice)


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
		and cp.cd_bin = left(@NumeroBIN,6)

		inner join 
		ci_middleway..mw_base b 
		on b.id_base = ep.id_base 
		and b.ds_nome_base_sql = DB_NAME() 

		where a.codapresentacao = @CodApresentacao


	if @CodTipBilheteBIN = @CodTipBilhete 
		select @NumeroBINAux = @NumeroBIN 
	else
		select @NumeroBINAux = null
    
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
	VALUES (@Indice, 
		@CodVenda, 
		@NomObjeto, 
		@NomPeca, 
		@NomRedPeca, 
		@DatApresentacao, 
		@HorSessao, 
		@Elenco,
		@Autor, 
		@Diretor, 
		@NomRedSala, 
		@TipBilhete,  
		@Preco,
		@CodCaixa, 
		@Login, 
		@NomResPeca,
		@CenPeca,
		@NomSetor,
		@DtMovHora, 
		1, 
		@PerDesconto, 
		0, 
		@CodSala,
		@Id_Cartao_patrocinado,
		@NumeroBINAux)

	IF @@ERROR <> 0 
		BEGIN
			SET @Step = '3'
			CLOSE C1
			DEALLOCATE C1
			GOTO ERRO
		END

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
				  and TTBTL1.dtinivig     <= @DatMovimento
				  and TTBTL1.inativo       = 'A'
				  and TTLB1.codtiplct     = TTBTL1.codtiplct
				  and TTLB1.IcPercVlr     = 'P'
				  and TTLB1.icusolcto    != 'B'
				  and TTLB1.inativo       = 'A')
	and 	TTBTL.inativo        = 'A'

	IF @@ERROR <> 0
		BEGIN
			SET @Step = '5'
			CLOSE C1
			DEALLOCATE C1
			GOTO ERRO
		END


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
				  and TTBTL1.dtinivig     <= @DatMovimento
				  and TTBTL1.inativo       = 'A'
				  and TTLB1.codtiplct     = TTBTL1.codtiplct
				  and TTLB1.IcPercVlr     = 'V'
				  and TTLB1.icusolcto    != 'B'
				  and TTLB1.inativo       = 'A')
	and 	TTBTL.inativo        = 'A'

	IF @@ERROR <> 0
		BEGIN
			SET @Step = '6'
			CLOSE C1
			DEALLOCATE C1
			GOTO ERRO
		END

	fetch next from C1 into 	
		@CodTipBilhete,
		@CodApresentacao, 
		@Indice,
		@Preco,
		@NomObjeto,
		@CodSetor,	
		@NomSetor,
		@PerDesconto,
		@TipBilhete

END
CLOSE C1
DEALLOCATE C1


-- Atualiza a cadeira da sala para "Vendido" quando for uma Venda Normal
IF @CodTipLancamento <> 4
BEGIN
	UPDATE tabLugSala 
	SET CodVenda   = @CodVenda, 
		StaCadeira = 'V',
		CodUsuario = @CodUsuario
	WHERE 
		CodCaixa   = @CodCaixa 
		AND 	CodApresentacao = @CodApresentacao
		AND    (StaCadeira      = 'T' or StaCadeira = 'M')
END

-- Atualiza a cadeira da sala para "Vendido" quando for uma Venda de Complemento de Meia Entrada
IF @CodTipLancamento = 4
BEGIN
	UPDATE tabLugSala 
	SET CodVendaComplMeia   = @CodVenda, 
		StaCadeiraComplMeia = 'V',
		CodTipBilheteCOmplMeia = @CodTipBilhete,
		CodUsuario = @CodUsuario
	WHERE 
		CodCaixa   = @CodCaixaVendaAnt 
		AND 	CodApresentacao = @CodApresentacao
		AND    (StaCadeiraComplMeia  = 'M')
END
IF @@ERROR <> 0 
BEGIN
	SET @Step = '4'
	GOTO ERRO
END

-- Grava Log de Operação
SELECT @CodLog = (SELECT COALESCE(MAX(IdLogOperacao),0)+1 FROM tabLogOperacao)

INSERT INTO tabLogOperacao (IdLogOperacao, DatOperacao, CodUsuario, Operacao) 
VALUES (@CodLog, GETDATE(), @CodUsuario, 'Venda de Ingressos - espetáculo '+ @NomPeca + '  Dt.:' + convert(varchar(10),@DatMovimento,103)  + ' ' + @StrLog + ' Cod.Venda:' + @CodVenda)
IF @@ERROR <> 0 
BEGIN
	SET @Step = '7'
	GOTO ERRO
END

INSERT INTO tabLogOpeDetalhe (IdLogOperacao, Indice, NumLancamento, TipLancamento) 
SELECT @CodLog, Indice, @NumLancamento, 1 
FROM tabLugSala 
WHERE 
	CodCaixa = CASE WHEN @CodCaixaVendaAnt = 0 THEN @CodCaixa ELSE @CodCaixaVendaAnt END
	AND CodApresentacao = @CodApresentacao   
	AND (StaCadeira = 'T' or StaCadeira = 'M')
IF @@ERROR <> 0
BEGIN
	SET @Step = '8'
	GOTO ERRO
END

-- Atualiza o Movimento do caixa
UPDATE tabMovCaixa SET Saldo = COALESCE(SALDO+@ValPagto,0)
WHERE CodCaixa = @CodCaixa	AND StaMovimento = 'A'
IF @@ERROR <> 0
BEGIN
	SET @Step = '9'
	GOTO ERRO
END

--Atualiza os detalhes do Pagto
IF NOT @CodCliente IS NULL AND NOT @NumeroBIN IS NULL
	INSERT INTO tabDetPagamento (CodForPagto, NumLancamento, Agencia, Numero, DatValidade,Observacao )
	VALUES(@CodForPagto, @NumLancamento, @Agencia, @NumeroBIN, @DatValidade,@Observacao )

IF @@ERROR <> 0 
BEGIN
	SET @Step = '10'
	GOTO ERRO
END

SET NOCOUNT OFF
COMMIT TRANSACTION
SELECT 1 AS Resultado
RETURN

ERRO:
	ROLLBACK TRANSACTION
	INSERT INTO tabLogErro (DatErro, Numero, Descricao, Origem, Operacao, CodUsuario) 
		Values (GetDate(), @@ERROR, 'Erro Procedure',@Step,'SP_VEN_INS001',@CodUsuario)
	DELETE tabDetPagamento WHERE NumLancamento = @NumLancamento
  	SET NOCOUNT OFF
	SELECT 0 AS Resultado
	RETURN

