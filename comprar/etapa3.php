<?php
session_start();
if (isset($_SESSION['operador'])) {
	header("Location: etapa3_2.php");
} else if (isset($_SESSION['user'])) {
	if (isset($_GET['redirect'])) {
		header("Location: " . urldecode($_GET['redirect']));
	} else {
		if ($_COOKIE['entrega']) {
			header("Location: etapa3_entrega.php");
		} else {
			header("Location: etapa4.php");
		}
	}
}

require_once('../settings/functions.php');

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
    <?php require("desktopMobileVersion.php"); ?>
	<link rel="stylesheet" href="../stylesheets/ajustes2.css"/>

	<script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
	<script src="../javascripts/cicompra.js" type="text/javascript"></script>

	<script src="../javascripts/jquery.cookie.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
	<script src="../javascripts/common.js" type="text/javascript"></script>
	<script src="../javascripts/simpleFunctions.js" type="text/javascript"></script>

	<script src="../javascripts/identificacao_cadastro.js" type="text/javascript"></script>
	<script src="../javascripts/contagemRegressiva.js?until=<?php echo tempoRestante(); ?>" type="text/javascript"></script>

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
<body>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNN2XTF" 
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
  <div class="bg__main" style=""></div>

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

			<div class="row centraliza">
				<div class="row descricao_pag">
				<button type="button" class="btn btn-primary botao btn__help" data-toggle="modal" data-target="#sideModalTR"></button>

					<div class="img">
						<img src="../images/ico_black_passo3.png">
					</div>
					<div class="descricao">
						<p class="nome title__page">3. Identificação</p>
						<p class="descricao">
						</p>
						<div class="sessao">
							<p class="tempo" id="tempoRestante"></p>
							<p class="mensagem">
								Após essse prazo seu pedido será cancelado<br>
								automaticamente e os lugares liberados
							</p>
						</div>
					</div>
				</div>

				<div class="row container__identificacao">
				<?php require "div_identificacao.php"; ?>

				<?php require "div_cadastro.php"; ?>
				</div>
			</div>
		</div>

		<div id="texts">
			<div class="centraliza">
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>

		<div id="overlay">
			<?php require 'termosUso.php'; ?>
		</div>
	</div>


<div class="modal fade right" id="sideModalTR" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

<div class="modal-dialog modal-side modal-top-right" role="document">

<div class="modal-content">
<div class="modal-header">
      <h4 class="modal-title w-100" id="myModalLabel">

		Passo <b>3 de 5</b> identifique-se ou cadastre-se
      </h4>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
		
<p>Identifique-se com seu e-mail e senha de acesso ou cadastre-se e crie sua conta.</p>
      
    </div>
  </div>
</div>
</div>
</body>
</html>