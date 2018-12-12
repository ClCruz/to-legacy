<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
$conn = getConnectionDw();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 213, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

	require('actions/' . $pagina);
	
    } else {

	$query = "SELECT S.ID_NR_CHAMADO, S.ID_DIA, O.DS_ORIGEM_CHAMADO,
		    C.DS_TIPO_CHAMADO, R.DS_TIPO_RESOLUCAO, S.ID_DIA_RESOLUCAO,
		    S.DS_OBSERVACAO
		    FROM FATO_SAC S
		    INNER JOIN DIM_ORIGEM_CHAMADO O ON S.ID_ORIGEM_CHAMADO = O.ID_ORIGEM_CHAMADO
		    INNER JOIN DIM_TIPO_CHAMADO C ON S.ID_TIPO_CHAMADO = C.ID_TIPO_CHAMADO
		    INNER JOIN DIM_TIPO_RESOLUCAO R ON S.ID_TIPO_RESOLUCAO = R.ID_TIPO_RESOLUCAO
		    WHERE S.ID_DIA LIKE ? + '__'
		    ORDER BY S.ID_DIA";
	$params = array($_GET['ano'] . $_GET['mes']);
	$result = executeSQL($conn, $query, $params);
?>
	<script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
	<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript" src="../javascripts/jquery.cookie.js"></script>
	<script type="text/javascript" language="javascript">
	    $(function() {
		var pagina = '<?php echo $pagina; ?>'
		$('input.button, a.button').button();

		$('#app table').delegate('a', 'click', function(event) {
		    event.preventDefault();

		    var $this = $(this),
			href = $this.attr('href'),
			id = 'id=' + $.getUrlVar('id', href),
			tr = $this.closest('tr');

		    if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
			if (!validateFields()) return false;

			$.ajax({
			    url: href,
			    type: 'post',
			    data: $('#dados').serialize(),
			    success: function(data) {
				if (trim(data).substr(0, 4) == 'true') {
				    var id = $.serializeUrlVars(data);
				    
				    tr.find('td:not(.button):eq(0)').html($('#dia').val());
				    tr.find('td:not(.button):eq(1)').html($('#origem option:selected').text());
				    tr.find('td:not(.button):eq(2)').html($('#tipo option:selected').text());
				    tr.find('td:not(.button):eq(3)').html($('#resolucao option:selected').text());
				    tr.find('td:not(.button):eq(4)').html($('#diaResolucao').val());
				    tr.find('td:not(.button):eq(5)').html($('#obs').val());
                                    
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

			tr.find('td:not(.button):eq(0)').html('<input name="dia" type="text" class="inputStyle datePicker dia" id="dia" maxlength="10" size="10" readonly />');
			tr.find('td:not(.button):eq(1)').html('<?php echo comboOrigemChamado('origem', $_GET['origem']); ?>');
			tr.find('td:not(.button):eq(2)').html('<?php echo comboTipoChamado('tipo', $_GET['tipo']); ?>');
			tr.find('td:not(.button):eq(3)').html('<?php echo comboTipoResolucao('resolucao', $_GET['resolucao']); ?>');
			tr.find('td:not(.button):eq(4)').html('<input name="diaResolucao" type="text" class="inputStyle datePicker" id="diaResolucao" maxlength="10" size="10" readonly />');
			tr.find('td:not(.button):eq(5)').html('<input name="obs" type="text" class="inputStyle" id="obs" maxlength="250" value="' + values[5] + '"/>');

			$('#origem').find('option').filter(function(){return $(this).text() == values[1];}).prop('selected','selected');
			$('#tipo').find('option').filter(function(){return $(this).text() == values[2];}).prop('selected', 'selected');
			$('#resolucao').find('option').filter(function(){return $(this).text() == values[3];}).prop('selected', 'selected');

			$this.text('Salvar').attr('href', pagina + '?action=update&' + id);

			$('#dia').val(values[0]);
			$('#diaResolucao').val(values[4]);
                        setDatePickers3();
			setDatePickers2();
                        $('#dia').change(function() {
                                $("#diaResolucao").datepicker("option", "minDate", $(this).val());
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

		$('#new').click(function(event){
		    event.preventDefault();

		    if(!hasNewLine()) return false;

		    if(!validateFields()) return false;

		    var newLine = '<tr id="newLine">' +
			'<td><input name="dia" type="text" class="inputStyle datePicker dia" id="dia" maxlength="10" size="10" readonly /></td>' +
			'<td>' + '<?php echo comboOrigemChamado('origem', $_GET['origem']); ?>' + '</td>' +
			'<td>' + '<?php echo comboTipoChamado('tipo', $_GET['tipo']); ?>' + '</td>' +
			'<td>' + '<?php echo comboTipoResolucao('resolucao', $_GET['resolucao']); ?>' + '</td>' +
			'<td><input name="diaResolucao" type="text" class="inputStyle datePicker" id="diaResolucao" maxlength="10" size="10" readonly /></td>' +
			'<td><input name="obs" type="text" class="inputStyle" id="obs" maxlength="250" /></td>' +
			'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
			'<td class="button"><a href="#delete">Apagar</a></td>' +
			'</tr>';
		    $('#app table tbody').append(newLine);
                   setDatePickers3();
		   setDatePickers2();
                   $('#dia').change(function() {
                        $("#diaResolucao").datepicker("option", "minDate", $(this).val());
                    });

		});

		$('#btnRelatorio').click(function() {
		    document.location = '?p='+pagina.replace('.php', '')+'&ano='+$('#ano').val()+'&mes='+$('#mes').val();
		});

		function validateFields() {
		    var campos = $(':input:not(button, .button, #obs, #diaResolucao)'),
			valido = true;

		    $.each(campos, function(i, e) {
			var $this = $(e);

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
	    function setDatePickers2() {
		var minDate = new Date($('#ano').val(), $('#mes').val()-1, 1),
		maxDate = new Date($('#ano').val(), $('#mes').val(), 0);

		$("#dia").datepicker("option", "minDate", minDate)
		    .datepicker("option", "maxDate", maxDate)
		    .datepicker("option", "changeMonth", false)
		    .datepicker("option", "changeYear", false);

		$('select').width(180);
	    }

            function setDatePickers3() {
               $('input.datePicker').datepicker({
                        minDate: new Date($('#ano').val(), $('#mes').val()-1,  $('#dia').val()),
                        maxDate: new Date($('#ano').val(), $('#mes').val(),0),
                        changeMonth: true,
                        changeYear: true
                });
                
                $('#diaResolucao').click(function() {
                       $("#diaResolucao").datepicker("option", "minDate", $('#dia').val());
                });
            }
	</script>
	<h2>SAC-Chamados</h2>
	<form id="dados" name="dados" method="post">
	    <p style="width:600px;">Ano&nbsp;<?php echo comboAnos('ano', $_GET['ano'], 2010, 2020); ?>
		&nbsp;&nbsp;Mês&nbsp;&nbsp;<?php echo comboMeses('mes', $_GET['mes']); ?>&nbsp;
		<input type="button" class="button" id="btnRelatorio" value="Buscar" />&nbsp;
	    </p>

	    <table class="ui-widget ui-widget-content" >
		<thead>
		    <tr class="ui-widget-header">
			<th>Dia</th>
			<th>Origem</th>
			<th>Tipo de Chamado</th>
			<th>Resolução</th>
			<th>Dia da Resolução</th>
			<th>Observações</th>
			<th colspan="2">Ações</th>
		    </tr>
		</thead>
    	<tbody>

<?php
	$totalAcessos = 0;
        $cont = 0;
	while ($rs = fetchResult($result)) {
	    $dia = $rs['ID_DIA'];
	    $id = 'id=' . $rs['ID_NR_CHAMADO'];
	    $dia = substr($rs['ID_DIA'], -2) . '/' . substr($rs['ID_DIA'], 4, 2) . '/' . substr($rs['ID_DIA'], 0, 4);
	    $diaRes = (($rs['ID_DIA_RESOLUCAO']) ? substr($rs['ID_DIA_RESOLUCAO'], -2) . '/' . substr($rs['ID_DIA_RESOLUCAO'], 4, 2) . '/' . substr($rs['ID_DIA_RESOLUCAO'], 0, 4) : '');
?>
    	    <tr>
    		<td><input name="diatemp[]" type="hidden" class="dia" id="diatemp<?php echo $cont;?>" maxlength="10" size="10" value="<?php echo $dia; ?>" /><?php echo $dia; ?></td>
    		<td><?php echo utf8_encode2($rs["DS_ORIGEM_CHAMADO"]); ?></td>
    		<td><?php echo utf8_encode2($rs["DS_TIPO_CHAMADO"]); ?></td>
    		<td><?php echo utf8_encode2($rs["DS_TIPO_RESOLUCAO"]); ?></td>
    		<td><?php echo $diaRes; ?></td>
    		<td><?php echo $rs["DS_OBSERVACAO"]; ?></td>
    		<td class="button"><a href="<?php echo $pagina; ?>?action=edit&<?php echo $id; ?>">Editar</a></td>
    		<td class="button"><a href="<?php echo $pagina; ?>?action=delete&<?php echo $id; ?>">Apagar</a></td>
    	    </tr>
<?php
            $cont++;
	}
?>
	</tbody>
    </table>
    <a id="new" class="button" href="#new">Novo</a>
</form>

<?php
	}
    }
?>