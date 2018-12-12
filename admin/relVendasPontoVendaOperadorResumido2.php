<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if ($_GET['xls']) {
	header("Content-type: application/vnd.ms-excel");
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=relVendasPontoVendaOperador.xls");
	?><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><?php
}

if (acessoPermitido($mainConnection, $_SESSION['admin'], 350, true)) {

    require_once('../settings/Paginator.php');

    $pagina = basename(__FILE__);

    if (isset($_GET["dt_inicial"]) && isset($_GET["dt_final"]) && isset($_GET["local"]) && isset($_GET["evento"])) {

    $id_base = $_GET["local"];
	$conn = getConnection($id_base);

	//relatório
	$strSql = " declare @CodTipBilhete	int,
			    @TipBilhete		varchar(20),
			    @DatMovimento	datetime,
			    @NomSetor		varchar(20),
			    @Indice		int,
			    @Preco		money,
			    @VlrAgregados	money,
			    @OUTROSVALORES	money,
			    @ds_canal_venda	varchar(20),
			    @DtIniApr		varchar(8),
			    @DtFimApr		varchar(8),
			    @codPeca		int,
			    @descrcaixa		varchar(50),
			    @nomusuario		varchar(30),
                @CodApresentacao    int,
			    @id_base		int,
			    @id_usuario		int,
			    @id_canal_venda	int

	    set @codPeca = ?
	    set @id_base = ?
	    set @DtIniApr = ?
	    set @DtFimApr = ?
	    set @id_usuario = ?
	    set @id_canal_venda = ?

	    set nocount on

	    SELECT
			ci_middleway..mw_evento.ds_evento,
		    tabLugSala.CodTipBilhete,
		    tabTipBilhete.TipBilhete,
		    tabforpagamento.ForPagto,
		    tabLancamento.DatMovimento,
		    tabSetor.NomSetor,
		    tabforpagamento.tipcaixa,
		    tabLugSala.Indice,
            tabLugSala.CodApresentacao,
		    tabLancamento.ValPagto as Preco2,
		    tabLancamento.ValPagto as Preco,
		    ci_middleway..mw_canal_venda.ds_canal_venda,
		    tabCaixa.descrcaixa,
		    tabUsuario.nomusuario,
		    sum(isnull(tabIngressoAgregados.valor,0))  as VlrAgregados,
		    0 AS OUTROSVALORES,
		    1 AS CONTABILIZAR

	    INTO #TMP_RESUMO
	    FROM
		    tabLugSala
		    INNER JOIN
		    tabTipBilhete
			    ON  tabLugSala.CodTipBilhete 	    = tabTipBilhete.CodTipBilhete
		    INNER JOIN
		    tabSalDetalhe
			    ON  tabLugSala.Indice 		    = tabSalDetalhe.Indice
		    INNER JOIN
		    tabSetor
			    ON  tabSalDetalhe.CodSala           = tabSetor.CodSala
			    AND tabSalDetalhe.CodSetor 	    = tabSetor.CodSetor
		    INNER JOIN
		    tabApresentacao
			    ON  tabLugSala.CodApresentacao      = tabApresentacao.CodApresentacao
		    INNER JOIN
			    tabSala
			    ON tabApresentacao.CodSala		   = tabSala.CodSala
			INNER JOIN
				ci_middleway..mw_evento
				ON ci_middleway..mw_evento.codpeca	= tabApresentacao.codpeca
				AND ci_middleway..mw_evento.id_base = @id_base
			INNER JOIN
				ci_middleway..mw_acesso_concedido
				ON ci_middleway..mw_acesso_concedido.codpeca		= ci_middleway..mw_evento.codpeca
				AND ci_middleway..mw_acesso_concedido.id_base 		= ci_middleway..mw_evento.id_base
				AND ci_middleway..mw_acesso_concedido.id_usuario	= @id_usuario
		    INNER JOIN
		    tabLancamento
			    ON  tabTipBilhete.CodTipBilhete     = tabLancamento.CodTipBilhete
			    AND tabSalDetalhe.Indice            = tabLancamento.Indice
			    AND tabApresentacao.CodApresentacao = tabLancamento.CodApresentacao
			    AND tabLancamento.CodTipLancamento  = 1
		    INNER JOIN
		    tabforpagamento
			    ON tabforpagamento.CodForPagto = tabLancamento.CodForPagto
		    LEFT JOIN
		    tabIngressoAgregados
			    ON  tabIngressoAgregados.codvenda   = tabLugSala.codvenda
			    and tabIngressoAgregados.indice     = tabLugSala.indice
		    INNER JOIN
			    tabCaixa
				    ON	tabLancamento.codCaixa	   = tabCaixa.codCaixa
		    LEFT JOIN
		    ci_middleway..mw_canal_venda
			    ON ci_middleway..mw_canal_venda.id_canal_venda = tabCaixa.id_canal_venda
		    INNER JOIN tabUsuario ON tabLancamento.codUsuario = tabUsuario.codUsuario
	    WHERE
		    (tabLugSala.CodVenda IS NOT NULL)
	    AND 	(convert(varchar(8), tabLancamento.DatVenda,112) between @DtIniApr and @DtFimApr)
	    and	(tabApresentacao.codpeca = convert(varchar(6),@codPeca) or convert(varchar(6),@codPeca) is null)
	    AND (ci_middleway..mw_canal_venda.id_canal_venda = @id_canal_venda or @id_canal_venda is null)
	    AND	not exists (Select 1 from tabLancamento bb
				    where tabLancamento.numlancamento = bb.numlancamento
				      and tabLancamento.codtipbilhete = bb.codtipbilhete
				      and bb.codtiplancamento = 2
				      and tabLancamento.codapresentacao = bb.codapresentacao
				      and tabLancamento.indice          = bb.indice)
	    and tabLancamento.ValPagto > 0
	    GROUP BY
			ci_middleway..mw_evento.ds_evento,
		    tabLugSala.CodTipBilhete,
		    tabTipBilhete.TipBilhete,
		    tabforpagamento.ForPagto,
		    tabLancamento.DatMovimento,
		    tabSetor.NomSetor,
		    tabLugSala.Indice,
            tabLugSala.CodApresentacao,
		    tabLancamento.ValPagto,
		    tabforpagamento.tipcaixa,
		    ci_middleway..mw_canal_venda.ds_canal_venda,
		    tabCaixa.descrcaixa,
		    tabUsuario.nomusuario
		    
		INSERT INTO #TMP_RESUMO
		SELECT
			ci_middleway..mw_evento.ds_evento,
		    tabLugSala.CodTipBilheteComplMeia as CodTipBilhete,
		    tabTipBilhete.TipBilhete,
		    tabforpagamento.ForPagto,
		    tabLancamento.DatMovimento,
		    tabSetor.NomSetor,
		    tabforpagamento.tipcaixa,
		    tabLugSala.Indice,
            tabLugSala.CodApresentacao,
		    tabLancamento.ValPagto as Preco2,
		    tabLancamento.ValPagto as Preco,
		    ci_middleway..mw_canal_venda.ds_canal_venda,
		    tabCaixa.descrcaixa,
		    tabUsuario.nomusuario,
		    sum(isnull(tabIngressoAgregados.valor,0))  as VlrAgregados,
		    0 AS OUTROSVALORES,
		    0 AS CONTABILIZAR

	    FROM
		    tabLugSala
		    INNER JOIN
		    tabTipBilhete
			    ON  tabLugSala.CodTipBilheteComplMeia 	    = tabTipBilhete.CodTipBilhete
		    INNER JOIN
		    tabSalDetalhe
			    ON  tabLugSala.Indice 		    = tabSalDetalhe.Indice
		    INNER JOIN
		    tabSetor
			    ON  tabSalDetalhe.CodSala           = tabSetor.CodSala
			    AND tabSalDetalhe.CodSetor 	    = tabSetor.CodSetor
		    INNER JOIN
		    tabApresentacao
			    ON  tabLugSala.CodApresentacao      = tabApresentacao.CodApresentacao
		    INNER JOIN
			    tabSala
			    ON tabApresentacao.CodSala		   = tabSala.CodSala
			INNER JOIN
				ci_middleway..mw_evento
				ON ci_middleway..mw_evento.codpeca	= tabApresentacao.codpeca
				AND ci_middleway..mw_evento.id_base = @id_base
			INNER JOIN
				ci_middleway..mw_acesso_concedido
				ON ci_middleway..mw_acesso_concedido.codpeca		= ci_middleway..mw_evento.codpeca
				AND ci_middleway..mw_acesso_concedido.id_base 		= ci_middleway..mw_evento.id_base
				AND ci_middleway..mw_acesso_concedido.id_usuario	= @id_usuario
		    INNER JOIN
		    tabLancamento
			    ON  tabTipBilhete.CodTipBilhete     = tabLancamento.CodTipBilhete
			    AND tabSalDetalhe.Indice            = tabLancamento.Indice
			    AND tabApresentacao.CodApresentacao = tabLancamento.CodApresentacao
			    AND tabLancamento.CodTipLancamento  = 4
		    INNER JOIN
		    tabforpagamento
			    ON tabforpagamento.CodForPagto = tabLancamento.CodForPagto
		    LEFT JOIN
		    tabIngressoAgregados
			    ON  tabIngressoAgregados.codvenda   = tabLugSala.codvenda
			    and tabIngressoAgregados.indice     = tabLugSala.indice
		    INNER JOIN
			    tabCaixa
				    ON	tabLancamento.codCaixa	   = tabCaixa.codCaixa
		    LEFT JOIN
		    ci_middleway..mw_canal_venda
			    ON ci_middleway..mw_canal_venda.id_canal_venda = tabCaixa.id_canal_venda
		    INNER JOIN tabUsuario ON tabLancamento.codUsuario = tabUsuario.codUsuario
	    WHERE
		    (tabLugSala.CodVenda IS NOT NULL)
	    AND 	(convert(varchar(8), tabLancamento.DatVenda,112) between @DtIniApr and @DtFimApr)
	    and	(tabApresentacao.codpeca = convert(varchar(6),@codPeca) or convert(varchar(6),@codPeca) is null)
	    AND (ci_middleway..mw_canal_venda.id_canal_venda = @id_canal_venda or @id_canal_venda is null)
	    AND	not exists (Select 1 from tabLancamento bb
				    where tabLancamento.numlancamento = bb.numlancamento
				      and tabLancamento.codtipbilhete = bb.codtipbilhete
				      and bb.codtiplancamento = 2
				      and tabLancamento.codapresentacao = bb.codapresentacao
				      and tabLancamento.indice          = bb.indice)
	    and tabLancamento.ValPagto > 0
	    GROUP BY
			ci_middleway..mw_evento.ds_evento,
		    tabLugSala.CodTipBilheteComplMeia,
		    tabTipBilhete.TipBilhete,
		    tabforpagamento.ForPagto,
		    tabLancamento.DatMovimento,
		    tabSetor.NomSetor,
		    tabLugSala.Indice,
            tabLugSala.CodApresentacao,
		    tabLancamento.ValPagto,
		    tabforpagamento.tipcaixa,
		    ci_middleway..mw_canal_venda.ds_canal_venda,
		    tabCaixa.descrcaixa,
		    tabUsuario.nomusuario


	    declare C1 cursor for
		    SELECT
			    CodTipBilhete,
			    TipBilhete,
			    DatMovimento,
			    NomSetor,
			    Indice,
                CodApresentacao,
			    Preco,
			    VlrAgregados,
			    OUTROSVALORES,
			    ds_canal_venda,
			    descrcaixa,
			    nomusuario

		    from #TMP_RESUMO


	    open C1

	    fetch next from C1 into
		    @CodTipBilhete,
		    @TipBilhete,
		    @DatMovimento,
		    @NomSetor,
		    @Indice,
            @CodApresentacao,
		    @Preco,
		    @VlrAgregados,
		    @OUTROSVALORES,
		    @ds_canal_venda,
		    @descrcaixa,
		    @nomusuario


	    while @@fetch_Status = 0
	    BEGIN
		    Select
			    @OutrosValores = (@Preco - @VlrAgregados) * case TTLB.icdebcre when 'D' then (isnull(TTBTL.valor,0)/100) else (isnull(TTBTL.valor,0)/100) * -1 end
		    FROM
			    tabTipBilhTipLcto	TTBTL
		    INNER JOIN
			    tabTipLanctoBilh	TTLB
			    ON  TTLB.codtiplct  = TTBTL.codtiplct
			    and TTLB.icpercvlr  = 'P'
			    and TTLB.icusolcto != 'C'
			    and TTLB.inativo    = 'A'
		    WHERE
			    TTBTL.codtipbilhete = @codtipbilhete
		    and	TTBTL.dtinivig      = (Select max(TTBTL1.dtinivig)
					     from tabTipBilhTipLcto  TTBTL1,
						  tabTipLanctoBilh   TTLB1
					    where TTBTL1.codtipbilhete = TTBTL.codtipbilhete
					      and TTBTL1.codtiplct     = TTBTL.codtiplct
					      and TTBTL1.dtinivig     <= @DatMovimento
					      and TTBTL1.inativo       = 'A'
					      and TTLB1.codtiplct     = TTBTL1.codtiplct
					      and TTLB1.IcPercVlr     = 'P'
					      and TTLB1.icusolcto    != 'C'
					      and TTLB1.inativo       = 'A')
		    and 	TTBTL.inativo        = 'A'


		    Select
			    @OutrosValores = @OutrosValores + (case TTLB.icdebcre when 'D' then isnull(TTBTL.valor,0) else isnull(TTBTL.valor,0) * -1 end)
		    FROM
			    tabTipBilhTipLcto	TTBTL
		    INNER JOIN
			    tabTipLanctoBilh	TTLB
			    ON  TTLB.codtiplct  = TTBTL.codtiplct
			    and TTLB.icpercvlr  = 'V'
			    and TTLB.icusolcto != 'C'
			    and TTLB.inativo    = 'A'
		    WHERE
			    TTBTL.codtipbilhete = @codtipbilhete
		    and	TTBTL.dtinivig      = (Select max(TTBTL1.dtinivig)
					     from tabTipBilhTipLcto  TTBTL1,
						  tabTipLanctoBilh   TTLB1
					    where TTBTL1.codtipbilhete = TTBTL.codtipbilhete
					      and TTBTL1.codtiplct     = TTBTL.codtiplct
					      and TTBTL1.dtinivig     <= @DatMovimento
					      and TTBTL1.inativo       = 'A'
					      and TTLB1.codtiplct     = TTBTL1.codtiplct
					      and TTLB1.IcPercVlr     = 'V'
					      and TTLB1.icusolcto    != 'C'
					      and TTLB1.inativo       = 'A')
		    and 	TTBTL.inativo        = 'A'


		    Update #TMP_RESUMO
		    Set	Preco = @Preco - @VlrAgregados + @OutrosValores
		    ,	OutrosValores = @OutrosValores

		    where	Indice = @Indice and
                                CodApresentacao = @CodApresentacao


		    fetch next from C1 into
			    @CodTipBilhete,
			    @TipBilhete,
			    @DatMovimento,
			    @NomSetor,
			    @Indice,
                @CodApresentacao,
			    @Preco,
			    @VlrAgregados,
			    @OUTROSVALORES,
			    @ds_canal_venda,
			    @descrcaixa,
			    @nomusuario
	    END

	    Close C1
	    Deallocate C1

	    Select
			ds_evento,
		    isnull(ds_canal_venda, 'Forma n&atilde;o cadastrada') ds_canal_venda,
		    isnull(descrcaixa, 'N&atilde;o Informado') descrcaixa,
		    nomusuario,
		    ForPagto,
		    count(1) as qtd,
		    sum(preco) as val,
		    contabilizar
	    from
		    #TMP_RESUMO
	    group by
			ds_evento,
		    isnull(ds_canal_venda, 'Forma n&atilde;o cadastrada'),
		    isnull(descrcaixa, 'N&atilde;o Informado'),
		    nomusuario,
		    ForPagto,
		    contabilizar
	    order by ds_evento, ds_canal_venda, descrcaixa, nomusuario, ForPagto, qtd, val

	    DROP TABLE #TMP_RESUMO";

	$dtInicial = explode('/', $_GET['dt_inicial']);
	$dtInicial = $dtInicial[2] . $dtInicial[1] . $dtInicial[0];
	$dtFinal = explode('/', $_GET['dt_final']);
	$dtFinal = $dtFinal[2] . $dtFinal[1] . $dtFinal[0];

	$codPeca = $_GET['evento'] == 'TODOS' ? null : $_GET['evento'];
	$canal = $_GET['canal'] == 'TODOS' ? null : $_GET['canal'];

	$params = array($codPeca, $id_base, $dtInicial, $dtFinal, $_SESSION['admin'], $canal);
	$result = executeSQL($conn, $strSql, $params);
    }
?>
    <script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script>
        $(function() {
	    	var pagina = '<?php echo $pagina; ?>'
	    	$('.button').button();
	    	//$(".datepicker").datepicker();
	    	$('input.datepicker').datepicker({
	    	    changeMonth: true,
	    	    changeYear: true,
	    	    onSelect: function(date, e) {
		    		if ($(this).is('#dt_inicial')) {
		    		    $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
		    		}
	    	    }
	    	}).datepicker('option', $.datepicker.regional['pt-BR']);
	    	$('tr:not(.ui-widget-header)').hover(function() {
	    	    $(this).addClass('ui-state-hover');
	    	}, function() {
	    	    $(this).removeClass('ui-state-hover');
	    	});

	    	$("#btnRelatorio").click(function(){
	    	    var data1 = $('#dt_inicial').val().split('/'),
	    	    data2 = $('#dt_final').val().split('/');

	    	    data1 = Number(data1[2] + data1[1] + data1[0]);
	    	    data2 = Number(data2[2] + data2[1] + data2[0]);

	    	    if (data1 > data2) {
		    		$.dialog({title:'Alerta...', text:'A data inicial não pode ser maior que a final.'});
		    		return false;
	    	    }

	    	    if (($('#local').val() == '' && $('#evento').val() == '')
		    		||
		    		($('#local').val() == '' && $('#evento').val() != '')) {
		    		$.dialog({title:'Alerta...', text:'Você deve selecionar um local e um evento antes de continuar.'});
		    		return false;
	    	    }

	    	    if ($('#evento').val() == '') {
		    		document.location = '?p=' + pagina.replace('.php', '') +
		    		    '&dt_inicial=' + $("#dt_inicial").val() +
		    		    '&dt_final='+ $("#dt_final").val() +
		    		    '&local='+ $("#local").val() +
		    		    '&evento='+ $("#evento").val() +
		    		    '&canal='+ $("#canal").val() +
		    		    '&eventoNome='+ $("#evento option:selected").text();
	    	    } else {
		    		document.location = 'esperaProcesso.php?redirect=' + escape('./?p=' + pagina.replace('.php', '') +
		    		    '&dt_inicial=' + $("#dt_inicial").val() +
		    		    '&dt_final='+ $("#dt_final").val() +
		    		    '&local='+ $("#local").val() +
		    		    '&evento='+ $("#evento").val() +
		    		    '&canal='+ $("#canal").val() +
		    		    '&eventoNome='+ $("#evento option:selected").text());
	    	    }
	    	});

	    	$('#local').change(function() {
	    	    if ($('#evento').val() != '') {
	    			$('#evento').val('');
	    	    }
	    	    $("#btnRelatorio").click();
	    	});

	    	if ($('#evento option').length > 2) {
	    		$('#evento option:first').after("<option value='TODOS'>&lt; TODOS OS EVENTOS &gt;</option>");
	    	}

	    	if ($.getUrlVar('evento') == 'TODOS') {
				$('#evento').val('TODOS');
	    	}

	    	if ($('#canal option').length > 2) {
	    		$('#canal option:first').after("<option value='TODOS'>&lt; TODOS OS CANAIS DE VENDA &gt;</option>");
	    	}

	    	if ($.getUrlVar('canal') == 'TODOS') {
				$('#canal').val('TODOS');
	    	}

	    	$('.excell').click(function(e) {
	    	    e.preventDefault();

	    	    document.location = '<?php echo $pagina; ?>?' + $.serializeUrlVars() + '&xls=1';
	    	});
        });
    </script>
    <style type="text/css">
        #paginacao{
    	width: 100%;
    	text-align: center;
    	margin-top: 10px;
        }
        .number {
    	text-align: right;
        }
        .total {
    	font-weight: bold;
        }
    </style>
    <h2>Relatório Canais de Venda Resumido (Por Forma de Pagamento)</h2>

    <?php if ($_GET['xls']) { ?>
    	<table>
    		<tr>
    			<td>Data Inicial da Venda</td>
    			<td><?php echo $_GET["dt_inicial"]; ?></td>
	        	<td>Data Final da Venda</td>
	        	<td><?php echo $_GET["dt_final"]; ?></td>
    		</tr>
    	</table>
	<?php } else { ?>
	    <p style="width:1000px;">Data Inicial da Venda <input type="text" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d/m/Y") ?>" class="datepicker" id="dt_inicial" name="dt_inicial" />
	        &nbsp;&nbsp;Data Final da Venda <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" />
	        &nbsp;&nbsp;<?php echo comboTeatroPorUsuario('local', $_SESSION['admin'], $_GET['local']); ?><br/>
	        &nbsp;&nbsp;<?php echo comboEventoPorUsuario('evento', $_GET['local'], $_SESSION['admin'], $_GET['evento']); ?>
	        &nbsp;&nbsp;<?php echo comboCanalVenda('canal', $_GET['canal']); ?>
	        &nbsp;&nbsp;<input type="submit" class="button" id="btnRelatorio" value="Buscar" />
	    <?php if (isset($result) && hasRows($result)) { ?>
	        &nbsp;&nbsp;<a class="button excell" href="#">Exportar Excel</a>
	    <?php } ?>
	    </p>
	<?php } ?>

