<?php
require_once('../settings/functions.php');
require_once('../settings/Paginator.php');
include('../settings/Log.class.php');

$mainConnection = mainConnection();
session_start();
if (acessoPermitido($mainConnection, $_SESSION['admin'], 16, true) === true) {

$pagina = basename(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT']."/admin/actions/".$pagina);

if (isset($_GET['action'])) {

	if ( isset($_POST['local']) )
	{
		$arrayBase 	= explode("*", $_POST["local"]);
		$idBase 	= $arrayBase[0];
		$nomeBase 	= $arrayBase[1];
		$idUsuario  =$_POST['usuario'];
	}
	//Se vier da página responsvelBasem tudo é enviado via ajax get.
	else
	{
		$idBase 	= $_GET['base'];
		$nomeBase 	= fetchAssoc( executeSQL($mainConnection, 'SELECT ds_nome_base_sql FROM mw_base WHERE id_base = '.$idBase) );
		$nomeBase 	= $nomeBase[0]['ds_nome_base_sql'];
		$idUsuario 	= $_GET['usuario'];
	}

	if (isset($_GET["action"]) && $_GET["action"] == "cad") {

		if(isset($_GET["tipo"]) && $_GET["tipo"] == "todos")
			echo cadastrarAcessoEvento($idUsuario, $idBase, $nomeBase, $_POST["eventos"], $mainConnection);
		else if(isset($_GET["tipo"]) && $_GET["tipo"] == "geral")
			echo cadastrarAcessoEvento($idUsuario, $idBase, $nomeBase, "geral", $mainConnection);
		else
			echo cadastrarAcessoEvento($idUsuario, $idBase, $nomeBase, $_GET["idevento"], $mainConnection);

	} else if (isset($_GET["action"]) && $_GET["action"] == "del") {

		if(isset($_GET["tipo"]) && $_GET["tipo"] == "todos"){
			echo deletarAcessoEvento($idUsuario, $idBase, $_POST["eventos"], $mainConnection);
		}
		else if(isset($_GET["tipo"]) && $_GET["tipo"] == "geral"){
			echo deletarAcessoEvento($idUsuario, $idBase, "geral", $mainConnection);
		}
		else{
			echo deletarAcessoEvento($idUsuario, $idBase, $_GET["idevento"], $mainConnection);
		}

	} else if (isset($_GET["action"]) && $_GET["action"] == "notificar") {

		echo notificarUsuarioEventos($idUsuario, $idBase, $nomeBase, $mainConnection);

	}
	die();
	
} else {
	
$result = executeSQL($mainConnection, 'SELECT ID_USUARIO, DS_NOME FROM  MW_USUARIO WHERE IN_ATIVO = 1 AND IN_ADMIN = 1 ORDER BY DS_NOME');
$resultBase = executeSQL($mainConnection,'SELECT ID_BASE, DS_NOME_TEATRO, DS_NOME_BASE_SQL FROM MW_BASE WHERE IN_ATIVO = 1 ORDER BY 2');
// Recebe dados e monta checkbox de eventos
if(isset($_GET["local"]) && isset($_GET["usuario"])){
	$arrayBase = explode("*", $_GET["local"]);

	$sqlMarcados = "SELECT ID_USUARIO FROM MW_ACESSO_CONCEDIDO WHERE ID_USUARIO = ? AND ID_BASE = ? ";
	$params = array($_GET["usuario"], $arrayBase[0]);
	$totalEventosMarcados = numRows($mainConnection, $sqlMarcados, $params);

	$total = totalEventos($arrayBase[1], $arrayBase[0], $_GET["usuario"], $mainConnection);
	$total_reg = (!isset($_GET["controle"])) ? 10 : $_GET["controle"];
	$offset = (isset($_GET["offset"])) ? $_GET["offset"] : 1;
	$final = ($offset + $total_reg) -1;

	if($totalEventosMarcados == $total)
		$checked = "checked";
	else
		$checked = "";

	$resultEventos = recuperarEventos($_GET["usuario"], $arrayBase[1], $arrayBase[0], $offset, $final, true, $mainConnection);
	$hasRows = hasRows($resultEventos);
}
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	var checkboxes = document.getElementsByClassName('chm');

	var btnSelecionarTodos = document.getElementsByClassName('btnSelecionarTodos')[0];
	var btnSelecionarGeral = document.getElementsByClassName('btnSelecionarGeral')[0];

	verificaCheckbox();

	cfgChecks();
	function cfgChecks()
	{
		for(var i = 0; i< checkboxes.length; i++)
		{
			var chm = checkboxes[i];
			checkClick(chm);
		}

		function checkClick(check)
		{
			check.onclick = function ()
			{
				var action = ( this.checked ) ? 'cad' : 'del';
				var url = pagina + "?action="+action+"&idevento=" + this.value;

				$.ajax({
					url: url,
					method: 'post',
					data: $('#dados').serialize(),
					success: function(data) {
						if (data != "OK")	$.dialog({text: data});
					},
					complete: function() {
						//$('loadingIcon').fadeOut('slow');
					}
				});
				verificaCheckbox();
			}
		}
	}

	function verificaCheckbox()
	{
		var allChecked = true;
		for(var i = 0; i < checkboxes.length; i++)
		{
			var cbox = checkboxes[i];
			if ( !cbox.checked ) { allChecked = false }
		}

		btnSelecionarTodos.checked = allChecked;
		if ( btnSelecionarGeral.checked && !allChecked )  { btnSelecionarGeral.checked = allChecked; }
	}

	$('.button').button();

	$('tr:not(.ui-widget-header)').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	});

	btnSelecionarTodos.onclick = function () { selectAllChecksCurPage(this, 'todos'); };
	btnSelecionarGeral.onclick = function () { selectAllChecksCurPage(this, 'geral'); };

	function selectAllChecksCurPage(allCheck, tipo)
	{
		if ( !validar() ) { return false; }

		var action = ( allCheck.checked ) ? 'cad' : 'del';
		if (action == 'cad') { changeCheck(); }

		$.ajax({
			url: pagina + "?action="+action+"&tipo="+tipo,
			type: 'post',
			data: $('#dados').serialize(),
			success: function(data) {
				if(data != "OK") $.dialog();
			},
			complete: function() {
				$('loadingIcon').fadeOut('slow');
				if (action == 'del') { changeCheck(); }
				verificaCheckbox();
			}
		});

		/*
		* Função executada em momentos diferentes para cada action ('cad','del') por causa do serialize
		* do form
		* */
		function changeCheck()
		{
			for(var i = 0; i < checkboxes.length; i++)
			{
				var checkEvento = checkboxes[i];
				checkEvento.checked = allCheck.checked;
			}
		}

	}

	// Alterar permissão dos eventos e enviar e-mail
	$('#btnAlterar').button({icons:{primary: "ui-icon-mail-closed"}}).click(function(){
		$.ajax({
			url: pagina + '?action=notificar',
			type: 'post',
			data: $('#dados').serialize(),
			success: function(data) {
				if (data != "true")	$.dialog({text: data});
				else $.dialog({title: 'Aviso...', text: 'E-mail enviado com sucesso'});
			},
			complete: function() {
				$('loadingIcon').fadeOut('slow');
			}
		});
	});

	//Paginação
	$("#controle").change(function(){
		document.location = '?p=' + pagina.replace('.php', '') + '&controle=' + $("#controle").val() + '&usuario=' + $("#usuario").val() + '&local=' + $("#local").val() + '';
	});

	// Executar busca de eventos
	$('#btnProcurar').click(function(){
		if(validar()){
			window.document.location = '?p=' + pagina.replace('.php', '') + '&usuario='+ $('#usuario').val() + '&local=' + $('#local').val()+'';
		}
	});

	//
	$('#local').change(function() {$('#btnProcurar').click();});
});

