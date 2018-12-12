<?php
require_once('../settings/Paginator.php');
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 9, true)) {
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {

	require('actions/'.$pagina);

} else {

	function paginarResultados($mainConnection)
	{
		global $obj;

		$_GET['ativo'] = !isset($_GET['ativo']) ? 1 : $_GET['ativo'];

		//$oldQuery = "SELECT ID_USUARIO, CD_LOGIN, DS_NOME, DS_EMAIL, IN_ATIVO, IN_ADMIN, IN_TELEMARKETING, IN_PDV, IN_POS FROM MW_USUARIO WHERE IN_ATIVO = ? OR ? = 2 ORDER BY DS_NOME ASC";

		$queryNumRows = 'SELECT id_usuario FROM mw_usuario WHERE in_ativo = ? OR ? = 2 ORDER BY ds_nome ASC';
		$paramnsQueryNumRows = array($_GET['ativo'], $_GET['ativo']);

		$paramns = array(
			'query' 	=> $queryNumRows,
			'paramns' 	=> $paramnsQueryNumRows
		);

		$link = '?p='.$_GET['p'].'&ativo='.$_GET['ativo'];
		$obj = Paginator::__paginate($link, $paramns);

		$between = 'WHERE row BETWEEN '.$obj["start"].' AND '.$obj["end"].' AND (in_ativo = ? OR ? = 2) ORDER BY ds_nome ASC';
		$newQuery = 'WITH results AS (
					SELECT 
						id_usuario
						, cd_login
						, ds_nome
						, ds_email
						, in_ativo
						, in_admin
						, in_telemarketing
						, in_pdv
						, in_pos
						, ROW_NUMBER() OVER (ORDER BY ds_nome) row 
						FROM mw_usuario
						WHERE in_ativo = '.$_GET["ativo"].' OR '.$_GET["ativo"].' = 2
				)
				SELECT * FROM results 
				 '.$between;

		$result = executeSQL($mainConnection,
			$newQuery,
			array($_GET['ativo'], $_GET['ativo']));

		return $result;
	}

	function pesquisaNome($mainConnection)
	{
		$like = "(ds_nome LIKE '%".$_GET['search']."%' OR ds_email LIKE '%".$_GET['search']."%' OR cd_login LIKE '%".$_GET['search']."%')";

		$ifAtivo = ( $_GET['ativo'] == '0' || $_GET['ativo'] == '1' ) ? "AND (in_ativo = ".(int)$_GET['ativo'].")" : '';

		$query = 'SELECT 
						id_usuario
						, cd_login
						, ds_nome
						, ds_email
						, in_ativo
						, in_admin
						, in_telemarketing
						, in_pdv
						, in_pos
						FROM mw_usuario
						WHERE '.$like.' '.$ifAtivo.' ORDER BY ds_nome';

		$result = executeSQL($mainConnection, $query);
		return $result;
	}

	if ( isset($_GET['search']) && !empty($_GET['search']) )
	{
		$result = pesquisaNome($mainConnection);
	}
	else
	{
		$result = paginarResultados($mainConnection);
	}
?>

<style type="text/css">
    .center{text-align: center;}
    #app{width: 95%;}
</style>
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
						var id = $.serializeUrlVars(data),
							email = $.getUrlVar('email', data);

						tr.find('td:not(.button):eq(0)').html($.getUrlVar('codusuario', data));
						tr.find('td:not(.button):eq(1)').html($('#nome').val());
						tr.find('td:not(.button):eq(2)').html($('#email').val());
						tr.find('td:not(.button):eq(3)').html($('#login').val());
						tr.find('td:not(.button):eq(4)').html($('#admin').is(':checked') ? 'sim' : 'n&atilde;o');
						tr.find('td:not(.button):eq(5)').html($('#ativo').is(':checked') ? 'sim' : 'n&atilde;o');
						tr.find('td:not(.button):eq(6)').html($('#telemarketing').is(':checked') ? 'sim' : 'n&atilde;o');
                        tr.find('td:not(.button):eq(7)').html($('#pdv').is(':checked') ? 'sim' : 'n&atilde;o');
                        tr.find('td:not(.button):eq(8)').html($('#pos').is(':checked') ? 'sim' : 'n&atilde;o');

						$this.text('Editar').attr('href', pagina + '?action=edit&' + id);
						tr.find('td.button a:eq(1)').attr('href', pagina + '?action=reset&' + id);
						tr.find('td.button a:last').attr('href', pagina + '?action=delete&' + id);
						tr.removeAttr('id');

						if (email == '') {
							$.dialog({title: 'Aviso...', text: 'E-mail de notificação enviado com sucesso.'});
						} else if (email != undefined) {
							$.dialog({text: email});
						}

						if (href.indexOf('?action=add') != -1) {
							$.dialog({
								text: 'Você cadastrou um novo usuário no sistema, selecione a opção desejada:<br><br>' +
									  'Clique no botão "Responsável pelo Teatro" para que o sistema gere automaticamente a permissão à todos os Eventos do teatro a ser selecionado.<br><br>' +
									  'Clique no botão "Liberar Permissões" para o cadastramento manual de uma evento para o usuário.<br><br>' +
									  'Clique no botão "Retornar" caso o usuário cadastrado seja um usuário padrão do sistema.',
								uiOptions: {
									buttons: {
										'Responsável pelo Teatro': function(){
											document.location = './?p=responsavelBase&' + id;
										},
										'Liberar Permissões': function(){
											document.location = './?p=usuariosEventos&' + id;
										},
										'Retornar': function(){
											$(this).dialog('close');
										}
									},
									open: function(e, ui) {
										$(this).next('.ui-dialog-buttonpane').find('span').filter(function(){
											return $(this).text() == 'Ok';
										}).parent().remove();
									}
								}
							});
						}
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

			tr.find('td:not(.button):eq(1)').html('<input name="nome" type="text" class="inputStyle" id="nome" maxlength="100" value="' + values[1] + '" />');
			tr.find('td:not(.button):eq(2)').html('<input name="email" type="text" class="inputStyle" id="email" maxlength="100" value="' + values[2] + '" />');
			tr.find('td:not(.button):eq(3)').html('<input name="login" type="text" class="readonly inputStyle" id="login" maxlength="10" value="' + values[3] + '" readonly />');
			tr.find('td:not(.button):eq(4)').html('<input name="admin" type="checkbox" class="inputStyle" id="admin" ' + (values[4] == 'sim' ? 'checked' : ''  )+ ' />');
			tr.find('td:not(.button):eq(5)').html('<input name="ativo" type="checkbox" class="inputStyle" id="ativo" ' + (values[5] == 'sim' ? 'checked' : ''  )+ ' />');
			tr.find('td:not(.button):eq(6)').html('<input name="telemarketing" type="checkbox" class="inputStyle" id="telemarketing" ' + (values[6] == 'sim' ? 'checked' : ''  )+ ' />');
            tr.find('td:not(.button):eq(7)').html('<input name="pdv" type="checkbox" class="inputStyle" id="pdv" ' + (values[7] == 'sim' ? 'checked' : ''  )+ ' />');
            tr.find('td:not(.button):eq(8)').html('<input name="pos" type="checkbox" class="inputStyle" id="pos" ' + (values[8] == 'sim' ? 'checked' : ''  )+ ' />');

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
		} else if (href.indexOf('?action=reset') != -1) {
			$.confirmDialog({
				text: 'Tem certeza que deseja restaurar a senha desse usuário?',
				uiOptions: {
					buttons: {
						'Sim': function() {
							$(this).dialog('close');
							$.get(href, function(data) {
								if (data.substr(0, 4) == 'true') {
									var email = $.getUrlVar('email', data);

									if (email != '') $.dialog({text: 'A senha foi restaurada para o padrão.<br/><br/>' + email});
									else $.dialog({title: 'Aviso...', text: 'A senha foi restaurada para o padrão e o e-mail de notificação foi enviado com sucesso.'});
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
                                                    '<td></td>' +
                                                    '<td><input name="nome" type="text" class="inputStyle" id="nome" maxlength="100" /></td>' +
                                                    '<td><input name="email" type="text" class="inputStyle" id="email" maxlength="100" /></td>' +
                                                    '<td><input name="login" type="text" class="inputStyle" id="login" maxlength="10" /></td>' +
                                                    '<td class="center"><input name="admin" type="checkbox" class="inputStyle" id="admin" /></td>' +
                                                    '<td class="center"><input name="ativo" type="checkbox" class="inputStyle" id="ativo" /></td>' +
                                                    '<td class="center"><input name="telemarketing" type="checkbox" class="inputStyle" id="telemarketing" /></td>' +
                                                    '<td class="center"><input name="pdv" type="checkbox" class="inputStyle" id="pdv" /></td>' +
                                                    '<td class="center"><input name="pos" type="checkbox" class="inputStyle" id="pos" /></td>' +
                                                    '<td class="button"><a href="' + pagina + '?action=add">Salvar</a></td>' +
                                                    '<td class="button"><a href="#reset">Restaurar Senha</a></td>' +
                                                    '<td class="button"><a href="#delete">Apagar</a></td>' +
                                            '</tr>';
		$(newLine).appendTo('#app table tbody');
		setDatePickers();
	});

	$('#filtro').change(function() {
		simples.setParamns('ativo', $(this).val(), { reload: true });
	});

	var porNome = document.getElementById('porNome');
	porNome.onkeydown = function (e) {
		if (e.keyCode == 13 /*enter*/) {
			$('#search').click();
		}
	};

	$('#search').click(function () {
		var status = $('#filtro').val();
		var string = porNome.value;

		if (string == '' || string.length < 3) {
			$.dialog({ text: 'Digite ao menos 3 caracteres' });
			return false;
		}

		simples.setParamns('ativo', status);
		simples.setParamns('search', string, { reload: true });
	});

	function validateFields() {
		var campos = $(':text:not(.notrequired)'),
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

    $('tr:not(.ui-widget-header)').hover(function() {
        $(this).addClass('ui-state-hover');
    }, function() {
        $(this).removeClass('ui-state-hover');
    });
});

function limparPesquisa() {
	simples.setParamns('ativo', 1);
	simples.setParamns('search', { action: 'delete', reload: true });
}
</script>
	<style>
		.boxSearch { float: left; margin-right: 45px; }
	</style>
<h2>Usuários</h2>
<div>
	<div class="boxSearch">
		Situação do usuário:
		<select id="filtro">
			<option value="1"<?php echo ($_GET['ativo'] == 1 ? ' selected' : ''); ?>>Ativo</option>
			<option value="0"<?php echo ($_GET['ativo'] == 0 ? ' selected' : ''); ?>>Inativo</option>
			<option value="2"<?php echo ($_GET['ativo'] == 2 ? ' selected' : ''); ?>>Todos</option>
		</select>
	</div>

	<div class="boxSearch">
		Pesquisar (Nome, login ou e-mail)
		<input class="notrequired" id="porNome" type="text" name="porNome" placeholder="Pesquisar..." value="<?php echo ( isset($_GET['search']) ? $_GET['search'] : '' ) ?>">
		<button id="search" type="submit" name="search">Pesquisar</button>
		<?php if( !empty($_GET['search']) ): ?>
			<button type="button" onclick="limparPesquisa();">Limpar pesquisa</button>
		<?php endif; ?>
	</div>
	<div style="clear: both"></div>
</div>
	<br/>
<form id="dados" name="dados" method="post">
	<table class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header ">
				<th width="1%">ID</th>
				<th width="20%">Nome</th>
				<th width="20%">E-mail</th>
				<th width="10%">Login</th>
				<th width="5%" class="center">Admin</th>
				<th width="5%" class="center">Ativo</th>
                <th width="5%" class="center">Telemarketing</th>
                <th width="10%" class="center">Usuário PDV</th>
                <th width="10%" class="center">Usuário POS</th>
				<th width="15%" class="center" colspan="3">A&ccedil;&otilde;es</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($rs = fetchResult($result)) {
					$id = $rs['id_usuario'];
			?>
			<tr>
				<td><?php echo $rs['id_usuario']; ?></td>
				<td><?php echo $rs['ds_nome']; ?></td>
				<td><?php echo $rs['ds_email']; ?></td>
				<td><?php echo $rs['cd_login']; ?></td>
				<td class="center"><?php echo $rs['in_admin'] ? 'sim' : 'n&atilde;o'; ?></td>
				<td class="center"><?php echo $rs['in_ativo'] ? 'sim' : 'n&atilde;o'; ?></td>
                <td class="center"><?php echo $rs['in_telemarketing'] ? 'sim' : 'n&atilde;o'; ?></td>
                <td class="center"><?php echo $rs['in_pdv'] ? 'sim' : 'n&atilde;o'; ?></td>
                <td class="center"><?php echo $rs['in_pos'] ? 'sim' : 'n&atilde;o'; ?></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=edit&codusuario=<?php echo $id; ?>">Editar</a></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=reset&codusuario=<?php echo $id; ?>">Restaurar Senha</a></td>
				<td class="button"><a href="<?php echo $pagina; ?>?action=delete&codusuario=<?php echo $id; ?>">Apagar</a></td>
			</tr>
			<?php
				}
			?>
		</tbody>
	</table>

	<div id="paginacao">
		<?php

		echo $obj['htmlpages'];

		//Paginator::__paginate($page, $perPage, $total, $link, 5, true, true);
		?>
	</div>
	<a id="new" href="#new">Novo</a>
</form>
<?php
}

}
?>