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

	$result = executeSQL($mainConnection, 'SELECT ID_PACOTE, P.ID_APRESENTACAO, DS_EVENTO, DT_INICIO_FASE1, DT_FIM_FASE1, DT_INICIO_FASE2, DT_FIM_FASE2, DT_INICIO_FASE3, DT_FIM_FASE3
											FROM MW_PACOTE P
											INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
											INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
											INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA AND AC.ID_USUARIO = ?
											WHERE E.ID_BASE = ?', array($_SESSION['admin'], $_GET['local']));
	
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
function setDatePickers() {
    $('input.datePicker').prop('readonly', true).datepicker({
        changeMonth: true,
        changeYear: true
    });
    $('input.datePicker').datepicker('option', $.datepicker.regional['pt-BR']);

    $('#dataInicio2, #dataFim2, #dataInicio3, #dataFim3').filter(function(){return $(this).val()==''}).prop('disabled', true);

	$('#dataInicio1').on('change', function() {
		$("#dataFim1").datepicker("option", "minDate", $(this).val()).trigger('change');
	});
	$('#dataFim1').on('change', function() {
		$("#dataInicio2").datepicker("option", "minDate", $(this).val()).trigger('change');
		if ($('#dataFim1').val() != '') $('#dataInicio2').prop('disabled', false);
	});
	$('#dataInicio2').on('change', function() {
		$("#dataFim2").datepicker("option", "minDate", $(this).val()).trigger('change');
		if ($('#dataInicio2').val() != '') $('#dataFim2').prop('disabled', false);
	});
	$('#dataFim2').on('change', function() {
		$("#dataInicio3").datepicker("option", "minDate", $(this).val()).trigger('change');
		if ($('#dataFim2').val() != '') $('#dataInicio3').prop('disabled', false);
	});
	$('#dataInicio3').on('change', function() {
		$("#dataFim3").datepicker("option", "minDate", $(this).val()).trigger('change');
		if ($('#dataInicio3').val() != '') $('#dataFim3').prop('disabled', false);
	});

	$('#dataInicio1').trigger('change');
}

$(function() {
	var pagina = '<?php echo $pagina; ?>'
	
	$('#app table').delegate('a', 'click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 href = $this.attr('href'),
			 id = 'pacote=' + $.getUrlVar('pacote', href),
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
						
						if ($('#apresentacao')[0]) {
							tr.find('td:not(.button):eq(0)').html($('#apresentacao option:selected').text());
							tr.find('td.button a:eq(1)').attr('href', pagina + '?action=redirect&url=' + encodeURIComponent('?p=pacoteApresentacoes&' + id));
						}

						tr.find('td:not(.button):eq(1)').html($('#dataInicio1').val());
						tr.find('td:not(.button):eq(3)').html($('#dataFim1').val());
						tr.find('td:not(.button):eq(4)').html($('#dataInicio2').val());
						tr.find('td:not(.button):eq(6)').html($('#dataFim2').val());
						tr.find('td:not(.button):eq(7)').html($('#dataInicio3').val());
						tr.find('td:not(.button):eq(9)').html($('#dataFim3').val());

						$.each(tr.find('td:not(.button)'), function() {
							$(this).text($(this).text() ? $(this).text() : ' - ');
						});
						
						$this.text('Editar').attr('href', pagina + '?action=edit&' + id);
						tr.find('td.button a:eq(1)').text('Apresentações');
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
			
			tr.find('td:not(.button):eq(1)').html('<input name="dataInicio1" type="text" class="inputStyle datePicker" id="dataInicio1" maxlength="10" value="' + values[1] + '" />');
			tr.find('td:not(.button):eq(3)').html('<input name="dataFim1" type="text" class="inputStyle datePicker" id="dataFim1" maxlength="10" value="' + values[3] + '" />');
			tr.find('td:not(.button):eq(4)').html('<input name="dataInicio2" type="text" class="inputStyle datePicker" id="dataInicio2" maxlength="10" value="' + values[4] + '" />');
			tr.find('td:not(.button):eq(6)').html('<input name="dataFim2" type="text" class="inputStyle datePicker" id="dataFim2" maxlength="10" value="' + values[6] + '" />');
			tr.find('td:not(.button):eq(7)').html('<input name="dataInicio3" type="text" class="inputStyle datePicker" id="dataInicio3" maxlength="10" value="' + values[7] + '" />');
			tr.find('td:not(.button):eq(9)').html('<input name="dataFim3" type="text" class="inputStyle datePicker" id="dataFim3" maxlength="10" value="' + values[9] + '" />');
			
			$this.text('Salvar').attr('href', pagina + '?action=update&' + id);
			tr.find('td.button a:eq(1)').text('');
			
			setDatePickers();
		} else if (href == '#delete') {
			tr.remove();
		} else if (href.indexOf('?action=redirect') != -1) {
			document.location = decodeURIComponent($.getUrlVar('url', href));
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
							'<td class="descricao">'+
								'<?php echo comboEventoPacotePorUsuario('apresentacao', $_GET['local'], $_SESSION['admin']); ?>' +
							'</td>' +
							'<td>'+
								'<input name="dataInicio1" type="text" class="inputStyle datePicker" id="dataInicio1" maxlength="10" />' +
							'</td>' +
							'<td>a</td>' +
							'<td>'+
								'<input name="dataFim1" type="text" class="inputStyle datePicker" id="dataFim1" maxlength="10" />' +
							'</td>' +
							'<td>'+
								'<input name="dataInicio2" type="text" class="inputStyle datePicker" id="dataInicio2" maxlength="10" />' +
							'</td>' +
							'<td>a</td>' +
							'<td>'+
								'<input name="dataFim2" type="text" class="inputStyle datePicker" id="dataFim2" maxlength="10" />' +
							'</td>' +
							'<td>'+
								'<input name="dataInicio3" type="text" class="inputStyle datePicker" id="dataInicio3" maxlength="10" />' +
							'</td>' +
							'<td>a</td>' +
							'<td>'+
								'<input name="dataFim3" type="text" class="inputStyle datePicker" id="dataFim3" maxlength="10" />' +
							'</td>' +
							'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
							'<td class="button"><a></a></td>' +
							'<td class="button"><a href="#delete">Apagar</a></td>' +
						'</tr>';
		
		$(newLine).appendTo('#app table tbody');
		setDatePickers();
	});
	
	function validateFields() {
		var campos = $('#newLine :input:not(#dataInicio2, #dataFim2, #dataInicio3, #dataFim3)'),
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

		if ($('#dataInicio2').val() != '' && $('#dataFim2').val() == '') {
			$('#dataFim2').parent().addClass('ui-state-error');
				valido = false;
		} else {
			$('#dataFim2').parent().removeClass('ui-state-error');
		}

		if ($('#dataInicio3').val() != '' && $('#dataFim3').val() == '') {
			$('#dataFim3').parent().addClass('ui-state-error');
				valido = false;
		} else {
			$('#dataFim3').parent().removeClass('ui-state-error');
		}

		if ($('#dataFim2').val() != '' && $('#dataInicio2').val() == '') {
			$('#dataInicio2').parent().addClass('ui-state-error');
				valido = false;
		} else {
			$('#dataInicio2').parent().removeClass('ui-state-error');
		}

		if ($('#dataFim3').val() != '' && $('#dataInicio3').val() == '') {
			$('#dataInicio3').parent().addClass('ui-state-error');
				valido = false;
		} else {
			$('#dataInicio3').parent().removeClass('ui-state-error');
		}

		return valido;
	}
	
	$('#local').change(function() {
		document.location = '?p=' + pagina.replace('.php', '') + '&local=' + $(this).val();
	});

	$('.ui-widget tr:not(.ui-widget-header)').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
	  	$(this).removeClass('ui-state-hover');
	});
});
</script>
<style>
	.datePicker {
		width: 80px;
	}

	table.ui-widget.ui-widget-content tbody tr td {
		text-align: center;
	}

	table.ui-widget.ui-widget-content tbody tr td.descricao {
		text-align: left;
	}