function validar(){
	if($('#usuario').val() == "vazio"){
		$.dialog({title: 'Alerta...', text: 'Selecione o usuário'});
		return false;
	}
	else if($('#local').val() == "vazio"){
		$.dialog({title: 'Alerta...', text: 'Selecione o local'});
		return false;
	}
	return true;
}
</script>
<style type="text/css">
	label{
		margin-left: 15px;	
	}
	#paginacao{
		width: 100%;
		text-align: center;
		margin-top: 10px;	
	}
	.selecionar{
		float: right;
		margin-right: 20px;
		padding-top: 5px;
	}
</style>
<h2>Liberar Permissões x Bases x Eventos</h2>
<form id="dados" name="dados" action="?p=programaUsuarioEventos" method="post" style="text-align: left;">
	<select name="usuario" id="usuario">
    <option value="vazio">Escolha o usuário</option>
    <?php 
		while($rs = fetchResult($result)){
			$selected = ((isset($_GET["usuario"]) && $_GET["usuario"] == $rs["ID_USUARIO"])
						or (isset($_GET["codusuario"]) && $_GET["codusuario"] == $rs["ID_USUARIO"])) ? "selected" : "";

			print("<option ". $selected ." value=\"". $rs["ID_USUARIO"] ."\">". $rs["DS_NOME"] ."</option>");
		}
	?>
    </select>
    <label>Local</label>
    <select name="local" id="local">
    <option value="vazio">Escolha o local</option>
    <?php
		while($rsBase = fetchResult($resultBase)){
			(isset($_GET["local"]) && $arrayBase[0] == $rsBase["ID_BASE"]) ? $selected = "selected" : $selected = "";
			print("<option ". $selected ." value=\"". $rsBase["ID_BASE"]."*".$rsBase["DS_NOME_BASE_SQL"] . "\">". utf8_encode2($rsBase["DS_NOME_TEATRO"]) ."</option>");
		}
	?>
    </select>
    <input type="button" class="button" id="btnProcurar" value="Buscar Eventos" />
    <div class="selecionar">Selecionar: 
        Todos <input type="checkbox" name="selecionados[]" class="btnSelecionarTodos" value="todos" /> &nbsp;&nbsp;
        Todos os eventos <input type="checkbox" name="selecionados[]" <?php echo $checked; ?> class="btnSelecionarGeral" value="geral" />
    </div>
    
    <div id="eventos">
    <!-- Tabela de pedidos -->
	<table class="ui-widget ui-widget-content" id="tabPedidos">
	<thead>
		<tr class="ui-widget-header">
			<th>Evento</th>
            <th style="text-align: center;">Permitir</th>
		</tr>
	</thead>
	<tbody>

    <?php
    	if ($hasRows) {
			while ($rsEventos = fetchResult($resultEventos)) {
				echo '<tr>
						  <td>'.$nbsp.'&nbsp;'.utf8_encode2($rsEventos['NOMPECA']).'</td>
						  <td style="text-align: center;">
							  <input type="checkbox" class="chm" name="eventos[]" '.$rsEventos["CHECKED"].' value="'.$rsEventos["CODPECA"].'" />		
							  <input type="hidden" name="eventosOcultos" value="'.$rsEventos["CODPECA"].'" />
						  </td>
					  </tr>';
			}
		}
	?>
    </tbody>
    </table>
    </div>
    
    <div id="paginacao">
	<?php
		if($hasRows){
        	$link = "?p=".basename($pagina, '.php')."&usuario=".$_GET["usuario"]."&local=".$_GET["local"]."&controle=".$total_reg."&bar=2&baz=3&offset=";
        	Paginator::paginate($offset, $total, $total_reg, $link, true);
		}
    ?>
	</div>
	<br />
    <center>
    	<strong>Enviar e-mail para usuário informando liberação para acesso ao relatório do borderô de vendas?</strong><br /><br />
    	<span id="btnAlterar">Sim</span>
    </center>
</form>
<?php
	}	
}
?>