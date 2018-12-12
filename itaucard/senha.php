<?php
session_start();
if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') header('Content-type: application/json');

require_once('../settings/functions.php');
require 'logado.php';

if ($_POST) {
	if (strlen($_POST['senha1']) < 6) exit(json_encode(array('error'=>utf8_encode2('Sua nova senha deve ter, no m�nimo, 6 caracteres.'))));
	if ($_POST['senha1'] == '123456') exit(json_encode(array('error'=>utf8_encode2('Sua nova senha deve ser difirente da atual.'))));
	if ($_POST['senha1'] != $_POST['senha2']) exit(json_encode(array('error'=>utf8_encode2('A nova senha n�o confere com a confirma��o.'))));
	
	$mainConnection = mainConnection();
	executeSQL($mainConnection, 'UPDATE MW_USUARIO_ITAU SET CD_PWW = ? WHERE ID_USUARIO = ?', array(md5($_POST['senha1']), $_SESSION['userItau']));
	
	unset($_SESSION['senha']);
	
	exit(json_encode(array('success'=>true, 'redirect'=>'sistema.php')));
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<?php require('header.php'); ?>
	</head>
	<body>
		<div id="sisbin">
			<div class="cabecalho">
				<div class="usuario"></div>
				<div class="logo"><img src="../images/itau/logo_itaucard.jpg" alt="Itaucard" title="Itaucard" /></div>
			</div>
			<div class="bar_top">SISBIN - Sistema de Controle de BINs Promo&ccedil;&atilde;o Itaucard</div>
			<div class="container" style="overflow:hidden;">
				<div class="cont_login">
					<h1 id="errorBox">Para continuar voc&ecirc; deve trocar sua senha, que deve ter, no m&iacute;nimo, 6 caracteres.</h1>
					<form id="senha" action="senha.php" method="post"> 
						<div class="login">
							<div class="cont_input_login">
								<p>Nova Senha</p>
								<div class="cont_input">
									<div class="contorno_left"></div>
									<input type="password" name="senha1">
									<div class="contorno_right"></div>
								</div>
							</div>
							<div class="cont_input_login">
								<p>Confirma&ccedil;&atilde;o</p>
								<div class="cont_input">
									<div class="contorno_left"></div>
									<input type="password" name="senha2">
									<div class="contorno_right"></div>
								</div>
							</div>
							<div class="cont_input_submit">
								<input type="submit" name="enviar" value="">
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="bar_bottom"></div>
		</div>
	</body>
</html>