<?php
require_once('acessoLogadoDie.php');

require_once('../settings/functions.php');

$mainConnection = mainConnection();
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {
	
	$nomeBase = executeSQL($mainConnection, 'SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ?', array($_GET['teatro']), true);
	$query = 'SELECT
				 DISTINCT DS_PISO, T.CODTIPBILHETE
				 FROM APRESENTACAO RA
				 INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = RA.ID_APRESENTACAO
				 INNER JOIN ' . $nomeBase . '..TABTIPBILHETE T ON T.CODTIPBILHETE = RA.CODTIPBILHETE
				 WHERE A.ID_EVENTO = ?';
	$result = executeSQL($mainConnection, $query, array($_GET['teatro']));
	
	if (is_numeric($_GET['evento'])) {
		$query = 'SELECT DISTINCT DS_PISO FROM MW_APRESENTACAO WHERE ID_EVENTO = ?';
		$params = array($_GET['evento']);
		$result2 = executeSQL($mainConnection, $query, $params);
		
		$comboPiso = '<select id="piso" name="piso"><option>Selecione um piso</option>';
		while ($rs2 = fetchResult($result2)) {
			$comboPiso .= '<option>' . $rs2['DS_PISO'] . '</option>';
		}
		$comboPiso .= '</select>';
	}
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>'
	
	$('#app table').delegate('a', 'click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 href = $this.attr('href'),
			 id = 'evento=' + $.getUrlVar('evento', href) + '&codtipbilhete=' + $.getUrlVar('CODTIPBILHETE', href) + '&ds_piso=' + $.getUrlVar('DS_PISO', href);
			 tr = $this.closest('tr');
		
		if (href.indexOf('?action=add') != -1) {
			if (!validateFields()) return false;
			
			$.ajax({
				url: href,
				type: 'post',
				data: $('#dados').serialize(),
				success: function(data) {
					if (data.substr(0, 4) == 'true') {
						var id = $.serializeUrlVars(data);
						
						tr.find('td:not(.button):eq(0)').html($('#idEvento option:selected').text());
						tr.find('td:not(.button):eq(1)').html($('#data').val());
						tr.find('td:not(.button):eq(2)').html('R$ ' + $('#valor').val());
						
						$this.text('Editar').attr('href', pagina + '?action=edit&' + id);
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
							$.get(href, function(data) {
								if (data.replace(/^\s*/, "").replace(/\s*$/, "") == 'true') {
									tr.remove();
								} else {
									$.dialog({text: data});
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
								'<td>' +
									'<?php echo $comboPiso; ?>' +
								'</td>' +
								'<td>' +
									'<?php echo comboBilhetes2('codtipbilhete', $_GET['teatro']); ?>' +
								'</td>' +
								'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
								'<td class="button"><a href="#delete">Apagar</a></td>' +
							'</tr>';
		$(newLine).appendTo('#app table tbody');
	});
	
	$('#teatro, #evento, #setor').change(function() {
		$('#dados').submit();
	});
	
	function validateFields() {
		var bilhete = $('#codtipbilhete'),
			 piso = $('#piso'),
			 valido = true;
			 
		if (bilhete.val() == '') {
			bilhete.parent().addClass('ui-state-error');
			valido = false;
		} else {
			bilhete.parent().removeClass('ui-state-error');
		}
		if (piso.val() == '') {
			piso.parent().addClass('ui-state-error');
			valido = false;
		} else {
			piso.parent().removeClass('ui-state-error');
		}
		
		return valido;
	}
});
</script>
<h2>Restri&ccedil;&atilde;o de Bilhetes</h2>
<form id="dados" name="dados" method="get">
	<input type="hidden" name="p" value="<?php echo $_GET['p']; ?>">
	<p style="width:200px;">
		<?php echo comboTeatro('teatro', $_GET['teatro']); ?><?php echo comboEvento('evento', $_GET['teatro'], $_GET['evento']); ?>
	</p>
	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header ">
				<th>Piso</th>
				<th>Ingresso</th>
				<th colspan="2">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$id = 'evento=' . $_GET['evento'] . '&codtipbilhete=' . $rs['CODTIPBILHETE'] . '&ds_piso=' . urlencode(utf8_encode2($rs['DS_PISO']));
			?>
			<tr>
				<td><?php echo utf8_encode2($rs['DS_PISO']); ?></td>
				<td><?php echo comboBilhetes2('codtipbilhete', $_GET['teatro'], $rs['CODTIPBILHETE'], false); ?></td>
				<?php if ($rs['EDICAO']) { ?>
				<td class="button"><a href="<?php echo $pagina; ?>?action=delete&=<?php echo $id; ?>">Apagar</a></td>
				<td class="button">&nbsp;</td>
				<?php } else { ?>
				<td colspan="2">&nbsp;</td>
				<?php } ?>
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
?>