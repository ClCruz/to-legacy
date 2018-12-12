<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 290, true)) {
$pagina = basename(__FILE__);

	if (isset($_GET['action'])) {
		
		require('actions/'.$pagina);
		
	} else {

	$pagina = basename(__FILE__);

	$result = executeSQL($mainConnection, 'SELECT E.ID_EVENTO, DS_EVENTO, E.IN_VER_NO_BORDERO, 
											CONVERT(VARCHAR(10), MIN(DT_APRESENTACAO),103) AS DATA_INICIAL, 
											CONVERT(VARCHAR(10), MAX(DT_APRESENTACAO),103) AS DATA_FINAL 
											FROM MW_EVENTO  E LEFT JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
											WHERE E.ID_BASE = ?
											GROUP BY E.ID_EVENTO, DS_EVENTO, E.IN_VER_NO_BORDERO
											ORDER BY DS_EVENTO, E.ID_EVENTO', array($_GET['teatro']));

	$resultTeatros = executeSQL($mainConnection, 'SELECT ID_BASE, DS_NOME_TEATRO FROM MW_BASE WHERE IN_ATIVO = \'1\'');
	?>

	<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
	<script>
	$(function() {
		var pagina = '<?php echo $pagina; ?>';
		
		$('#app table').delegate('a', 'click', function(event) {
			event.preventDefault();
			
			var $this = $(this),
				 href = $this.attr('href'),
				 id = 'id_evento=' + $.getUrlVar('id_evento', href),
				 tr = $this.closest('tr');
			
			if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
				$.ajax({
					url: href,
					type: 'post',
					data: $('#dados').serialize(),
					success: function(data) {
						if (data.substr(0, 4) == 'true') {
							var id = $.serializeUrlVars(data),
								email = $.getUrlVar('email', data);
							
							tr.find('td:not(.button):eq(4)').html($('#ativo').is(':checked') ? 'sim' : 'n&atilde;o');
							
							$this.text('Editar').attr('href', pagina + '?action=edit&' + id);
							tr.find('td.button a:eq(1)').attr('href', pagina + '?action=reset&' + id);
							tr.removeAttr('id');
						} else {
							$.dialog({text: data});
						}
					}
				});
			} else if (href.indexOf('?action=edit') != -1) {
				if(!hasNewLine()) return false;
				
				var values = new Array();
				
				tr.attr('id', 'newLine');
				
				$.each(tr.find('td:not(.button)'), function() {
					values.push($(this).text());
				});
				
				tr.find('td:not(.button):eq(4)').html('<input name="ativo" type="checkbox" class="inputStyle" id="ativo" ' + (values[4] == 'sim' ? 'checked' : ''  )+ ' />');
				
				$this.text('Salvar').attr('href', pagina + '?action=update&' + id);
				
				setDatePickers();
			}
		});

		$('#teatro').change(function() {
			document.location = '?p=' + pagina.replace('.php', '') + '&teatro=' + $(this).val();
		});
		
		$('tr:not(.ui-widget-header)').hover(function() {
			$(this).addClass('ui-state-hover');
		}, function() {
			$(this).removeClass('ui-state-hover');
		});
	});
	</script>
	<h2>Visualização do Evento no Borderô</h2>
	<form id="dados" name="dados" method="post">
		<p style="width:200px;"><?php echo comboTeatro('teatro', $_GET['teatro']); ?></p>
		<table class="ui-widget ui-widget-content">
			<thead>
				<tr class="ui-widget-header">
					<th>ID</th>
					<th>Evento</th>
					<th>Data de In&iacute;cio</th>
					<th>Data de T&eacute;rmino</th>
					<th>Visualizar no Borderô</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php while($rs = fetchResult($result)) { ?>
				<tr>
					<td><?php echo $rs['ID_EVENTO']; ?></td>
					<td><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
					<td><?php echo $rs['DATA_INICIAL']; ?></td>
					<td><?php echo $rs['DATA_FINAL']; ?></td>
					<td><?php echo ($rs['IN_VER_NO_BORDERO'] ? 'sim' : 'não'); ?></td>
					<td class="button"><a href="<?php echo $pagina; ?>?action=edit&id_evento=<?php echo $rs['ID_EVENTO']; ?>">Editar</a></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</form>
	<?php
	}
}
?>