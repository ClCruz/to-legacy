<?php
require_once('../settings/functions.php');
require_once('../settings/Paginator.php');
include('../settings/Log.class.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 26, true)) {

$pagina = basename(__FILE__);

require('actions/'.$pagina);

if (isset($_GET['action'])) {
	
	$arrayBase = explode("*", $_POST["local"]);
	$idBase = $arrayBase[0];
	$nomeBase = $arrayBase[1];

	// Alterar programas 
	if (isset($_GET["action"]) && $_GET["action"] == "cad") {
		if(isset($_GET["tipo"]) && $_GET["tipo"] == "todos")
			echo cadastrarAcessoEvento($_POST["usuario"], $_POST["eventos"], $idBase, $mainConnection);
		else if(isset($_GET["tipo"]) && $_GET["tipo"] == "geral")
			echo cadastrarAcessoEvento($_POST["usuario"], "geral", $idBase, $mainConnection);
		else
			echo cadastrarAcessoEvento($_POST["usuario"], $_GET["idevento"], $idBase, $mainConnection);
	} else if (isset($_GET["action"]) && $_GET["action"] == "del"){
		if(isset($_GET["tipo"]) && $_GET["tipo"] == "todos")
			echo deletarAcessoEvento($_POST["usuario"], $_POST["eventos"], $idBase, $mainConnection);		
		else if(isset($_GET["tipo"]) && $_GET["tipo"] == "geral")
			echo deletarAcessoEvento($_POST["usuario"], "geral", $idBase, $mainConnection);
		else
			echo deletarAcessoEvento($_POST["usuario"], $_GET["idevento"], $idBase, $mainConnection);	
	}
	die();
	
} else {
	
$result = executeSQL($mainConnection, 'SELECT ID_USUARIO, DS_NOME FROM  MW_USUARIO_ITAU WHERE IN_ATIVO = 1 AND IN_ADMIN = 1');
$resultBase = executeSQL($mainConnection,'SELECT ID_BASE, DS_NOME_TEATRO, DS_NOME_BASE_SQL FROM MW_BASE WHERE IN_ATIVO = 1 ORDER BY 2');
// Recebe dados e monta checkbox de eventos
if (isset($_GET["local"]) && isset($_GET["usuario"])) {
	$arrayBase = explode("*", $_GET["local"]);

	$sqlMarcados = "SELECT COUNT(1) FROM MW_USUARIO_ITAU_EVENTO U INNER JOIN MW_EVENTO E ON U.ID_EVENTO = E.ID_EVENTO WHERE U.ID_USUARIO = ? AND E.ID_BASE = ?";
	$params = array($_GET["usuario"], $arrayBase[0]);
	$totalEventosMarcados = numRows($mainConnection, $sqlMarcados, $params);

	$total = totalEventos($arrayBase[0], $_GET["usuario"], $mainConnection);
	$total_reg = (!isset($_GET["controle"])) ? 10 : $_GET["controle"];
	$offset = (isset($_GET["offset"])) ? $_GET["offset"] : 1;
	$final = ($offset + $total_reg) -1;

	if($totalEventosMarcados == $total)
		$checked = "checked";
	else
		$checked = "";

	$resultEventos = recuperarEventos($_GET["usuario"], $arrayBase[0], $offset, $final, true, $mainConnection);
	$hasRows = hasRows($resultEventos);
}
?>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	
    $('.btnSelecionarGeral').click(function(){
		var checkbox = $(this);
		
		if (validar()) {
			if (checkbox.is(':checked')) {
				var check = true;
				var url = pagina + "?action=cad&tipo=geral";
			} else {
				var check = false;
				var url = pagina + "?action=del&tipo=geral";
			}
				
			$(".chm").attr('checked', true);
			$(".btnSelecionarTodos").attr('checked', check);
			
			$.ajax({
				url: url,
				type: 'post',
				data: $('#dados').serialize(),
				success: function(data){
					if (data != "OK") $.dialog({text: data});
				},
				complete: function(){
					$('loadingIcon').fadeOut('slow');	
				}
			}); 
			if (!check) $(".chm").attr('checked', false);
		}
    });
	
	$('.chm').click(function(){
		var checkbox = $(this);
		
		if (validar()) {
			if (checkbox.is(':checked')) {
				var check = true;
				var url = pagina + "?action=cad&idevento=" + checkbox.val();
			} else {
				var check = false;
				var url = pagina + "?action=del&idevento=" + checkbox.val();
				$('.btnSelecionarGeral').attr('checked', false);
			}
			
			$('loadingIcon').fadeIn('fast');
			
			$.ajax({
				url: url,
				type: 'post',
				data: $('#dados').serialize(),
				success: function(data) {
					if (data != "OK")	$.dialog({text: data});
				},
				complete: function() {
					$('loadingIcon').fadeOut('slow');
				}
			});
			verificaCheckbox();
		}
	});
	
	verificaCheckbox = function() {
		var numCheckbox = $('.chm').length;
		var chm = $('.chm').get();
		var cont = 0;
		for(i = 0; i < numCheckbox; i++){
			if(chm[i].checked)
				cont++;
		}
		if(cont == numCheckbox)
			$('.btnSelecionarTodos').attr('checked', true);
		else
			$('.btnSelecionarTodos').attr('checked', false);		
	}; verificaCheckbox();

	$('.button').button();

	$('tr:not(.ui-widget-header)').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	});
	
	// Alterar permissão dos eventos
	$('#btnAlterar').click(function(){
		$.dialog({title: 'Sucesso...', text: 'Dados alterados com sucesso'});
	});
	
	$("#controle").change(function(){
		document.location = '?p=' + pagina.replace('.php', '') + '&controle=' + $("#controle").val() + '&usuario=' + $("#usuario").val() + '&local=' + $("#local").val() + '';		
	});
	
	// Selecionar todos os eventos na página
	$('.btnSelecionarTodos').click(function(){
		if (validar()) {
			if (this.checked) {
				var v = true;
				var url = pagina + "?action=cad&tipo=todos";
			} else {
				var v = false;
				var url = pagina + "?action=del&tipo=todos";
				$('.btnSelecionarGeral').attr('checked', false);
			}	
			
			$('.chm').attr('checked', true);
	
			$.ajax({
				url: url,
				type: 'post',
				data: $('#dados').serialize(),
				success: function(data) {
					if(data != "OK") $.dialog();
				}, 
				complete: function() {
					$('loadingIcon').fadeOut('slow');
				}
			});
			if (!v) $('.chm').attr('checked', false);
		}
	});
		
	// Executar busca de eventos
	$('#btnProcurar').click(function(){
		if(validar()){
			window.document.location = '?p=' + pagina.replace('.php', '') + '&usuario='+ $('#usuario').val() + '&local=' + $('#local').val()+'';
		}
	});
	
	$('#local').change(function() {$('#btnProcurar').click();});
	$('#usuario').change(function() {$('#btnProcurar').click();});
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
<h2>SISBIN x Permissões x Bases x Eventos</h2>
<form id="dados" name="dados" action="?p=programaUsuarioEventos" method="post" style="text-align: left;">
	<select name="usuario" id="usuario">
    <option value="vazio">Escolha o usuário</option>
    <?php 
		while($rs = fetchResult($result)){
			(isset($_GET["usuario"]) && $_GET["usuario"] == $rs["ID_USUARIO"]) ? $selected = "selected" : $selected = "";
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
			print("<option ". $selected ." value=\"". $rsBase["ID_BASE"]."*".$rsBase["DS_NOME_BASE_SQL"] . "\">". $rsBase["DS_NOME_TEATRO"] ."</option>");
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
						  <td>'.$nbsp.'&nbsp;'.utf8_encode2($rsEventos['DS_EVENTO']).'</td>
						  <td style="text-align: center;">
							  <input type="checkbox" class="chm" name="eventos[]" '.$rsEventos["CHECKED"].' value="'.$rsEventos["ID_EVENTO"].'" />
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
    <div style="text-align: right;"><input type="button" id="btnAlterar" value="Alterar" class="button" /></div>
	</div>
</form>
<?php
	}	
}
?>