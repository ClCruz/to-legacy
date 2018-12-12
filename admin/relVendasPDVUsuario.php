<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 380, true)) {

$pagina = basename(__FILE__);

if(isset($_GET["dt_inicial"]) && isset($_GET["dt_final"])){
		
	$strSql = "SELECT
					UI.DS_NOME,
					ISNULL(LE.DS_LOCAL_EVENTO, '" . utf8_decode('Não informado no cadastro de evento') . "') DS_LOCAL_EVENTO,
					ISNULL(E.DS_EVENTO, '" . utf8_decode('Não informado no cadastro de evento') . "') DS_EVENTO,
					MP.DS_MEIO_PAGAMENTO,
					SUM(IPV.QT_INGRESSOS) QT_INGRESSOS,
					SUM(IPV.VL_UNITARIO) TOTAL_VENDA,
					SUM(IPV.VL_TAXA_CONVENIENCIA) TOTAL_CONVENIENCIA
					
				FROM MW_PEDIDO_VENDA PV
					INNER JOIN MW_USUARIO UI
					ON UI.ID_USUARIO = PV.ID_USUARIO_CALLCENTER

					INNER JOIN MW_ITEM_PEDIDO_VENDA IPV
					ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA

					LEFT JOIN MW_APRESENTACAO A
					ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
					
					LEFT JOIN MW_EVENTO E
					ON E.ID_EVENTO = A.ID_EVENTO
					
					LEFT JOIN MW_LOCAL_EVENTO LE
					ON LE.ID_LOCAL_EVENTO = E.ID_LOCAL_EVENTO
					
					INNER JOIN MW_MEIO_PAGAMENTO MP
					ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO AND MP.IN_TRANSACAO_PDV = 1

				WHERE DT_HORA_CANCELAMENTO IS NULL
				AND DT_PEDIDO_VENDA BETWEEN CONVERT(DATETIME, ? + ' 00:00:00', 103) AND CONVERT(DATETIME, ? + ' 23:59:59', 103)
				AND PV.IN_SITUACAO = 'F'
				AND PV.ID_USUARIO_CALLCENTER = ?
				GROUP BY 
					UI.DS_NOME,
					LE.DS_LOCAL_EVENTO,
					E.DS_EVENTO,
					MP.DS_MEIO_PAGAMENTO
				ORDER BY LE.DS_LOCAL_EVENTO, UI.DS_NOME, E.DS_EVENTO, TOTAL_VENDA DESC";
	$params = array($_GET["dt_inicial"], $_GET["dt_final"], $_SESSION['admin']);
	$result = executeSQL($mainConnection, $strSql, $params);
	
	$query = "SELECT
					SUM(IPV.QT_INGRESSOS) QT_INGRESSOS,
					SUM(IPV.VL_UNITARIO) TOTAL_VENDA,
					SUM(IPV.VL_TAXA_CONVENIENCIA) TOTAL_CONVENIENCIA
					
				FROM MW_PEDIDO_VENDA PV
					INNER JOIN MW_USUARIO UI
					ON UI.ID_USUARIO = PV.ID_USUARIO_CALLCENTER

					INNER JOIN MW_ITEM_PEDIDO_VENDA IPV
					ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
					
					INNER JOIN MW_MEIO_PAGAMENTO MP
					ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO AND MP.IN_TRANSACAO_PDV = 1

				WHERE PV.DT_HORA_CANCELAMENTO IS NULL
				AND PV.DT_PEDIDO_VENDA BETWEEN CONVERT(DATETIME, ? + ' 00:00:00', 103) AND CONVERT(DATETIME, ? + ' 23:59:59', 103)
				AND PV.IN_SITUACAO = 'F'
				AND PV.ID_USUARIO_CALLCENTER = ?";
	$rs = executeSQL($mainConnection, $query, $params, true);
	$total['TOTAL_PEDIDO'] = $rs['TOTAL_VENDA'];
	$total['QUANTIDADE'] = $rs['QT_INGRESSOS'];
	$total['TOTAL_CONVENIENCIA'] = $rs['TOTAL_CONVENIENCIA'];
}

if (!$_GET['xls']) {
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
		
		document.location = '?p=' + pagina.replace('.php', '') + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+ $("#dt_final").val();
	});
	
	$('.excell').click(function(e) {
		e.preventDefault();
		
		document.location = '<?php echo ucfirst($pagina); ?>?' + $.serializeUrlVars() + '&xls=1';
	});
});
</script>
<style type="text/css">
.number {
	text-align: right;
}
.total {
	font-weight: bold;
}
</style>
<h2>PDV - Vendas do Usuário Logado</h2>

