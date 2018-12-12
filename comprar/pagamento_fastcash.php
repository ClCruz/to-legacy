<?php
session_start();
require_once('../settings/functions.php');
require_once('../settings/settings.php');
require_once('../settings/multisite/unique.php');

if ($is_manutencao === true) {
	header("Location: manutencao.php");
	die();
}
error_reporting(E_ALL & ~E_NOTICE);
require('acessoLogado.php');
require('verificarBilhetes.php');
require('verificarServicosPedido.php');
require('verificarLimitePorCPF.php');
require('verificarEntrega.php');
require('verificarAssinatura.php');

require_once('../settings/fastcash/Fastcash.php');

if (isset($_COOKIE['entrega'])) {
    $action = "verificatempo";
    $etapa = 4;
    $idestado = $_COOKIE['entrega'];
    require('calculaFrete.php');
}

$mainConnection = mainConnection();

$json = json_encode(array('descricao' => ($_POST ? '2.' : '1.') .' etapa5 - ' . ($_POST ? 'envio de dados' : 'formulario cartao')));
include('logiPagareChamada.php');

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));

$query = "SELECT 1
            FROM MW_PEDIDO_VENDA P
            INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
            INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO AND M.DS_MEIO_PAGAMENTO LIKE '%FASTCASH%'
            WHERE P.ID_PEDIDO_VENDA = ? AND P.ID_CLIENTE = ?";
$params = array($_GET['pedido'], $_SESSION['user']);
$rs = executeSQL($mainConnection, $query, $params, true);

// se nao encontrar nenhum registro pode ser usuario tentando acessar
// um pedido de outro usuario, meio de pagamento que nao bate com o
// selecionado ou um pedido que nao esta mais em processamento
if (empty($rs)) {
    header("Location: ". multiSite_getURI("URI_SSL"));
    die();
}

$url = getFastcashPaymentURL($_GET['pedido']);
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

	<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
	<script src="../javascripts/common.js" type="text/javascript"></script>

	<script>
	$(function(){
		$('.botao_pagamento').click(function(e){
			e.preventDefault();
			$('#dadosPagamento').submit();
		});
	});
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
<body>
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
						<img src="../images/ico_black_passo5.png">
					</div>
					<div class="descricao">
						<p class="nome">5. Pagamento - Fastcash</p>
						<p class="descricao">
							passo <b>5 de 5</b> escolha a bandeira de sua preferência
						</p>
					</div>

					<div id="fastcashPaymentFrame"></div>
					<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pym/0.4.5/pym.min.js"></script>
					<script>var pymParent = new pym.Parent('fastcashPaymentFrame', '<?php echo $url?>', {});</script>

				</div>
					
			</div>

		</div>

		<div id="texts">
			<div class="centraliza">
				<p>Para visualizar a condição de pagamento dos meus pedidos, clique <a href="minha_conta.php">aqui</a>.</p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>
</body>
</html>