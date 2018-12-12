<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 4, true)) {
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {
	
	$result = executeSQL($mainConnection, 'SELECT R.DS_REGIAO_GEOGRAFICA, CONVERT(VARCHAR(10), T.DT_INICIO_VIGENCIA, 103) DT_INICIO_VIGENCIA, T.VL_TAXA_FRETE, CASE WHEN CONVERT(CHAR(8), T.DT_INICIO_VIGENCIA, 112) >= CONVERT(CHAR(8), GETDATE(), 112) THEN 1 ELSE 0 END EDICAO FROM MW_TAXA_FRETE T INNER JOIN MW_REGIAO_GEOGRAFICA R ON R.ID_REGIAO_GEOGRAFICA = T.ID_REGIAO_GEOGRAFICA');
	
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	
	$('#app table').delegate('a', 'click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 href = $this.attr('href'),
			 id = 'regiao=' + $.getUrlVar('regiao', href) + '&data=' + $.getUrlVar('data', href),
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
						
						tr.find('td:not(.button):eq(0)').html($('#regiao option:selected').text());
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
		} else if (href.indexOf('?action=edit') != -1) {
			if(!hasNewLine()) return false;
			
			var values = new Array();
			
			tr.attr('id', 'newLine');
			
			$.each(tr.find('td:not(.button)'), function() {
				values.push($(this).text());
			});
			
			tr.find('td:not(.button):eq(0)').html('<?php echo comboRegiaoGeografica('regiao'); ?>');
			$('#regiao').find('option[text=' + values[0] + ']').attr('selected', 'selected');
			tr.find('td:not(.button):eq(1)').html('<input name="data" type="text" class="datePicker inputStyle" id="data" maxlength="10" value="' + values[1] + '" readonly>');
			tr.find('td:not(.button):eq(2)').html('R$ <input name="valor" type="text" class="number inputStyle" id="valor" maxlength="6" value="' + values[2].substr(3, values[2].length) + '" >');
			
			$this.text('Salvar').attr('href', pagina + '?action=update&' + id);
			
			setDatePickers();
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
									'<?php echo comboRegiaoGeografica('regiao'); ?>' +
								'</td>' +
								'<td><input name="data" type="text" class="datePicker inputStyle" id="data" maxlength="10" readonly></td>' +
								'<td>R$ <input name="valor" type="text" class="number inputStyle" id="valor" maxlength="6" ></td>' +
								'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
								'<td class="button"><a href="#delete">Apagar</a></td>' +
							'</tr>';
		$(newLine).appendTo('#app table tbody');
		setDatePickers();
	});
	
	function validateFields() {
		var regiao = $('#regiao'),
			 data = $('#data'),
			 valor = $('#valor'),
			 valido = true;
		if (regiao.val() == '') {
			regiao.parent().addClass('ui-state-error');
			valido = false;
		} else {
			regiao.parent().removeClass('ui-state-error');
		}
		if (data.val() == '') {
			data.parent().addClass('ui-state-error');
			valido = false;
		} else {
			data.parent().removeClass('ui-state-error');
		}
		if (valor.val() <= 0) {
			valor.parent().addClass('ui-state-error');
			valido = false;
		} else {
			valor.parent().removeClass('ui-state-error');
		}
		
		return valido;
	}
});
</script>
<h2>Servi&ccedil;os de Entrega</h2>
<form id="dados" name="dados" method="post">
	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header ">
				<th>Regi&atilde;o Geogr&aacute;fica</th>
				<th>Data de In&iacute;cio de Vig&ecirc;ncia</th>
				<th>Valor</th>
				<th colspan="2">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$regiao = $rs['DS_REGIAO_GEOGRAFICA'];
					$data = $rs['DT_INICIO_VIGENCIA'];
					$valor = $rs['VL_TAXA_FRETE'];
			?>
			<tr>
				<td><?php echo utf8_encode2($regiao); ?></td>
				<td><?php echo $data; ?></td>
				<td>R$ <?php echo str_replace('.', ',', $valor); ?></td>
				<?php if ($rs['EDICAO']) { ?>
				<td class="button"><a href="<?php echo $pagina; ?>?action=edit&regiao=<?php echo $regiao; ?>&data=<?php echo $data; ?>">Editar</a></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=delete&regiao=<?php echo $regiao; ?>&data=<?php echo $data; ?>">Apagar</a></td>
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

}
?>