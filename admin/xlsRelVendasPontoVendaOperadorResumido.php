<?php
header("Content-type: application/vnd.ms-excel");
header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=relVendasPontoVendaOperador.xls");
session_start();

require_once('../settings/functions.php');

$mainConnection = mainConnection();

if(isset($_GET["dt_inicial"]) && isset($_GET["dt_final"]) && isset($_GET["local"]) && isset($_GET["evento"])){

	acessoPermitidoEvento($_GET["local"], $_SESSION['admin'], $_GET["evento"], true);

	$conn = getConnection($_GET["local"]);
	
	$strSql = "select
					cv.ds_canal_venda,
					t.tipbilhete,
					sum(l.qtdbilhete) qtd,
					sum(l.valpagto) val
				from tablancamento l
				inner join tabcaixa c on l.codcaixa = c.codcaixa
				inner join ci_middleway..mw_canal_venda cv on c.id_canal_venda = cv.id_canal_venda
				inner join tabtipbilhete t on l.codtipbilhete = t.codtipbilhete
				inner join tabapresentacao a on l.codapresentacao = a.codapresentacao
				where a.codpeca = ?
					and l.datvenda between convert(datetime, ? + ' 00:00:00', 103) and convert(datetime, ? + ' 23:59:59', 103)
					and not exists (select 1
									from tablancamento l2
									where l2.numlancamento = l.numlancamento
										and l2.codtipbilhete = l.codtipbilhete
										and l2.codapresentacao = l.codapresentacao
										and l2.indice = l.indice
										and l2.codtiplancamento = 2)
				group by
					cv.ds_canal_venda,
					t.tipbilhete
				order by cv.ds_canal_venda, t.tipbilhete";
	$params = array($_GET['evento'], $_GET['dt_inicial'], $_GET['dt_final']);
	$result = executeSQL($conn, $strSql, $params);
	
	$query = "select
					sum(l.qtdbilhete) qtd,
					sum(l.valpagto) val
				from tablancamento l
				inner join tabapresentacao a on l.codapresentacao = a.codapresentacao
				where a.codpeca = ?
					and l.datvenda between convert(datetime, ? + ' 00:00:00', 103) and convert(datetime, ? + ' 23:59:59', 103)";
	$rs = executeSQL($conn, $query, $params, true);
	$total['TOTAL_PEDIDO'] = $rs['val'];
	$total['QUANTIDADE'] = $rs['qtd'];

}
?>
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
<h2>Relatório Canais de Venda (Por Data de Venda) Resumido</h2>
<table class="ui-widget ui-widget-content" id="tabPedidos">
	<thead>
		<tr class="ui-widget-header">
			<th>Data Inicial:</th>
            <th><?php echo $_GET["dt_inicial"]; ?></th>
			<th>Data Final:</th>
			<th><?php echo $_GET["dt_final"]; ?></th>
			<th>Evento:</th>
			<th><?php echo $_GET["eventoNome"]; ?></th>
		</tr>
		<tr class="ui-widget-header">
			<th>Canal de venda</th>
			<th>Tipo de Ingresso</th>
			<th>Quantidade de Ingressos</th>
			<th>Total das Vendas</th>
		</tr>
	</thead>
	<tbody>
		<?php 
			if(isset($result) ){
				$lastLocal = '';
				$somaTotal = 0;
				$somaQuant = 0;
				while($rs = fetchResult($result)) {
					if ($lastLocal != $rs['ds_canal_venda'] and $lastLocal != '') {
						?>
						<tr class="total">
							<td colspan="2" class="number">Sub-Total (canal)</td>
							<td class="number"><?php echo $somaQuant; ?></td>
							<td class="number"><?php echo number_format($somaTotal, 2, ',', '.'); ?></td>
						</tr>
						<?php
						$lastLocal = $rs['ds_canal_venda'];
						$somaTotal = $rs['val'];
						$somaQuant = $rs['qtd'];
						?>
						<tr>
							<td><?php echo utf8_encode2($rs['ds_canal_venda']); ?></td>
							<td><?php echo utf8_encode2($rs['tipbilhete']); ?></td>
							<td class="number"><?php echo $rs['qtd']; ?></td>
							<td class="number"><?php echo number_format($rs['val'], 2, ',', '.'); ?></td>
						</tr>
						<?php
					} elseif ($lastLocal != $rs['ds_canal_venda']) {
						?>
						<tr>
							<td><?php echo utf8_encode2($rs['ds_canal_venda']); ?></td>
							<td><?php echo utf8_encode2($rs['tipbilhete']); ?></td>
							<td class="number"><?php echo $rs['qtd']; ?></td>
							<td class="number"><?php echo number_format($rs['val'], 2, ',', '.'); ?></td>
						</tr>
						<?php
						$lastLocal = $rs['ds_canal_venda'];
						$somaTotal += $rs['val'];
						$somaQuant += $rs['qtd'];
					} else {
						?>
						<tr>
							<td>&nbsp;</td>
							<td><?php echo utf8_encode2($rs['tipbilhete']); ?></td>
							<td class="number"><?php echo $rs['qtd']; ?></td>
							<td class="number"><?php echo number_format($rs['val'], 2, ',', '.'); ?></td>
						</tr>
						<?php
						$somaTotal += $rs['val'];
						$somaQuant += $rs['qtd'];
					}
				}
		?>
		<tr class="total">
			<td colspan="2" class="number">Sub-Total (canal)</td>
			<td class="number"><?php echo $somaQuant; ?></td>
			<td class="number"><?php echo number_format($somaTotal, 2, ',', '.'); ?></td>
		</tr>
		<tr class="total">
			<td colspan="2" class="number">Total geral</td>
			<td class="number"><?php echo $total['QUANTIDADE']; ?></td>
			<td class="number"><?php echo number_format($total['TOTAL_PEDIDO'], 2, ',', '.'); ?></td>
		</tr>
		<?php 
			}
		?>
	</tbody>
</table>
<?php print_r(sqlErrors()); ?>