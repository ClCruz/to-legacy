<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 600, true)) {

$pagina = basename(__FILE__);

if ($_GET['action']) {

	if ($_GET['action'] == 'combo_eventos') {
		$result = executeSQL($mainConnection, "SELECT DISTINCT E.ID_EVENTO, E.DS_EVENTO
												FROM MW_EVENTO E
												INNER JOIN MW_ACESSO_CONCEDIDO AC ON E.ID_BASE = AC.ID_BASE
												AND AC.ID_USUARIO = ? AND AC.CODPECA = E.CODPECA AND AC.ID_BASE = ?
												AND E.IN_ATIVO = '1'
												INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
												INNER JOIN MW_RESERVA R ON R.ID_APRESENTACAO = A.ID_APRESENTACAO
												ORDER BY DS_EVENTO",
			    							array($_SESSION['admin'], $_GET["local"]));

	    $combo = '<option value="-1">TODOS</option>';
	    while ($rs = fetchResult($result)) {
		$combo .= '<option value="' . $rs['ID_EVENTO'] . '"' .
			(($_GET["evento"] == $rs['ID_EVENTO']) ? ' selected' : '') .
			'>' . str_replace("'", "\'", utf8_encode2($rs['DS_EVENTO'])) . '</option>';
	    }

	    echo $combo;
	}

	elseif ($_GET['action'] == 'combo_setores') {
		$result = executeSQL($mainConnection, "SELECT DISTINCT R.DS_SETOR
												FROM MW_RESERVA R
												INNER JOIN MW_APRESENTACAO A ON R.ID_APRESENTACAO = A.ID_APRESENTACAO
												INNER JOIN MW_EVENTO E ON A.ID_EVENTO = E.ID_EVENTO
												INNER JOIN MW_ACESSO_CONCEDIDO AC ON E.ID_BASE = AC.ID_BASE
												AND AC.ID_USUARIO = ? AND AC.CODPECA = E.CODPECA AND AC.ID_BASE = ?
												AND E.IN_ATIVO = '1'
												WHERE (A.ID_EVENTO = ? OR ? = -1)
												ORDER BY DS_SETOR",
			    							array($_SESSION['admin'], $_GET["local"], $_GET["evento"], $_GET["evento"]));

	    $combo = '<option value="-1">TODOS</option>';
	    while ($rs = fetchResult($result)) {
		$combo .= '<option value="' . $rs['DS_SETOR'] . '"' .
			(($_GET["setor"] == $rs['DS_SETOR']) ? ' selected' : '') .
			'>' . str_replace("'", "\'", utf8_encode2($rs['DS_SETOR'])) . '</option>';
	    }

	    echo $combo;

	}
	die();
}

if($_GET['local'] AND $_GET['evento'] AND $_GET['setor']){
		
	$query = "DECLARE @ID_BASE INT = ?,
						@ID_EVENTO INT = ?,
						@DS_SETOR VARCHAR(100) = ?;

				SELECT
					A.DT_APRESENTACAO,
					A.HR_APRESENTACAO,
					R.DS_CADEIRA,
					R.DS_SETOR,
					R.DT_VALIDADE,
					R.ID_PEDIDO_VENDA
				FROM MW_RESERVA R
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
				WHERE E.ID_BASE = @ID_BASE
				AND (E.ID_EVENTO = @ID_EVENTO OR @ID_EVENTO = -1)
				AND (R.DS_SETOR = @DS_SETOR OR @DS_SETOR = '-1')";
	$params = array($_GET["local"], $_GET["evento"], $_GET["setor"]);
	$result = executeSQL($mainConnection, $query, $params);
	
}

?>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>'
	$('.button').button();
	
	$('table.ui-widget  tr:not(.ui-widget-header)').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	});
	
	$('#local').on('change', function(){
		if ($(this).val() != '') {
			$.ajax({
	          url: pagina + '?action=combo_eventos&local=' + $('#local').val() + '&evento=<?php echo $_GET["evento"]; ?>',
	          success: function(data) {
	            $('#evento').html(data).trigger('change');
	          }
	        });
		}
	}).trigger('change');

	$('#evento').on('change', function(){
		if ($(this).val() != '') {
			$.ajax({
	          url: pagina + '?action=combo_setores&local=' + $('#local').val() + '&evento=' + $('#evento').val() + '&setor=<?php echo $_GET["setor"]; ?>',
	          success: function(data) {
	            $('#setor').html(data);
	          }
	        });
		}
	});

	$('#btnRelatorio').on('click', function(){

		if ($('#local').val() == '') {
			$.dialog({text: 'Favor informar o local.'});
			return;
		}

		if ($('#evento').val() == '') {
			$.dialog({text: 'Favor informar o evento.'});
			return;
		}

		if ($('#setor').val() == '') {
			$.dialog({text: 'Favor informar o setor.'});
			return;
		}

		document.location = './?p=relatorioReservasTemporarias&local='+$('#local').val()+'&evento='+$('#evento').val()+'&setor='+$('#setor').val();
	});
});
</script>

<h2>Lugares Temporariamente Reservados</h2>
<form>
<table>
	<tr>
		<td>Local</td>
		<td><?php echo comboTeatroPorUsuario('local', $_SESSION['admin'], $_GET['local']); ?></td>
		<td>Evento</td>
		<td><select name="evento" class="inputStyle" id="evento"><option>Selecione um Local...</option></select></td>
		<td>Setor</td>
		<td><select name="setor" class="inputStyle" id="setor"><option>Selecione um Evento...</option></select></td>
	</tr>
	<tr>
		<td colspan="6" align="center">
			<input type="button" class="button" id="btnRelatorio" value="Buscar" />
		</td>
	</tr>
</table>
</form>
<br/>

<!-- Tabela de pedidos -->
<table class="ui-widget ui-widget-content" id="tabPedidos">
	<thead>
		<tr class="ui-widget-header">
            <th>Data da Apresentação</th>
            <th>Hora da Apresentação</th>
            <th>Cadeira</th>
			<th>Setor</th>
			<th>Validade</th>
			<th>Pedido</th>
		</tr>
	</thead>
	<tbody>
		<?php 
			if(isset($result) ){
				while($rs = fetchResult($result)) {
					?>
					<tr>
						<td><?php echo $rs['DT_APRESENTACAO']->format('d/m/Y'); ?></td>
						<td><?php echo $rs['HR_APRESENTACAO']; ?></td>
						<td><?php echo $rs['DS_CADEIRA']; ?></td>
						<td><?php echo $rs['DS_SETOR']; ?></td>
						<td><?php echo $rs['DT_VALIDADE']->format('d/m/Y H:i'); ?></td>
						<td><?php echo $rs['ID_PEDIDO_VENDA']; ?></td>
					</tr>
					<?php
				}
			}
		?>
	</tbody>
</table>

<?php
}
?>