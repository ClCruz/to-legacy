<?php
session_start();

require_once('../settings/functions.php');

require('acessoLogado.php');
require_once('../settings/settings.php');
require_once('../settings/MCAPI.class.php');
require_once('../settings/multisite/unique.php');
$mainConnection = mainConnection();

$json = json_encode(array('descricao' => '8. chamada do assinatura_ok - codigo_pedido=' . $_GET['pedido'], 'Post='=>$_GET ));
include('logiPagareChamada.php');

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));



limparCookies();
unset($_SESSION['order_id']);
unset($_SESSION['id_braspag']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
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
	
	<style type="text/css">
	#selos {
		margin-bottom: 0;
	}
	.imprima_agora.nova_venda {
	    float: right;
	    width: auto;
	    background-color: #930606;
	}
	.imprima_agora.nova_venda a {
		color: white;
		padding: 10px;
	}
	</style>

	<script type="text/javascript">
	function popup(url, width, height) {
		var left = (window.screen.width/2)-(width/2);
		var top = (window.screen.height/2)-(height/2);

		var win = window.open(url, "_blank", 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width='+width+', height='+height);
		win.moveTo(left, top);
	}
	</script>

	<?php echo $campanha['script']; ?>

	<script type="text/javascript">
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', '<?php echo multiSite_getGoogleAnalytics();?>']);
	  _gaq.push(['_setDomainName', '<?php echo multiSite_getName(); ?>']);
	  _gaq.push(['_setAllowLinker', true]);
	  _gaq.push(['_trackPageview']);

	  <?php echo $scriptTransactionAnalytics; ?>
	  _gaq.push(['_trackTrans']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();
	</script>

	<script type="text/javascript">
	var fb_param = {};
	fb_param.pixel_id = '6009548174001';
	fb_param.value = '<?php echo $valorPagamento; ?>';
	fb_param.currency = 'USD';
	(function(){
	  var fpw = document.createElement('script');
	  fpw.async = true;
	  fpw.src = '//connect.facebook.net/en_US/fp.js';
	  var ref = document.getElementsByTagName('script')[0];
	  ref.parentNode.insertBefore(fpw, ref);
	})();
	</script>
	<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/offsite_event.php?id=6009548174001&amp;value=<?php echo $valorPagamento; ?>&amp;currency=USD" /></noscript>

	<!-- Facebook Conversion Code for Compra -->
	<script>(function() {
	  var _fbq = window._fbq || (window._fbq = []);
	  if (!_fbq.loaded) {
	    var fbds = document.createElement('script');
	    fbds.async = true;
	    fbds.src = '//connect.facebook.net/en_US/fbds.js';
	    var s = document.getElementsByTagName('script')[0];
	    s.parentNode.insertBefore(fbds, s);
	    _fbq.loaded = true;
	  }
	})();
	window._fbq = window._fbq || [];
	window._fbq.push(['track', '6025588813845', {'value':'<?php echo $valorPagamento; ?>','currency':'BRL'}]);
	</script>
	<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6025588813845&amp;cd[value]=<?php echo $valorPagamento; ?>&amp;cd[currency]=BRL&amp;noscript=1" /></noscript>

	<title><?php echo multiSite_getTitle()?></title>
</head>
<body>
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

			<div class="centraliza" id="pedido">
				<div class="descricao_pag">
					<div class="img">
						<img src="../images/ico_black_passo6.png">
					</div>
					<div class="descricao final">
						<p class="nome">Muito obrigado!</p>
						<p class="descricao">
							<b>Sua assinatura foi enviada para<br>
							seu e-mail,</b> verifique a caixa de spam<br>
							se não encontrar a mensagem.
						</p>
					</div>
					<div class="numero_pedido">
						<p class="numero">
							Seu pedido com o número<br>
							<a href="minha_conta.php?pedido=<?php echo $_GET['pedido']; ?>" <?php echo (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) ? 'target="_blank"' : ''; ?>><?php echo $_GET['pedido']; ?></a> foi realizado.
						</p>
						<p class="minha_conta">
							Você pode conferir essa compra e as<br>
							anteriores acessando a <a href="minha_conta.php" <?php echo (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) ? 'target="_blank"' : ''; ?>>minha conta</a>
						</p>
					</div>
				</div>

				<?php if ((isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) { ?>
					<div class="imprima_agora nova_venda"><a href="etapa0.php">NOVA VENDA</a></div>
				<?php } ?>

			</div>
		</div>

		<div id="texts">
			<div class="centraliza">
				<p>Muito obrigado por escolher a <?php echo multiSite_getName(); ?> para a compra de seus ingressos.</p>

				<p>Fique por dentro das principais atrações em cartaz na sua cidade através do nosso Guia de Espetáculos enviado por email. Adicione o email <?php echo multiSite_getEmail("marketing"); ?> ao seu catálogo de endereços para receber nossos emails na sua caixa de entrada.</p>

				<p>Curta nossa página no <a href=“<?php echo multiSite_getFacebook(); ?>”>Facebook</a> e acompanhe diariamente as últimas novidades da nossa programação.</p>

				<p>Bom espetáculo!</p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>
</body>
</html>