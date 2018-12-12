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

require_once('../settings/pagarme_functions.php');

$mainConnection = mainConnection();

$json = json_encode(array('descricao' => '6. pagamento pagarme - tela final do pedido '.$_GET['pedido']));
include('logiPagareChamada.php');

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));

$query = "SELECT PP.OBJ_PAGSEGURO
            FROM MW_PEDIDO_VENDA P
            INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
            INNER JOIN MW_PEDIDO_PAGSEGURO PP ON PP.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA
            WHERE P.ID_PEDIDO_VENDA = ? AND P.ID_CLIENTE = ?
            ORDER BY PP.DT_STATUS DESC";
$params = array($_GET['pedido'], $_SESSION['user']);
$rs = executeSQL($mainConnection, $query, $params, true);

// se nao encontrar nenhum registro pode ser usuario tentando acessar
// um pedido de outro usuario ou meio de pagamento que nao bate com o selecionado
if (empty($rs)) {
    header("Location: ". multiSite_getURI("URI_SSL"));
    die();
} else {

	$transaction = unserialize(base64_decode($rs['OBJ_PAGSEGURO']));
	// var_dump($transaction);
	// se for boleto
	if ($transaction['payment_method'] == 'boleto') {
		$boleto_url = $transaction['boleto_url'];
	}
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

			<div class="row centraliza" id="pedido">
				<div class="row descricao_pag">
					<div class="img">
						<img src="../images/ico_black_passo6.png">
					</div>
					<div class="descricao final">
						<p class="nome title__page">Muito obrigado!</p>
						<p class="descricao">
							<b>Seu pedido está em análise e os ingressos serão
							enviados para seu e-mail assim que o
							pagamento for confirmado.</b>
							Verifique a caixa de spam se não encontrar a mensagem.
						</p>
					</div>
					<div class="numero_pedido">
						<p class="numero">
							Seu pedido com o número
							<a href="minha_conta.php?pedido=<?php echo $_GET['pedido']; ?>" <?php echo (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) ? 'target="_blank"' : ''; ?>><?php echo $_GET['pedido']; ?></a> está emprocesso de análise.
						</p>
						<p class="minha_conta">
							Você pode conferir essa compra e as
							anteriores acessando a <a href="minha_conta.php" <?php echo (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) ? 'target="_blank"' : ''; ?>>minha conta</a>
						</p>
					</div>
				</div>
				<div class="row container__imprima">

				<?php if ($boleto_url) { ?>
				<div class="imprima_agora"><a href="<?php echo $boleto_url; ?>" target="_new"><div class="icone"></div>Imprima agora seu boleto.</a></div>
				<?php } ?>
				<?php if ($debito_url) { ?>
				<div class="imprima_agora pague_agora"><a href="<?php echo $debito_url; ?>" target="_new"><div class="icone"></div>Clique aqui para efetuar o débito online.</a></div>
				<?php } ?>

				<?php if ((isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) { ?>
					<div class="imprima_agora nova_venda"><a href="etapa0.php">NOVA VENDA</a></div>
				<?php } ?>

            	<?php require 'detalhes_pedido.php';?>
				</div>
			</div>
		</div>

		<div id="texts">
			<div class="centraliza">
				<p>Muito obrigado por escolher a <?php echo multiSite_getName(); ?> para a compra de seus ingressos.</p>

				<p>Fique por dentro das principais atrações em cartaz na sua cidade através do nosso Guia de Espetáculos enviado por email. Adicione o email <?php multiSite_getEmail("marketing"); ?> ao seu catálogo de endereços para receber nossos emails na sua caixa de entrada.</p>

				<p>Curta nossa página no <a href=“<?php echo multiSite_getFacebook(); ?>”>Facebook</a> e acompanhe diariamente as últimas novidades da nossa programação.</p>

				<p>Bom espetáculo!</p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>
</body>
</html>