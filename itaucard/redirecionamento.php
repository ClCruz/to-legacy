<?php
session_start();

require 'logado.php';

$url = 'sistema.php?evento='.$_GET['evento'].'&apresentacao='.$_GET['apresentacao'];
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<?php require('header.php'); ?>
		<meta http-equiv="refresh" content="3; url=<?php echo $url; ?>" />
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
					<h2>Pedido efetuado com sucesso!</h2>
					<br/><br/>
					<h3>Redirecionando...</h3>
					<h4>(Se o redirecionamento demorar mais que 3 segundos clique 
						<a href="<?php echo $url; ?>">aqui</a>)</h4>					
				</div>
			</div>
			<div class="bar_bottom"></div>
		</div>
	</body>
</html>