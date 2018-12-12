<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 25, true)) {
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {
	
	$result = executeSQL($mainConnection, 'SELECT ID_USUARIO, CD_LOGIN, DS_NOME,
						DS_EMAIL, IN_ATIVO, IN_ADMIN, CD_CPF,
						DS_DDD_CELULAR, DS_CELULAR
						FROM MW_USUARIO_ITAU');
	
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	
	$('#app table').delegate('a', 'click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 href = $this.attr('href'),
			 id = 'codusuario=' + $.getUrlVar('codusuario', href),
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
						tr.find('td:not(.button):eq(1)').html($('#email').val());
						tr.find('td:not(.button):eq(2)').html($('#login').val());
						tr.find('td:not(.button):eq(3)').html($('#cpf').val());
						tr.find('td:not(.button):eq(4)').html($('#ddd').val());
						tr.find('td:not(.button):eq(5)').html($('#celular').val());
						tr.find('td:not(.button):eq(6)').html($('#admin').is(':checked') ? 'sim' : 'n&atilde;o');
						tr.find('td:not(.button):eq(7)').html($('#ativo').is(':checked') ? 'sim' : 'n&atilde;o');
						
						$this.text('Editar').attr('href', pagina + '?action=edit&' + id);
						tr.find('td.button a:eq(1)').attr('href', pagina + '?action=reset&' + id);
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
			
			tr.find('td:not(.button):eq(0)').html('<input name="nome" type="text" class="inputStyle" id="nome" maxlength="100" value="' + values[0] + '" />');
			tr.find('td:not(.button):eq(1)').html('<input name="email" type="text" class="inputStyle" id="email" maxlength="100" value="' + values[1] + '" />');
			tr.find('td:not(.button):eq(2)').html('<input name="login" type="text" class="readonly inputStyle" id="login" maxlength="10" value="' + values[2] + '" readonly />');
			tr.find('td:not(.button):eq(3)').html('<input name="cpf" type="text" class="number inputStyle" id="cpf" maxlength="11"  size="11" value="' + values[3] + '" />');
			tr.find('td:not(.button):eq(4)').html('<input name="ddd" type="text" class="number inputStyle" id="ddd" maxlength="2" size="2" value="' + values[4] + '" />');
			tr.find('td:not(.button):eq(5)').html('<input name="celular" type="text" class="inputStyle" id="celular" maxlength="15" size="15" value="' + values[5] + '" />');
			tr.find('td:not(.button):eq(6)').html('<input name="admin" type="checkbox" class="inputStyle" id="admin" ' + (values[6] == 'sim' ? 'checked' : ''  )+ ' />');
			tr.find('td:not(.button):eq(7)').html('<input name="ativo" type="checkbox" class="inputStyle" id="ativo" ' + (values[7] == 'sim' ? 'checked' : ''  )+ ' />');
			
			$this.text('Salvar').attr('href', pagina + '?action=update&' + id);
			$('.number').onlyNumbers();
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
		} else if (href.indexOf('?action=reset') != -1) {
			$.confirmDialog({
				text: 'Tem certeza que deseja restaurar a senha desse usuário?',
				uiOptions: {
					buttons: {
						'Sim': function() {
							$(this).dialog('close');
							$.get(href, function(data) {
								if (data.replace(/^\s*/, "").replace(/\s*$/, "") == 'true') {
									$.dialog({title: 'Aviso...', text: 'A senha foi restaurada para o padrão.'});
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
								'<td><input name="nome" type="text" class="inputStyle" id="nome" maxlength="100" /></td>' +
								'<td><input name="email" type="text" class="inputStyle" id="email" maxlength="100" /></td>' +
								'<td><input name="login" type="text" class="inputStyle" id="login" maxlength="10" /></td>' +
								'<td><input name="cpf" type="text" class="number inputStyle" id="cpf" maxlength="11" size="11" /></td>' +
								'<td><input name="ddd" type="text" class="number inputStyle" id="ddd" maxlength="2" size="2" /></td>' +
								'<td><input name="celular" type="text" class="inputStyle" id="celular" maxlength="15" size="15" /></td>' +
								'<td><input name="admin" type="checkbox" class="inputStyle" id="admin" /></td>' +
								'<td><input name="ativo" type="checkbox" class="inputStyle" id="ativo" /></td>' +
								'<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
								'<td class="button"><a href="#reset">Restaurar Senha</a></td>' +
								'<td class="button"><a href="#delete">Apagar</a></td>' +
							'</tr>';
		$(newLine).appendTo('#app table tbody');
		$('.number').onlyNumbers();
	});
	
	function validateFields() {
		var campos = $(':text:not(#ddd, #celular)'),
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
<h2>SISBIN x Usuários</h2>
<form id="dados" name="dados" method="post">
	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header ">
				<th>Nome</th>
				<th>E-mail</th>
				<th>Login</th>
				<th>CPF</th>
				<th>DDD</th>
				<th>Celular</th>
				<th>Admin</th>
				<th>Ativo</th>
				<th colspan="3">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$id = $rs['ID_USUARIO'];
			?>
			<tr>
				<td><?php echo utf8_encode2($rs['DS_NOME']); ?></td>
				<td><?php echo utf8_encode2($rs['DS_EMAIL']); ?></td>
				<td><?php echo utf8_encode2($rs['CD_LOGIN']); ?></td>
				<td><?php echo $rs['CD_CPF']; ?></td>
				<td><?php echo $rs['DS_DDD_CELULAR']; ?></td>
				<td><?php echo $rs['DS_CELULAR']; ?></td>
				<td><?php echo $rs['IN_ADMIN'] ? 'sim' : 'n&atilde;o'; ?></td>
				<td><?php echo $rs['IN_ATIVO'] ? 'sim' : 'n&atilde;o'; ?></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=edit&codusuario=<?php echo $id; ?>">Editar</a></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=reset&codusuario=<?php echo $id; ?>">Restaurar Senha</a></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=delete&codusuario=<?php echo $id; ?>">Apagar</a></td>
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