</style>
<h2>Pacotes</h2>
<form id="dados" name="dados" method="post">
	<table class="ui-widget ui-widget-content">
		<p style="width:200px;"><?php echo comboTeatroPorUsuario('local', $_SESSION['admin'], $_GET['local']); ?></p>
		<thead>
			<tr class="ui-widget-header ">
				<th>Pacote</th>
				<th colspan="3">Período da 1ª Fase de Assinatura</th>
				<th colspan="3">Período da 2ª Fase de Assinatura</th>
				<th colspan="3">Período da 3ª Fase de Assinatura</th>
				<th colspan="3">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$pacote = $rs['ID_PACOTE'];
                    $nome = $rs['DS_EVENTO'];
                    $dataInicio1 = $rs['DT_INICIO_FASE1']->format('d/m/Y');
                    $dataFim1 = $rs['DT_FIM_FASE1']->format('d/m/Y');
                    $dataInicio2 = $rs['DT_INICIO_FASE2'] ? $rs['DT_INICIO_FASE2']->format('d/m/Y') : ' - ';
                    $dataFim2 = $rs['DT_FIM_FASE2'] ? $rs['DT_FIM_FASE2']->format('d/m/Y') : ' - ';
                    $dataInicio3 = $rs['DT_INICIO_FASE3'] ? $rs['DT_INICIO_FASE3']->format('d/m/Y') : ' - ';
                    $dataFim3 = $rs['DT_FIM_FASE3'] ? $rs['DT_FIM_FASE3']->format('d/m/Y') : ' - ';
			?>
			<tr>
				<td class="descricao"><?php echo utf8_encode2($nome); ?></td>
				<td><?php echo $dataInicio1; ?></td>
				<td>a</td>
				<td><?php echo $dataFim1; ?></td>
				<td><?php echo $dataInicio2; ?></td>
				<td>a</td>
				<td><?php echo $dataFim2; ?></td>
				<td><?php echo $dataInicio3; ?></td>
				<td>a</td>
				<td><?php echo $dataFim3; ?></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=edit&pacote=<?php echo $pacote; ?>">Editar</td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=redirect&url=<?php echo urlencode('?p=pacoteApresentacoes&pacote='.$pacote); ?>">Apresentações</td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=delete&pacote=<?php echo $pacote; ?>">Apagar</td>
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