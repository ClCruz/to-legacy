<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 23, true)) {
	
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {

	// obtem o id_base
	$rs = executeSQL($mainConnection, 'SELECT E.ID_BASE
										FROM MW_EVENTO E
										INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA AND AC.ID_USUARIO = ?
										INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
										INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A.ID_APRESENTACAO
										WHERE ID_PACOTE = ?
										ORDER BY DS_EVENTO', array($_SESSION['admin'], $_GET['pacote']), true);

	$id_base = $rs['ID_BASE'];


	// listagem
	$result = executeSQL($mainConnection, 'SELECT MIN(P.ID_APRESENTACAO) AS ID_APRESENTACAO, DS_EVENTO, DT_APRESENTACAO, HR_APRESENTACAO
											FROM MW_PACOTE_APRESENTACAO P
											INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
											INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
											INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA AND AC.ID_USUARIO = ?
											WHERE ID_PACOTE = ?
											GROUP BY DS_EVENTO, DT_APRESENTACAO, HR_APRESENTACAO', array($_SESSION['admin'], $_GET['pacote']));


	// permissao para delete
	$result1 = executeSQL($mainConnection, "SELECT 1 FROM MW_PACOTE_RESERVA WHERE ID_PACOTE = ?", array($_GET['pacote']));

	$result2 = executeSQL($mainConnection, "SELECT 1 FROM MW_ITEM_PEDIDO_VENDA I
											INNER JOIN MW_PACOTE_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
											WHERE ID_PACOTE = ?", array($rs['ID_APRESENTACAO']));
	
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>'
	
	$('#app table').delegate('a', 'click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 href = $this.attr('href'),
			 id = 'apresentacao=' + $.getUrlVar('apresentacao', href),
			 tr = $this.closest('tr');
		
		if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
			if (!validateFields()) return false;
			
			$.ajax({
				url: href,
				type: 'post',
				data: $('#dados').serialize(),
				success: function(data) {
					if (data.substr(0, 4) == 'true') {
						var id = $.serializeUrlVars(data);
						
						tr.find('td:not(.button):eq(0)').html($('#evento option:selected').text());
						tr.find('td:not(.button):eq(1)').html($('#data').val());
						tr.find('td:not(.button):eq(2)').html($('#hora').val());
						
						$this.remove();
						tr.find('td.button a:last').attr('href', pagina + '?action=delete&' + id);
						tr.removeAttr('id');
					} else {
						$.dialog({text: data});
					}
				}
			});
		} else if (href == '#delete') {
			tr.remove();
		} else if (href.indexOf('?action=delete') != -1) {
			$.confirmDialog({
				text: 'Tem certeza que deseja apagar este registro?',
				uiOptions: {
					buttons: {
						'Sim': function() {
							$(this).dialog('close');
							$.ajax({
								url: href,
								type: 'post',
								data: $('#dados').serialize(),
								success: function(data) {
									if (data.replace(/^\s*/, "").replace(/\s*$/, "") == 'true') {
										tr.remove();
									} else {
										$.dialog({text: data});
									}
								}
							});
						}
					}
				}
			});
		}
	});
	
	$('#new').button().click(function(event) {
		event.preventDefault();
		
		if(!hasNewLine()) return false;
		
		var newLine = '<tr id="newLine">' +
							'<td>'+
								'<select name="evento" class="inputStyle" id="evento"><option>Carregando...</option></select>' +
							'</td>' +
							'<td>' +
								'<select name="data" class="inputStyle" id="data"><option>Selecione um evento...</option></select>' +
							'</td>' +
							'<td>' +
								'<select name="hora" class="inputStyle" id="hora"><option>Selecione uma data...</option></select>' +
							'</td>' +
							'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
							'<td class="button"><a href="#delete">Apagar</a></td>' +
						'</tr>';
		
		$(newLine).appendTo('#app table tbody');
		setCombos();
	});
	
	function validateFields() {
		var campos = $(':input:not(button)'),
			 valido = true;
			 
		$.each(campos, function() {
			var $this = $(this);
			
			if ($this.val() == '' || $this.val() == '-1') {
				$this.parent().addClass('ui-state-error');
				valido = false;
			} else {
				$this.parent().removeClass('ui-state-error');
			}
		});

		return valido;
	}

	function setCombos() {
		$.ajax({
			url: pagina + '?action=comboEvento',
			type: 'post',
			data: 'base=<?php echo $id_base?>&pacote=<?php echo $_GET['pacote']; ?>',
			success: function(data){
				$('#evento').html(data);
			}
		});

		$('#evento').on('change', function(){
			$.ajax({
				url: pagina + '?action=comboData',
				type: 'post',
				data: 'evento=' + $(this).val(),
				success: function(data){
					$('#data').html(data);
				}
			});
		});

		$('#data').on('change', function(){
			$.ajax({
				url: pagina + '?action=comboHora',
				type: 'post',
				data: 'evento=' + $('#evento').val() + '&data=' + $(this).val(),
				success: function(data){
					$('#hora').html(data);
				}
			});
		});
	}
	
	$('#pacote').change(function() {
		document.location = '?p=' + pagina.replace('.php', '') + '&pacote=' + $(this).val();
	});
});
</script>
<style>
	.datePicker {
		width: 80px;
	}

	<?php if (hasRows($result1) or hasRows($result2)) { ?>
	#new, .button {
		display: none;
	}
	<?php } ?>
</style>
<h2>Apresentações do Pacote</h2>
<form id="dados" name="dados" method="post">
<p style="width:200px;"><?php echo comboPacote('pacote', $_SESSION['admin'], $_GET['pacote']); ?></p>
	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header ">
				<th>Evento</th>
				<th>Data</th>
				<th>Hora</th>
				<th colspan="2">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$apresentacao = $rs['ID_APRESENTACAO'];
                    $nome = $rs['DS_EVENTO'];
                    $data = $rs['DT_APRESENTACAO']->format('d/m/Y');
                    $hora = $rs['HR_APRESENTACAO'];
			?>
			<tr>
				<td class="descricao"><?php echo utf8_encode2($nome); ?></td>
				<td><?php echo $data; ?></td>
				<td><?php echo $hora; ?></td>
				<td class="button">&nbsp;</td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=delete&apresentacao=<?php echo $apresentacao; ?>">Apagar</td>
			</tr>
			<?php
				}
			?>
		</tbody>
	</table>
	<a id="new" href="#new">Novo</a>
</form>
<?php
}

}
?>