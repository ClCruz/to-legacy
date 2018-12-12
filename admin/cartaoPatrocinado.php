<?php
require_once("../settings/Paginator.php");
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 23, true)) {
	
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {
	
	// $result = executeSQL($mainConnection, 'SELECT ID_CARTAO_PATROCINADO, DS_CARTAO_PATROCINADO, CD_BIN FROM MW_CARTAO_PATROCINADO WHERE ID_PATROCINADOR = ? order by DS_CARTAO_PATROCINADO', array($_GET['idPatrocinador']));

	function paginarResultados($mainConnection)
	{
		global $obj;

		$num_registros = 'SELECT ID_CARTAO_PATROCINADO, DS_CARTAO_PATROCINADO, CD_BIN FROM MW_CARTAO_PATROCINADO WHERE ID_PATROCINADOR = ? order by DS_CARTAO_PATROCINADO';
		$params = array($_GET['idPatrocinador']);

		$paramns = array(
			'query' => $num_registros,
			'paramns' => $params
		);

		$link = '?p='.$_GET['p'].'&idPatrocinador='.$_GET['idPatrocinador'];
		$obj = Paginator::__paginate($link, $paramns);

		$between = 'where row between '.$obj["start"].' and '.$obj['end'];
		$nQuery = 'WITH results AS (
				SELECT 
					ID_CARTAO_PATROCINADO, 
					DS_CARTAO_PATROCINADO, 
					CD_BIN
					, ROW_NUMBER() OVER (ORDER BY DS_CARTAO_PATROCINADO) row 
				FROM MW_CARTAO_PATROCINADO 
				WHERE ID_PATROCINADOR = ?)
				SELECT * FROM results '.$between;

		$result = executeSQL($mainConnection, $nQuery, array($_GET['idPatrocinador']));
		return $result;
	}

	$result = paginarResultados($mainConnection);

?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>'
	
	$('#app table').delegate('a', 'click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 href = $this.attr('href'),
			 id = 'idCartaoPatrocinado=' + $.getUrlVar('idCartaoPatrocinado', href),
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
						
						tr.find('td:not(.button):eq(0)').html($('#nome').val());
						tr.find('td:not(.button):eq(1)').html($('#bin').val());
						
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
			
			tr.find('td:not(.button):eq(0)').html('<input name="nome" type="text" class="inputStyle" id="nome" maxlength="50" value="' + values[0] + '" />');
			tr.find('td:not(.button):eq(1)').html('<input name="bin" type="text" class="inputStyle" id="bin" maxlength="6" value="' + values[1] + '" />');
			
			$this.text('Salvar').attr('href', pagina + '?action=update&' + id);
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
								'<td>'+
									'<input name="nome" type="text" class="inputStyle" id="nome" maxlength="50" />' +
								'</td>' +
								'<td>'+
									'<input name="bin" type="text" class="inputStyle" id="bin" maxlength="6" />' +
								'</td>' +
								'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
								'<td class="button"><a href="#delete">Apagar</a></td>' +
							'</tr>';
		$(newLine).appendTo('#app table tbody');
		setDatePickers();
	});
	
	function validateFields() {
		var campos = $(':input'),
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
	
	$('#idPatrocinador').change(function() {
		document.location = '?p=' + pagina.replace('.php', '') + '&idPatrocinador=' + $(this).val();
	});
});
</script>
<h2>Cart&otilde;es Patrocinados</h2>
<form id="dados" name="dados" method="post">
	<table class="ui-widget ui-widget-content">
		<p style="width:200px;"><?php echo comboPatrocinador('idPatrocinador', $_GET['idPatrocinador']); ?></p>
		<thead>
			<tr class="ui-widget-header ">
				<th>Cart&atilde;o Patrocinado</th>
				<th>BIN</th>
				<th colspan="2">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$idCartaoPatrocinado = $rs['ID_CARTAO_PATROCINADO'];
                                        $nome = $rs['DS_CARTAO_PATROCINADO'];
					$bin = $rs['CD_BIN'];
			?>
			<tr>
				<td><?php echo utf8_encode2($nome); ?></td>
				<td><?php echo $bin; ?></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=edit&idCartaoPatrocinado=<?php echo $idCartaoPatrocinado; ?>">Editar</a></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=delete&idCartaoPatrocinado=<?php echo $idCartaoPatrocinado; ?>">Apagar</a></td>
			</tr>
			<?php
				}
			?>
		</tbody>
	</table>
	<?php if($_GET['idPatrocinador'] != '') { ?>
	<div id="paginacao">
		<?php
			echo $obj['htmlpages'];
		?>
	</div>
	<?php } ?>
	<a id="new" href="#new">Novo</a>
</form>
<?php
}

}
?>