<!-- Tabela de pedidos -->
<table class="ui-widget ui-widget-content" id="tabPedidos">
    <thead>
	<tr class="ui-widget-header">
	    <th>Canal de venda</th>
        <th>Nome do Ponto de Venda</th>
	    <th>Operador</th>
	    <th>Forma de Pagamento</th>
	    <th>Qtde Ingr</th>
	    <th>Total das Vendas</th>
	</tr>
    </thead>
    <tbody>
	<?php
	if ($result and $_GET['evento'] != '') {

		//geral
	    $somaTotal = 0;
	    $somaQuant = 0;
	    
	    //ponto
		$lastPonto = '';
	    $somaTotalPonto = 0;
	    $somaQuantPonto = 0;

	    //operador
	    $lastOperador = '';
	    $somaTotalOperador = 0;
	    $somaQuantOperador = 0;

	    //canal
		$lastCanal = '';
	    $somaTotalCanal = 0;
	    $somaQuantCanal = 0;
	    
	    //evento
		$lastEvento = '';
	    $somaTotalEvento = 0;
	    $somaQuantEvento = 0;

	    while ($rs = fetchResult($result)) {

	    	//quebras de acordo com a hierarquia das quebras, exemplo: se um ponto de venda sofrer quebra o operador e o tipo de ingresso tambem devem sofrer quebra
	    	if ($lastEvento != $rs['ds_evento'] and $lastEvento != '') {
	    		$lastCanal = '#quebra#';
	    	}
	    	if ($lastCanal != $rs['ds_canal_venda'] and $lastEvento != '') {
    			$lastPonto = '#quebra#';
    		}
	    	if ($lastPonto != $rs['descrcaixa'] and $lastEvento != '') {
    			$lastOperador = '#quebra#';
    		}

			// quebra por operador
			if ($lastOperador != $rs['nomusuario'] and $lastOperador != '') {
				?>
				<tr class="total">
		    	    <td colspan="4" class="number">Sub-Total (operador)</td>
		    	    <td class="number"><?php echo $somaQuantOperador; ?></td>
		    	    <td class="number"><?php echo number_format($somaTotalOperador, 2, ',', '.'); ?></td>
				</tr>
				<?php
			    $somaTotalOperador = 0;
			    $somaQuantOperador = 0;
			}

			// quebra por ponto
			if ($lastPonto != $rs['descrcaixa'] and $lastPonto != '') {
				?>
				<tr class="total">
		    	    <td colspan="4" class="number">Sub-Total (ponto)</td>
		    	    <td class="number"><?php echo $somaQuantPonto; ?></td>
		    	    <td class="number"><?php echo number_format($somaTotalPonto, 2, ',', '.'); ?></td>
				</tr>
				<?php
			    $somaTotalPonto = 0;
			    $somaQuantPonto = 0;
			}

			// quebra por canal
			if ($lastCanal != $rs['ds_canal_venda'] and $lastCanal != '') {
				?>
				<tr class="total">
		    	    <td colspan="4" class="number">Sub-Total (canal)</td>
		    	    <td class="number"><?php echo $somaQuantCanal; ?></td>
		    	    <td class="number"><?php echo number_format($somaTotalCanal, 2, ',', '.'); ?></td>
				</tr>
				<?php
			    $somaTotalCanal = 0;
			    $somaQuantCanal = 0;
			}

			// quebra por evento
			if ($lastEvento != $rs['ds_evento']) {
				if ($lastEvento != '') {
				?>
		    	<tr class="total">
		    	    <td colspan="4" class="number">Total geral do evento</td>
		    	    <td class="number"><?php echo $somaQuantEvento; ?></td>
		    	    <td class="number"><?php echo number_format($somaTotalEvento, 2, ',', '.'); ?></td>
		    	</tr>
		    	<?php } ?>

				<tr><th colspan="6">&nbsp;</td></th>
				<tr><th colspan="6">Evento: <?php echo utf8_encode2($rs['ds_evento']); ?></td></th>
				<tr><th colspan="6">&nbsp;</td></tr>
				<?php
			    $somaTotalEvento = 0;
			    $somaQuantEvento = 0;
			}




			// linhas
		?>
			<tr>
				<td><?php echo ($rs['ds_canal_venda'] == $lastCanal ? '&nbsp;' : utf8_encode2($rs['ds_canal_venda'])); ?></td>
				<td><?php echo ($rs['descrcaixa'] == $lastPonto ? '&nbsp;' : utf8_encode2($rs['descrcaixa'])); ?></td>
				<td><?php echo ($rs['nomusuario'] == $lastOperador ? '&nbsp;' : utf8_encode2($rs['nomusuario'])); ?></td>
				<td><?php echo utf8_encode2($rs['ForPagto']) ?></td>
				<td class="number"><?php echo $rs['qtd']; ?></td>
				<td class="number"><?php echo number_format($rs['val'], 2, ',', '.'); ?></td>
			</tr>
		<?php

			//geral
			$somaTotal += $rs['val'];
			$somaQuant += $rs['contabilizar'] ? $rs['qtd'] : 0;
		    
		    //ponto
			$lastPonto = $rs['descrcaixa'];
		    $somaTotalPonto += $rs['val'];
		    $somaQuantPonto += $rs['contabilizar'] ? $rs['qtd'] : 0;

		    //operador
		    $lastOperador = $rs['nomusuario'];
		    $somaTotalOperador += $rs['val'];
		    $somaQuantOperador += $rs['contabilizar'] ? $rs['qtd'] : 0;

		    //canal
			$lastCanal = $rs['ds_canal_venda'];
		    $somaTotalCanal += $rs['val'];
		    $somaQuantCanal += $rs['contabilizar'] ? $rs['qtd'] : 0;
		    
		    //evento
			$lastEvento = $rs['ds_evento'];
		    $somaTotalEvento += $rs['val'];
		    $somaQuantEvento += $rs['contabilizar'] ? $rs['qtd'] : 0;

	    }
	?>
    	<tr class="total">
    	    <td colspan="4" class="number">Sub-Total (operador)</td>
    	    <td class="number"><?php echo $somaQuantOperador; ?></td>
    	    <td class="number"><?php echo number_format($somaTotalOperador, 2, ',', '.'); ?></td>
    	</tr>
    	<tr class="total">
    	    <td colspan="4" class="number">Sub-Total (ponto)</td>
    	    <td class="number"><?php echo $somaQuantPonto; ?></td>
    	    <td class="number"><?php echo number_format($somaTotalPonto, 2, ',', '.'); ?></td>
    	</tr>
    	<tr class="total">
    	    <td colspan="4" class="number">Sub-Total (canal)</td>
    	    <td class="number"><?php echo $somaQuantCanal; ?></td>
    	    <td class="number"><?php echo number_format($somaTotalCanal, 2, ',', '.'); ?></td>
    	</tr>
    	<tr class="total">
    	    <td colspan="4" class="number">Total geral do evento</td>
    	    <td class="number"><?php echo $somaQuantEvento; ?></td>
    	    <td class="number"><?php echo number_format($somaTotalEvento, 2, ',', '.'); ?></td>
    	</tr>
    	<tr class="total">
    	    <td colspan="4" class="number">Total geral</td>
    	    <td class="number"><?php echo $somaQuant; ?></td>
    	    <td class="number"><?php echo number_format($somaTotal, 2, ',', '.'); ?></td>
    	</tr>
	<?php
	}
	?>
    </tbody>
</table>
<p style="text-align:right;">Importante: A qtde. de ingressos de "Complemento de Meia Entrada" não foram somados aos Totais de "Qtde. de Ingressos".</p>
<?php
    }
?>