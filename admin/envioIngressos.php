<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 17, true)) {
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {
	
	$result = executeSQL($mainConnection, 'SELECT E.ID_ESTADO, E.DS_ESTADO, LE.QT_HORAS_LIMITE 
											FROM MW_ESTADO E
											LEFT JOIN MW_LIMITE_ENTREGA LE ON LE.ID_ESTADO = E.ID_ESTADO
											ORDER BY E.DS_ESTADO');
	
?>
<style type="text/css">
	.center{
		text-align: center;
	}	
</style>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	
	$('#app table').delegate('a', 'click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 href = $this.attr('href'),
			 id = 'codestado=' + $.getUrlVar('codestado', href),
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
						
						tr.find('td:not(.button):eq(0)').html($('#codestado').val());
						tr.find('td:not(.button):eq(1)').html($('#qtdhoras').val());
						
						$this.text('Editar').attr('href', pagina + '?action=edit&' + id);
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
			
			tr.find('td:not(.button):eq(0)').html('<input name="codestado" readonly type="text" class="readonly inputStyle" id="codestado" maxlength="100" value="' + values[0] + '" />');
			tr.find('td:not(.button):eq(1)').html('<input name="qtdhoras" type="text" class="inputStyle" id="qtdhoras" maxlength="100" value="' + values[1] + '" />');
			
			$this.text('Salvar').attr('href', pagina + '?action=update&' + id);
			
			setDatePickers();
		} 
	});
	$('tr:not(.ui-widget-header)').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	});	
	
	function validateFields() {
		var campos = $(':text'),
			 valido = true;
			 
		$.each(campos, function() {
			var $this = $(this);
			
			if ($this.val() == '') {
				$this.parent().addClass('ui-state-error');
				valido = false;
			} else {
				$this.parent().removeClass('ui-state-error');
			}
		});
		return valido;
	}
});
</script>
<h2>Envio de Ingressos</h2>
<form id="dados" name="dados" method="post">
	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header ">
				<th>Estado</th>
				<th class="center">Qtd Horas Limite Entrega</th>
				<th class="center">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$id = $rs['ID_ESTADO'];
			?>
			<tr>
				<td><?php echo utf8_encode2($rs['DS_ESTADO']); ?></td>
				<td class="center"><?php echo utf8_encode2($rs['QT_HORAS_LIMITE']); ?></td>
				<td class="button center"><a href="<?php echo $pagina; ?>?action=edit&codestado=<?php echo $id; ?>">Editar</a></td>
			</tr>
			<?php
				}
			?>
		</tbody>
	</table>
</form>
<?php
}

}
?>