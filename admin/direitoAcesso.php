<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 14, true)) {

$pagina = basename(__FILE__);

if (isset($_GET['action'])) {
	
	require('actions/'.$pagina);
	
} else {
	
$result = executeSQL($mainConnection, 'SELECT ID_USUARIO, DS_NOME FROM  MW_USUARIO WHERE IN_ATIVO = 1 AND IN_ADMIN = 1 ORDER BY DS_NOME ASC');
?>

<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script>
$(function() {
	var pagina = '<?php echo $pagina; ?>';
	$('.button').button();
	$('#btnProcurar').click(function(){
		if($('#usuario').val() != "vazio"){
			$.ajax({
				url: "programaUsuario.php?action=view",
				type: 'post',
				data: $('#dados').serialize(),
				success: function(data) {
					$("#programas").html(data);
				}
			});
		}else{
			$.dialog({title: 'Alerta...', text: 'Selecione o usuário'});
		}
	});
	
	$('#usuario').change(function() {$('#btnProcurar').click();});
});
</script>
<h2>Direitos de Acesso</h2>
<form id="dados" name="dados" action="?p=direitoAcesso" method="post" style="text-align: left;">
	<select name="usuario" id="usuario">
    <option value="vazio">Escolha o usuário</option>
    <?php 
		while($rs = fetchResult($result)){
			print("<option value=\"". $rs["ID_USUARIO"] ."\">". $rs["DS_NOME"] ."</option>");
		}
	?>
    </select>
    <input type="button" class="button" id="btnProcurar" value="Buscar Programas" />
    
    <div id="programas">
    
    </div>
    
</form>
<?php
	}	
}
?>