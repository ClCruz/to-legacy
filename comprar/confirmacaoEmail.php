<?php
	session_start();

	require_once('../settings/settings.php');
	require_once('../settings/functions.php');
	require_once('../settings/multisite/unique.php');
	if (!isset($_SESSION['user'])) {
		header("Location: login.php?redirect=" . urlencode(getCurrentUrl()));
		die();
	}

	if ($_GET['action'] == 'confirmar') {

		$mainConnection = mainConnection();

		$query = 'SELECT 1 FROM MW_CONFIRMACAO_EMAIL WHERE ID_CLIENTE = ? AND CD_CONFIRMACAO = ?';
		$params = array($_SESSION['user'], trim($_POST['codigo']));
		
		$rs = executeSQL($mainConnection, $query, $params, true);

		if ($rs[0]) {
			$query = 'DELETE FROM MW_CONFIRMACAO_EMAIL WHERE ID_CLIENTE = ? AND CD_CONFIRMACAO = ?';
			executeSQL($mainConnection, $query, $params, true);

			unset($_SESSION['confirmar_email']);

			echo 'redirect.php?redirect=' . $_GET['redirect'];
		}

		die();

	} else if ($_GET['action'] == 'reenviar') {

		sendConfirmationMail($_SESSION['user'], preg_match('/assinatura/', $_GET['redirect']));

		echo json_encode(array('text' => 'Confirmação de e-mail enviada', 'detail' => 'Por favor, confirme o recebimento no e-mail cadastrado.'));

		die();
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-WNN2XTF');</script>
	<!-- End Google Tag Manager -->

	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex,nofollow">
	<link href="<?php echo multiSite_getFavico()?>" rel="shortcut icon"/>
	<link href='https://fonts.googleapis.com/css?family=Paprika|Source+Sans+Pro:200,400,400italic,200italic,300,900' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="../stylesheets/cicompra.css"/>
	<link rel="stylesheet" href="../stylesheets/ajustes2.css"/>

	<script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
	<script src="../javascripts/cicompra.js" type="text/javascript"></script>

	<script src="../javascripts/confirmacaoEmail.js" type="text/javascript"></script>

	<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
	<script src="../javascripts/common.js" type="text/javascript"></script>
	<title><?php echo multiSite_getTitle()?></title>
</head>
<body>
	<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNN2XTF" 
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<div class="bg__main" style=""></div><div id="pai">
	<?php require "header.php"; ?>
	
	<div id="content">
		<div class="alert">
				<div class="centraliza">
					<img src="../images/ico_erro_notificacao.png">
					<div class="container_erros"></div>
					<a>fechar</a>
				</div>
			</div>

			<div class="row centraliza">
				<div class="row descricao_pag">
					<div class="img">
						<img src="../images/ico_black_passo2.png">
					</div>
					<div class="descricao">
						<p class="title__page">Confirmação de e-mail</p>
						<p class="">
							Informe o código recebido no e-mail cadastrado<br/>ou solicite o reenvio do e-mail de confirmação.
						</p>
						<div class="sessao">
							<p class="tempo" id="tempoRestante"></p>
							<p class="mensagem"></p>
						</div>
					</div>
				</div>

				<span id="row identificacao container__login" class="row contaner__login">
					<form id="confirmacaoForm" method="post" action="confirmacaoEmail.php">
						<div class="identificacao">
							<p class="frase"><b>Já recebi</b><br/>o código</p>
							<p class="site">de confirmação</p>
							<input type="text" name="codigo" placeholder="digite o código recebido" id="codigo" value="<?php echo $_GET['codigo']; ?>">
							<div class="erro_help">
								<p class="erro">código inválido</p>
								<p class="help"></p>
							</div>
							<input type="button" class="submit logar" id="confirmar" value="Confirmar" />
						</div>
						<div class="identificacao">
							<p class="frase"><b>Não recebi</b><br/>o código</p>
							<p class="site">de confirmação</p>
							<input type="button" class="submit reenviar" id="reenviar" value="Reenviar"/>
						</div>
					</form>
				</span>

			</div>
		</div>

		<div id="texts">
			<div class="centraliza">
				<p>Informe o código recebido no e-mail cadastrado ou solicite o reenvio do e-mail de confirmação.</p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>
</body>
</html>