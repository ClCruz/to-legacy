<?php
session_start();
if (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) {
	require_once('../settings/functions.php');
	
	if (isset($_SESSION['user']) and is_numeric($_SESSION['user'])) {
		$mainConnection = mainConnection();
		
		$query = 'SELECT ID_CLIENTE, DS_NOME, DS_SOBRENOME, DS_DDD_TELEFONE, DS_TELEFONE, CD_CPF
					 FROM MW_CLIENTE
					 WHERE ID_CLIENTE = ?';
		$rs = executeSQL($mainConnection, $query, array($_SESSION['user']), true);
		$userSelected = true;
	} else {
		$userSelected = false;
	}
} else header("Location: loginOperador.php?redirect=etapa3_2.php");

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));
require_once('../settings/multisite/unique.php');
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
    <?php require("desktopMobileVersion.php"); ?>

	<script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
	<script src="../javascripts/cicompra.js" type="text/javascript"></script>

	<script src="../javascripts/jquery.cookie.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
	<script src="../javascripts/common.js" type="text/javascript"></script>

	<script type="text/javascript" src="../javascripts/identificacao_cadastro_operador.js"></script>
	<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
	<?php if (!isset($_GET['assinatura'])) { ?>
	<script type="text/javascript" src="../javascripts/contagemRegressiva.js?until=<?php echo tempoRestante(); ?>"></script>
	<?php } ?>

	<script>
	$(function() {
		$('#limpar').click();
		
		<?php if ($userSelected) { ?>
		$('#nomeBusca').val('<?php echo utf8_encode2($rs['DS_NOME']); ?>');
		$('#sobrenomeBusca').val('<?php echo utf8_encode2($rs['DS_SOBRENOME']); ?>');
		$('#telefoneBusca').val('<?php echo $rs['DS_TELEFONE']; ?>');
		$('#cpfBusca').val('<?php echo $rs['CD_CPF']; ?>');
		
		$('#buscar').click();
		<?php } ?>
	})
	</script>

	<?php echo $campanha['script']; ?>

	<script type="text/javascript">
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', '<?php echo multiSite_getGoogleAnalytics(); ?>']);
	  _gaq.push(['_setDomainName', '<?php echo multiSite_getName(); ?>']);
	  _gaq.push(['_setAllowLinker', true]);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();
	</script>
	<title><?php echo multiSite_getTitle()?></title>
</head>
<body<?php echo ((isset($_SESSION['usuario_pdv']) AND $_SESSION['usuario_pdv'] == 1) OR preg_match('/assinatura/', $_GET['redirect'])) ? ' class="mini"' : ''; ?>>

	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNN2XTF" 
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	
	<div id="pai">
		<?php require "header.php"; ?>
		<div id="content">
			<div class="alert">
				<div class="centraliza">
					<img src="../images/ico_erro_notificacao.png">
					<div class="container_erros"></div>
					<a>fechar</a>
				</div>
			</div>

			<div class="centraliza">
				<div class="descricao_pag">
					<div class="img">
						<img src="../images/ico_black_passo3.png">
					</div>
					<div class="descricao">
						<p class="nome">3. Identificação</p>
						<?php if (!isset($_GET['assinatura'])) { ?>
						<p class="descricao">
							passo <b>3 de 5</b> identifique-se ou cadastre-se
						</p>
						<div class="sessao">
							<p class="tempo" id="tempoRestante">00:11:30</p>
							<p class="mensagem">
								Após essse prazo seu pedido será cancelado<br>
								automaticamente e os lugares liberados
							</p>
						</div>
						<?php } ?>
					</div>
				</div>

				<?php require "div_identificacao_operador.php"; ?>
				<div id="resultadoBusca" class="identificacao"></div>
				<?php require "div_cadastro.php"; ?>

			</div>
		</div>

		<div id="texts">
			<div class="centraliza">
				<p></p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>

		<div id="overlay">
			<?php require 'termosUso.php'; ?>
		</div>
	</div>
</body>
</html>