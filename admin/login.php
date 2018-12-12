<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
if ($_GET['action'] == 'logout') {
	foreach ($_COOKIE as $key => $val) {
		setcookie($key, "", time() - 3600);
	}
	
	session_unset();
	session_destroy();
} else if ($_GET['action'] == 'trocarSenha' && $_SESSION['senha'] != true) {
	require_once('acessoLogado.php');
}

require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('header_new.php');

$nome = "";

		if (isset($_SESSION['admin'])) {
			$mainConnection = mainConnection();
			$query = 'SELECT DS_NOME FROM MW_USUARIO WHERE ID_USUARIO = ?';
			$params = array($_SESSION['admin']);
			$rs = executeSQL($mainConnection, $query, $params, true);
$nome = $rs['DS_NOME']; 
		}


?>
    <div id='content'>
    	<div id='app'>
			<script>
			$(function() {
				$.busyCursor();
				
				$('#enviar').button().click(function(event) {
					event.preventDefault();
					
					var form = $('form');
					
					$("#loadingIcon").fadeIn('fast');
					
					$.ajax({
						url: form.attr('action') + '?' + $.serializeUrlVars(),
						data: form.serialize(),
						type: form.attr('method'),
						success: function(data) {
							if (data.substr(0, 4) == 'redi') {
								document.location = data;
							} else {
								$("#idmessagerror").html(data);
								$("#iderroralert").show();
							}
						},
						complete: function() {
							$('#loadingIcon').fadeOut('slow');
						}
					});
				});
			})
			</script>
			<?php if ($_GET['action'] == 'trocarSenha') { ?>

<div>
	<div class="flex-center flex-column">
	
		<div class="view overlay">
			<img src="<?php echo getwhitelabel("logo"); ?>" style="width:100%; max-width:350px;" class="mx-auto d-block mb-4" alt="">
			<a href="#">
				<div class="mask rgba-white-slight"></div>
			</a>
		</div>
		
		<div class="view overlay">
		<h3>
			<p class="text-center"><font color="#FFFFFF"  face="verdana" size="5">Trocar senha</font></strong></p>
			<p class="text-center mt-2"><font color="#FFFFFF" face="verdana" size="3"><?php echo $nome ?>, por favor digite sua senha nova</font></p>
		</h3>
		</div>

		<div id="iderroralert" class="alert alert-warning alert-dismissible fade show" style="display: none" role="alert">
		  <button  type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="javascript:$('#iderroralert').hide()">
			<span aria-hidden="true">&times;</span>
		  </button>
		  <span id="idmessagerror"></span>
		</div>
	
		<!--Card-->
		<div class="card mt-4">
			<!--Card content-->
			<div class="card-body">
				<!--Title-->
				<h4 class="card-title">
				<i class="fa fa-laptop" aria-hidden="true"></i>
				Portal de Administração</h4>
				<!--Text-->
				<form action="autenticacao.php" method="post">
					<!-- Material input email -->
					<div class="md-form">
						<i class="fa fa-lock prefix grey-text"></i>
						<input type="password" id="senhaOld" placeholder="Senha antiga" name="senhaOld" class="form-control">
					</div>

					<div class="md-form">
						<i class="fa fa-lock prefix grey-text"></i>
						<input type="password" id="senha1" placeholder="Senha nova" name="senha1" class="form-control">
					</div>

					<!-- Material input password -->
					<div class="md-form">
						<i class="fa fa-lock prefix grey-text"></i>
						<input type="password" id="senha2" placeholder="Confirme senha nova" name="senha2" class="form-control">
					</div>

					<div class="text-center mt-4">
						<button class="btn" id="enviar" type="submit">Trocar</button>
					</div>
				</form>
			</div>

		</div>
		<!--/.Card-->
		
	</div>
</div>

			<?php } else { ?>
			
<div>
	<div class="flex-center flex-column">
	
		<div class="view overlay">
		
			<?php
				echo "<img src='".getwhitelabel("logo")."' style='width:100%; max-width:125px;' class='mx-auto d-block mb-4' alt=''>";
			?>
			<a href="#">
				<div class="mask rgba-white-slight"></div>
			</a>
		</div>
		
		<div class="view overlay">
		<h3>
			<p class="text-center"><font color="#FFFFFF"  face="verdana" size="5">Seja bem-vindo!</font></strong></p>
			<p class="text-center mt-2"><font color="#FFFFFF" face="verdana" size="3">Faça <b>login</b> para ter acesso a todas as <br>funcionalidades do Portal de Administração.</font></p>
		</h3>
		</div>

		<div id="iderroralert" class="alert alert-warning alert-dismissible fade show" style="display: none" role="alert">
		  <button  type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="javascript:$('#iderroralert').hide()">
			<span aria-hidden="true">&times;</span>
		  </button>
		  <span id="idmessagerror"></span>
		</div>
	
		<!--Card-->
		<div class="card mt-4">
			<!--Card content-->
			<div class="card-body">
				<!--Title-->
				<h4 class="card-title">
				<i class="fa fa-laptop" aria-hidden="true"></i>
				Portal de Administração</h4>
				<!--Text-->
				<form action="autenticacao.php" method="post">
					<!-- Material input email -->
					<div class="md-form">
						<i class="fa fa-user prefix grey-text"></i>
						<input type="text" id="usuario" placeholder="Usuário" name="usuario" class="form-control">
					</div>

					<!-- Material input password -->
					<div class="md-form">
						<i class="fa fa-lock prefix grey-text"></i>
						<input type="password" id="senha"placeholder="Senha" name="senha" class="form-control">
					</div>

					<div class="text-center mt-4">
						<button class="btn btn-dark btn-admin" id="enviar" type="submit">Entrar</button>
					</div>
				</form>
			</div>

		</div>
		<!--/.Card-->
		
	</div>
</div>
			
			<?php } ?>
		</div>
    </div>
<?php
require_once('footer_new.php');
?>