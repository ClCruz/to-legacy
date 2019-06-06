<?php
header("Location: etapa5.php");
$edicao = false;
session_start();

require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');
require('acessoLogado.php');
require('verificarBilhetes.php');
require('verificarServicosPedido.php');
require('verificarLimitePorCPF.php');
require('verificarEntrega.php');
require('verificarAssinatura.php');

if (isset($_COOKIE['entrega'])) {
    $action = "verificatempo";
    $etapa = 4;
    $idestado = $_COOKIE['entrega'];
    require('calculaFrete.php');
}

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));
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
	<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>

	<script type="text/javascript" src="../javascripts/contagemRegressiva.js?until=<?php echo tempoRestante(); ?>"></script>
	<script type="text/javascript" src="../javascripts/carrinho.js"></script>
	<script type="text/javascript" src="../javascripts/dadosEntrega.js"></script>
	<?php echo $scriptBilheteInvalido.$scriptTempoLimiteFrete.$scriptLlimitePorCPF.$scriptEntrega.$scriptServicosPorPedido.$scriptAssinatura; ?>

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

			<div class="centraliza">
				<div class="descricao_pag">
					<div class="img">
						<img src="../images/ico_black_passo4.png">
					</div>
					<div class="descricao">
						<p class="nome title__page">4. Confirmação</p>
						<div class="sessao">
							<p class="tempo" id="tempoRestante"></p>
							<p class="mensagem">
								Após essse prazo seu pedido será cancelado<br>
								automaticamente e os lugares liberados
							</p>
						</div>
					</div>
					
					<?php require 'resumoPedido.php';?>
					</div>
					
					<div class="container_botoes_etapas">
						<div class="centraliza">
							<a href="etapa2.php?<?php echo $_COOKIE['lastEvent']; ?><?php echo $campanha['tag_voltar']; ?>" class="botao voltar passo3">identificação</a>
								<div class="resumo_carrinho">
									<span class="quantidade"></span>
									<span class="frase">selecionado(s) <br>para essa compra</span>
								</div>
							<a href="etapa5.php?eventoDS=<?php echo $_GET['eventoDS']; ?><?php echo $campanha['tag_avancar']; ?>" class="botao avancar passo5 botao_pagamento">IR PARA PAGAMENTO</a>
						</div>
					</div>
			</div>
			<div id="texts">
				<div class="centraliza">
					<p>Confira atentamente os dados do seu pedido e as condições e endereço de entrega se for o caso.</p>

					</div>
			</div>
		</div>


		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>

	

<div class="modal fade right" id="sideModalTR" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

<div class="modal-dialog modal-side modal-top-right" role="document">

<div class="modal-content">
<div class="modal-header">
			<h4 class="modal-title w-100" id="myModalLabel">
			Passo <b>4 de 5</b> revise e confirme sua compra
		</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
		<p>Confira atentamente os dados do seu pedido e as condições e endereço de entrega se for o caso.</p>

<p>Clique em avançar para efetuar o pagamento.</p>
			
		</div>
	</div>
</div>
</div>
</body>
</html>