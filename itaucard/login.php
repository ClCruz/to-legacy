<?php
session_start();
if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') header('Content-type: application/json');

if ($_POST) {
	require_once('../settings/functions.php');

	$mainConnection = mainConnection();

	$query = 'SELECT ID_USUARIO FROM MW_USUARIO_ITAU WHERE CD_LOGIN = ? AND CD_PWW = ? AND IN_ATIVO = 1';
	$result = executeSQL($mainConnection, $query, array($_POST['usuario'], md5($_POST['senha'])));

	if (hasRows($result)) {
		$rs = fetchResult($result);
		$_SESSION['userItau'] = $rs['ID_USUARIO'];
		if ($_POST['senha'] == '123456') $_SESSION['senha'] = true;
		$_SESSION['mensagens'] = true;
		
		$data['redirect'] = 'sistema.php';
	} else {
		$data['error'] = 'Usuário e/ou senha não conferem!';
	}
	
	exit(json_encode($data));
	
} else if (isset($_GET['logout'])) session_destroy();
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
					<h1 id="errorBox">Insira seu Usuário e Senha para entrar no sistema.</h1>
					<form id="login" action="login.php" method="post"> 
						<div class="login">
							<div class="cont_input_login">
								<p>Usuário</p>
								<div class="cont_input">
									<div class="contorno_left"></div>
									<input type="text" name="usuario" />
									<div class="contorno_right"></div>
								</div>
							</div>
							<div class="cont_input_login">
								<p>Senha</p>
								<div class="cont_input">
									<div class="contorno_left"></div>
									<input type="password" name="senha" />
									<div class="contorno_right"></div>
								</div>
							</div>
							<div class="cont_input_submit">
								<input type="submit" name="enviar" value="" />
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="bar_bottom"></div>
		</div>
	</body>
</html>