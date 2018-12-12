<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
require_once('../settings/Paginator.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 280, true)) {
	
$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {

	$result = fetchAssoc( executeSQL($mainConnection, 'SELECT ID_USUARIO, DS_NOME FROM  MW_USUARIO WHERE IN_ATIVO = 1 AND IN_ADMIN = 1 ORDER BY DS_NOME ASC') );

	$preSelectedID = ( isset($_GET['codusuario']) ) ? $_GET['codusuario'] : '';

	//printr($result);
	$options = comboGenerico($result, array('strValue' => 'ID_USUARIO', 'reg' => $preSelectedID, 'strToShow' => 'DS_NOME'));
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
	<script>
		$(function () {

			var changed 	= false; //Usado para verificar se foi executada alguma coisa via ajax, se sim, exibir msg ao 'salvar'
			var userid 		= null;

			$('.btnSave').click(function () {
				//Se algum dado foi alterado, exibir msg de ok
				if (changed) {
					$.dialog({title: 'Sucesso', text: 'Dados alterados com sucesso.'});
					changed = false;
				}
			});

			if ( $('#usuario').val() != '')
			{
				getTeatros();
			}


			$("#usuario").change(function () {
				getTeatros();
			});

			function getTeatros()
			{
				//e = $('#usuario').val();
				var e = document.getElementById('usuario');
				userid = e.value;
				if (e.value == '')
				{
					$('#teatros tbody').html('');
					$('.btnSave').hide();
					$('#selectAll').parent().hide();
					return false;
				}

				changed = false;
				$('#selectAll').parent().show();
				$('#selectAll').prop('checked', false);
				$('#selectAll').parent().find('.txt').html('Selecionar Todos');
				$('.btnSave').show();

				$.ajax({
					method: 'get',
					data: { action: 'getTeatros', userid: e.value },
					url: 'responsavelBase.php',
					success: function (data)
					{
						userid = e.value;
						$('#teatros tbody').html(data);
						$('#teatros tbody tr').hover(function () {
							$(this).toggleClass('ui-state-hover');
						});
						cfgCheck();
					},
					error: function (error)
					{
						$.dialog({
							title: 'Erro',
							text: 'Não foi possivel carregar teatros para este usuário. Entre em contato com o administrador do sistema.'
						});
						location.reload();
					}

				})
			}

			/*
			*
			* Definir a ação utilizada com base no check selecionado ou não.*/
			function cfgCheck()
			{
				$('.check').change(function (){
					var e = this;
					var teatroid = e.value;

					var action = ( e.checked ) ? 'cad' : 'del';

					changePermissao(action, userid, teatroid);
				});

				$('#selectAll').click(function () {

					status = this.checked.toString();

					var txt = ( status == 'true' ) ? 'Desmarcar Todos' : 'Selecionar Todos';
					$(this).parent().find('.txt').html(txt);

					$('.check').each(function () {
						elementStatus = this.checked.toString();
						if ( elementStatus != status)
						{
							this.click();
						}
					})
				});
			}

			/*
			* Liberar permissão para a base, com sucesso, liberar permissão a todos os eventos da base
			* */
			function changePermissao(action, userid, teatroid)
			{
				$.ajax({
					method: 'get',
					data: { action: action, userid: userid, teatroid: teatroid },
					url: 'responsavelBase.php',
					success: function ()
					{
						changed = true;
						updateBase(action, teatroid, userid);
					},
					error: function (error)
					{
						$.dialog({
							title: 'Erro',
							text: 'Não foi possível atualizar os dados. Entre em contato com o administrador do sistema.'
						});
						location.reload();
					}
				})
			}

			/*
			* Conceder permissão a todos os eventos da base selecionada
			* */
			function updateBase(action, baseID, userID)
			{
				$.ajax({
					method: 'get',
					url: 'usuariosEventos.php',
					data: { action: action, base: baseID, usuario: userID, tipo: 'geral' },
					success: function (data) {
						console.log(data);
					},
					error: function (error) {
						alert('erro!');
					}
				})
			}

		})
	</script>

	<style>
		.btnSave {  display: none; }
		div.left { text-align: left; margin: 15px 0px; }
		div.right { text-align: right; margin: 15px 0px; }
		label.selectAll { display: none; font-size: 14px; }
		.float.left { float: left; }
		.float.right  { float: right; }
	</style>
<h2>Usuário Responsável pelo Teatro</h2>
<form id="dados" name="dados" method="post">
	<div class="float left">
		<select name="usuario" id="usuario">
			<option value="">Escolha o usuário</option>
			<?php
				echo $options;
			?>
		</select>
	</div>

	<div class="float right">
		<label class="selectAll">
			<span class="txt">selecionar todos</span>
			<input id="selectAll" type="checkbox">
		</label>
	</div>

	<table id="teatros" class="ui-widget ui-widget-content">
		<thead>
			<tr class="ui-widget-header">
				<th>Teatro</th>
				<th>Permissão</th>
			</tr>
		</thead>
		<tbody>
			<!-- conteúdo carregado via ajax ao selecionar o usuário -->
		</tbody>
	</table>
	<div class="right">
		<input type="button" class="btnSave" value="Salvar" />
	</div>
</form>
<?php
}

}
?>