<p style="width:1000px;">Data Inicial <input type="text" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d/m/Y") ?>" class="datepicker" id="dt_inicial" name="dt_inicial" />
&nbsp;&nbsp;Data Final <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" />
&nbsp;&nbsp;<input type="submit" class="button" id="btnRelatorio" value="Buscar" />
<?php if(isset($result) && hasRows($result)) { ?>
&nbsp;&nbsp;<a class="button excell" href="#">Exportar Excel</a>
<?php } ?>
</p><br/>
<?php
} else {
	header("Content-type: application/vnd.ms-excel");
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=relVendasLocalUsuario.xls");
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style type="text/css">
.moeda {
	mso-number-format:"_\(\[$R$ -416\]* \#\,\#\#0\.00_\)\;_\(\[$R$ -416\]* \\\(\#\,\#\#0\.00\\\)\;_\(\[$R$ -416\]* \0022-\0022??_\)\;_\(\@_\)";
}
.number {
	text-align: right;
}
.total {
	font-weight: bold;
}
</style>
<h2>PDV - Vendas do Usuário Logado</h2>
<?php } ?>
<!-- Tabela de pedidos -->
<table class="ui-widget ui-widget-content" id="tabPedidos">
	<thead>
		<?php if ($_GET['xls']) { ?>
		<tr class="ui-widget-header">
			<th>Data Inicial:</th>
            <th><?php echo $_GET["dt_inicial"]; ?></th>
			<th>Data Final:</th>
			<th><?php echo $_GET["dt_final"]; ?></th>
		</tr>
		<?php } ?>
		<tr class="ui-widget-header">
			<th>Local</th>
            <th>Usuário</th>
            <th>Evento</th>
            <th>Forma de Pagamento.</th>
			<th class="number">Quantidade de Ingressos</th>
			<th class="number">Total dos Ingressos</th>
			<th class="number">Valor de Serviço</th>
		</tr>
	</thead>
	<tbody>
		<?php 
			if(isset($result) ){
				$lastLocal = '';
				$lastUsuario = '';
				$lastEvento = '';
				$somaTotal = $somaTotalUsuario = 0;
				$somaQuant = $somaQuantUsuario = 0;
				$somaServico = $somaServicoUsuario = 0;
				while($rs = fetchResult($result)) {
					// quebra por usuario
					if ($lastUsuario != $rs['DS_NOME'] and $lastUsuario != '') {
						?>
						<tr class="total">
							<td colspan="4" class="number">Sub-Total (usuário)</td>
							<td class="number"><?php echo $somaQuantUsuario; ?></td>
							<td class="number"><?php echo number_format($somaTotalUsuario, 2, ',', '.'); ?></td>
							<td class="number"><?php echo number_format($somaServicoUsuario, 2, ',', '.'); ?></td>
						</tr>
						<?php
						$somaTotalUsuario = 0;
						$somaQuantUsuario = 0;
						$somaServicoUsuario = 0;
					}
					// quebra por local
					if ($lastLocal != $rs['DS_LOCAL_EVENTO'] and $lastLocal != '') {
						?>
						<tr class="total">
							<td colspan="4" class="number">Sub-Total (local)</td>
							<td class="number"><?php echo $somaQuant; ?></td>
							<td class="number"><?php echo number_format($somaTotal, 2, ',', '.'); ?></td>
							<td class="number"><?php echo number_format($somaServico, 2, ',', '.'); ?></td>
						</tr>
						<?php
						$somaTotal = $somaTotalUsuario = 0;
						$somaQuant = $somaQuantUsuario = 0;
						$somaServico = $somaServicoUsuario = 0;
					}
					?>
					<tr>
						<td><?php echo ($rs['DS_LOCAL_EVENTO'] == $lastLocal ? '&nbsp;' : utf8_encode2($rs['DS_LOCAL_EVENTO'])); ?></td>
						<td><?php echo ($rs['DS_NOME'] == $lastUsuario ? '&nbsp;' : $rs['DS_NOME']); ?></td>
						<td><?php echo ($rs['DS_EVENTO'] == $lastEvento ? '&nbsp;' : utf8_encode2($rs['DS_EVENTO'])); ?></td>
						<td><?php echo utf8_encode2($rs['DS_MEIO_PAGAMENTO']) ?></td>
						<td class="number"><?php echo $rs['QT_INGRESSOS']; ?></td>
						<td class="number"><?php echo number_format($rs['TOTAL_VENDA'], 2, ',', '.'); ?></td>
						<td class="number"><?php echo number_format($rs['TOTAL_CONVENIENCIA'], 2, ',', '.'); ?></td>
					</tr>
					<?php
					$lastLocal = $rs['DS_LOCAL_EVENTO'];
					$lastUsuario = $rs['DS_NOME'];
					$lastEvento = $rs['DS_EVENTO'];

					$somaTotal += $rs['TOTAL_VENDA'];
					$somaQuant += $rs['QT_INGRESSOS'];
					$somaServico += $rs['TOTAL_CONVENIENCIA'];

					$somaTotalUsuario += $rs['TOTAL_VENDA'];
					$somaQuantUsuario += $rs['QT_INGRESSOS'];
					$somaServicoUsuario += $rs['TOTAL_CONVENIENCIA'];
				}
		?>
		<tr class="total">
			<td colspan="4" class="number">Sub-Total (usuário)</td>
			<td class="number"><?php echo $somaQuantUsuario; ?></td>
			<td class="number"><?php echo number_format($somaTotalUsuario, 2, ',', '.'); ?></td>
			<td class="number"><?php echo number_format($somaServicoUsuario, 2, ',', '.'); ?></td>
		</tr>
		<tr class="total">
			<td colspan="4" class="number">Sub-Total (local)</td>
			<td class="number"><?php echo $somaQuant; ?></td>
			<td class="number"><?php echo number_format($somaTotal, 2, ',', '.'); ?></td>
			<td class="number"><?php echo number_format($somaServico, 2, ',', '.'); ?></td>
		</tr>
		<tr class="total">
			<td colspan="4" class="number">Total geral</td>
			<td class="number"><?php echo $total['QUANTIDADE']; ?></td>
			<td class="number"><?php echo number_format($total['TOTAL_PEDIDO'], 2, ',', '.'); ?></td>
			<td class="number"><?php echo number_format($total['TOTAL_CONVENIENCIA'], 2, ',', '.'); ?></td>
		</tr>
		<?php 
			}
		?>
	</tbody>
</table>

<?php
}
?>