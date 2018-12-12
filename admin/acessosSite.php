<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
$conn = getConnectionDw();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 32, true)) {
    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
		
    } else {
	
		$query = "SELECT A.ID_DIA, A.ID_PAGINA, A.QT_ACESSO, P.DS_PAGINA
					FROM FATO_ACESSO_SITE A
					INNER JOIN DIM_PAGINA P ON A.ID_PAGINA = P.ID_PAGINA
					WHERE A.ID_DIA LIKE ? + '__'
					ORDER BY ID_DIA";
		$params = array($_GET['ano'].$_GET['mes']);
		$result = executeSQL($conn, $query, $params);
	
?>
    <script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script type="text/javascript" language="javascript">
        $(function() {
            var pagina = '<?php echo $pagina; ?>'
            $('input.button, a.button').button();
			
			$('#app table').delegate('a', 'click', function(event) {
				event.preventDefault();

				var $this = $(this),
				href = $this.attr('href'),
				id = 'dia=' + $.getUrlVar('dia', href) + '&pagina=' + $.getUrlVar('pagina', href),
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
								tr.find('td:not(.button):eq(1)').html($('#pagina option:selected').text());
								tr.find('td:not(.button):eq(2)').html($('#acessos').val());

								$this.text('Editar').attr('href', pagina + '?action=edit&' + id);
								tr.find('td.button a:last').attr('href', pagina + '?action=delete&' + id);
								tr.removeAttr('id');
								
								$('#btnRelatorio').click();
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

					tr.find('td:not(.button):eq(0)').html('<input name="dia" type="text" class="inputStyle datePicker" id="dia" maxlength="10" value="' + values[0] + '" readonly />');
					tr.find('td:not(.button):eq(1)').html('<?php echo comboPaginas('pagina', $_GET['pagina']); ?>');
					tr.find('td:not(.button):eq(2)').html('<input name="acessos" type="text" class="inputStyle" id="acessos" value="' + values[2].replace(/\./g, '') + '" maxlength="9" />');
					$('#pagina').find('option').filter(function(){return $(this).text() == values[1];}).prop('selected', 'selected');
					$this.text('Salvar').attr('href', pagina + '?action=update&' + id);

					setDatePickers();
					setDatePickers2();
					$('#dia').val(values[0]);
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
											$('#btnRelatorio').click();
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
					'<td><input name="dia" type="text" class="inputStyle datePicker" id="dia" maxlength="10" readonly /></td>' +
					'<td>' + '<?php echo comboPaginas('pagina', $_GET['pagina']); ?>' + '</td>' +
					'<td align="right"><input name="acessos" type="text" class="inputStyle" id="acessos" maxlength="9" /></td>' +
					'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
					'<td class="button"><a href="#delete">Apagar</a></td>' +
					'</tr>';
				$('#app table tbody tr.total').before(newLine);
				setDatePickers();
				setDatePickers2();
            });
			
			$('#btnRelatorio').click(function() {
				document.location = '?p='+pagina.replace('.php', '')+'&ano='+$('#ano').val()+'&mes='+$('#mes').val();
			});
			
			function validateFields() {
				var campos = $(':input:not(button, .button)'),
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
			
			$(".datePicker").datepicker("option", "minDate", minDate)
							.datepicker("option", "maxDate", maxDate)
							.datepicker("option", "changeMonth", false)
							.datepicker("option", "changeYear", false);
			
			$('#acessos').onlyNumbers();
		}

    </script>
    <h2>Cadastro de Acessos ao Site</h2>
<form id="dados" name="dados" method="post">
<p style="width:600px;">Ano&nbsp;<?php echo comboAnos('ano', $_GET['ano'], 2010, 2020); ?>
&nbsp;&nbsp;Mês&nbsp;&nbsp;<?php echo comboMeses('mes', $_GET['mes']); ?>&nbsp;
<input type="button" class="button" id="btnRelatorio" value="Buscar" />&nbsp;
</p>

<table class="ui-widget ui-widget-content" >
    <thead>
        <tr class="ui-widget-header">
            <th>Dia</th>
            <th>Página</th>
            <th	align="right" style="text-align: right;">Acessos</th>
            <th colspan="2">Ações</th>
        </tr>
    </thead>

    <?php
        $totalAcessos = 0;
        while ($rs = fetchResult($result)) {
			$dia = $rs['ID_DIA'];
			$id = 'dia='.$dia.'&pagina='.$rs['ID_PAGINA'];
			$dia = substr($rs['ID_DIA'], -2).'/'.substr($rs['ID_DIA'], 4, 2).'/'.substr($rs['ID_DIA'], 0, 4);
    ?>
            <tbody>
                <tr>
                    <td><?php echo $dia; ?></td>
                    <td><?php echo utf8_encode2($rs["DS_PAGINA"]); ?></td>
                    <td align="right"><?php echo number_format($rs["QT_ACESSO"], 0, ',', '.'); ?></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=edit&<?php echo $id; ?>">Editar</a></td>
                    <td class="button"><a href="<?php echo $pagina; ?>?action=delete&<?php echo $id; ?>">Apagar</a></td>
                </tr>
        <?php
            $totalAcessos += $rs["QT_ACESSO"];
        }
        ?>
        <tr class="total">
            <td align="right" colspan="2" style="font-weight: bold;">Total</td>
            <td align="right" width="104" style="font-weight: bold;"><?php echo number_format($totalAcessos, 0, ',', '.'); ?></td>
			<td class="button" colspan="2">&nbsp;</td>
        </tr>
    </tbody>
</table>
<a id="new" class="button" href="#new">Novo</a>
</form>

<?php
        }
    }
?>