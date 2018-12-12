<?php
ob_start();
require_once('../settings/functions.php');
require_once('../settings/settings.php');
require_once('../settings/multisite/unique.php');

if ($is_manutencao === true) {
	header("Location: manutencao.php");
	die();
}

require('acessoLogado.php');
require('verificarBilhetes.php');
require('verificarServicosPedido.php');
require('verificarLimitePorCPF.php');
require('verificarEntrega.php');
require('verificarAssinatura.php');

require('verificarDadosCadastrais.php');

if (isset($_COOKIE['entrega'])) {
    $action = "verificatempo";
    $etapa = 4;
    $idestado = $_COOKIE['entrega'];
    require('calculaFrete.php');
}

require('../settings/pagseguro_functions.php');

$mainConnection = mainConnection();
$rs = executeSQL($mainConnection, 'SELECT COUNT(1) FROM MW_RESERVA WHERE ID_SESSION = ?', array(session_id()), true);
$qtdIngressos = $rs[0] >= 9 ? '0'.$rs[0] : $rs[0];

$json = json_encode(array('descricao' => ($_POST ? '2.' : '1.') .' etapa5 - ' . ($_POST ? 'envio de dados' : 'formulario cartao')));
include('logiPagareChamada.php');

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

	<script src="../javascripts/jquery.are-you-sure.js" type="text/javascript"></script>

	<script type="text/javascript" src="../javascripts/contagemRegressiva.js?until=<?php echo tempoRestante(); ?>"></script>
	<script type="text/javascript" src="../javascripts/formCartao.js"></script>

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

	  function changeDadosCartaoBySelect(type) {
		$(".container_card_others").show();
		  switch (type) {
			  case "card_others":
				  $(".container_card_paypal").hide();
				  $(".container_card_others").show();
				  $(".botao_pagamento").show();
			  break;
			  case "card_paypal":
			  	  $(".botao_pagamento").hide();
				  $(".container_card_others").hide();
				  $(".container_card_paypal").show();
			  break;
		  }
	  }

	  $(document).ready(function() {
		$(".card_paypal").click(function() {
			changeDadosCartaoBySelect("card_paypal");
		});
		$(".card_others").click(function() {
			changeDadosCartaoBySelect("card_others");
		});

		try {
			paypal.Button.render({
			env: 'production', // Or production - sandbox
			commit: true, // Show a 'Pay Now' button
			client: {
				sandbox:    'AQ8hnNgMxLFzukyzkMMwfMFAkmHTBxv6uAuZ95rZLOOHdW6bAx7MyeMpGpVIzBN2DoIighIYNIBke1qO',
				production: 'AagFfpGw_irk48l196ERKmqntzzTw8kDmf2glId43tuRENMx0-DIqUMq_kgZewGos3-8WjmoeLKXYvIP'
			},
			locale: 'ja_JP',
			style:  {
				label: 'checkout',
			tagline: false
			},

			payment: function(data, actions) {
				//aqui
				return actions.payment.create({
					payment: {
						transactions: [
							{
								amount: { total: <?php echo str_replace(",",".", $_COOKIE['total_exibicao']);?>, currency: 'BRL' }
							}
						]
					}
				});
			},
			onCancel: function(data, actions) {
				$.dialog({text: 'Pagamento cancelado no paypal.'});
				//console.log(data);
			/* 
			* Buyer cancelled the payment 
			*/
			},

			onError: function(err) {
				$.dialog({text: 'Ocorreu um erro ao tentar pagar no paypal. ' + err});
				//console.log(err);
			/* 
			* An error occurred during the transaction 
			*/
			},

			onAuthorize: function(data, actions) {
				return actions.payment.execute().then(function(payment) {
					$("#paypal_data").val(JSON.stringify(data));
					$("#paypal_payment").val(JSON.stringify(payment));
					$('.botao_pagamento').click();
					//console.log(data);
					//console.log(payment);
					// The payment is complete!
					// You can now show a confirmation message to the customer
				});
			}

			}, '#paypal-button');
		} catch (e) {
			
		}
	  });
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
		<img src="../images/ico_erro_notificacao.svg">
		<div class="container_erros"></div>
		<a>fechar</a>
		</div>
		</div>
		

<div class="modal fade right" id="sideModalTR" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-side modal-top-right" role="document">
		
		<div class="modal-content">
		<div class="modal-header">
          <h4 class="modal-title w-100" id="myModalLabel">
				Passo <b>5 de 5</b> <?php echo ($_COOKIE['total_exibicao'] != 0 ? 'escolha a bandeira de sua preferência' : 'clique em finalizar para processamento do pedido'); ?></h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
				<div class="modal-body">
				<?php if ($_COOKIE['total_exibicao'] != 0) { ?>
					Escolha o cartão de crédito de sua preferência, preencha os dados e clique em Pagar para finalizar o seu pedido.
					<?php } else { ?>
					Clique em finalizar para o processar o seu pedido.
					<?php } ?>
					
        </div>
      </div>
    </div>
  </div>

			<form id="dadosPagamento" action="formCartao.php" method="post">
				<input type="hidden" name="paypal_data" id="paypal_data" />
				<input type="hidden" name="paypal_payment" id="paypal_payment" />
				<div class="centraliza">
					<div class="descricao_pag">
						<div class="img">
							<img src="../images/ico_black_passo5.png">
						</div>
						<div class="descricao">
						<p class="nome title__page">5. <?php echo ($_COOKIE['total_exibicao'] != 0 ? 'Pagamento' : 'Finalização'); ?></p>
										
							<button type="button" class="btn btn-primary botao btn__help" data-toggle="modal" data-target="#sideModalTR"></button>
						<div class="sessao">
						<p class="tempo" id="tempoRestante"></p>
						<p class="mensagem">
						Após essse prazo seu pedido será cancelado<br>
						automaticamente e os lugares liberados
						</p>
						</div>
						</div>
						</div>
						<div class="descricao_pag">
					
						<?php require('formCartao.php'); ?>

						
					<?php if (!isset($_SESSION['operador'])) { ?>
					</div>

					</div>

				
					<div class="img_cod_cartao"><img src=""><p></p></div>
					<div class="explicacao_envio_presente"><p>Um e-mail será enviado ao presenteado em seu nome, contendo um link para impressão do e-ticket</p></div>

					<?php } ?>
				</div>
			
			</form>

		<div id="texts" style="margin-top: 40px"> 
			<div class="centraliza">
				<p>
					<?php if ($_COOKIE['total_exibicao'] != 0) { ?>
					Escolha o cartão de crédito de sua preferência, preencha os dados e clique em Pagar para finalizar o seu pedido.
					<?php } else { ?>
					Clique em finalizar para o processar o seu pedido.
					<?php } ?>
				</p>
			</div>
		</div>

		</div>
		

		<?php include "footer.php"; ?>

			<div class="container_botoes_etapas">
						<div class="centraliza">
							<a href="etapa4.php?eventoDS=<?php echo $_GET['eventoDS']; ?><?php echo $campanha['tag_voltar']; ?>" class="botao voltar passo4">confirmação</a>
								<div class="resumo_carrinho">
									<span class="quantidade"><?php echo $qtdIngressos; ?></span>
									<span class="frase">ingressos selecionados <br>para essa compra</span>
								</div>
							<a href="etapa5.php?eventoDS=<?php echo $_GET['eventoDS']; ?><?php echo $campanha['tag_avancar']; ?>" class="botao avancar passo6 botao_pagamento <?php echo $_COOKIE['total_exibicao'] == 0 ? 'finalizar' : '' ?>">Finalizar</a>
						</div>
					</div>

		<?php //include "selos.php"; ?>
	</div>

	<script>
		(function (a, b, c, d, e, f, g) {a['CsdpObject'] = e; a[e] = a[e] || function() {(a[e].q = a[e].q || []).push(arguments)}, a[e].l = 1 * new Date(); f = b.createElement(c), g = b.getElementsByTagName(c)[0]; f.async = 1; f.src = d; g.parentNode.insertBefore(f, g)})(window, document, 'script', '//device.clearsale.com.br/p/fp.js', 'csdp');
		csdp('app', 'ae6af083e9');
		csdp('sessionid', '<?php echo session_id(); ?>');
	</script>

	<?php
	if ($carregar_pagarme_lib) {
		echo '<script src="https://assets.pagar.me/js/pagarme.min.js"></script>';
		echo '<script type="text/javascript" src="../javascripts/pagarme.js"></script>';
		echo '<intput type="hidden" id="loaded_pagarme" value="1" >';
	}
	if ($carregar_pagseguro_lib) {
		if ($_ENV['IS_TEST']) {
			echo '<script type="text/javascript" src="https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>';
		} else {
			echo '<script type="text/javascript" src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>';
		}
		$sessionId = getPagSeguroSessionId();
		$amount = str_replace(',', '.', $_COOKIE['total_exibicao']);
		echo "<script type='text/javascript'>var pagseguro = {sessionId: '$sessionId', amount: $amount};</script>";
		echo '<script type="text/javascript" src="../javascripts/pagseguro.js"></script>';
	}
	if ($carregar_cielo_lib) {
		echo '<script src="https://assets.pagar.me/js/pagarme.min.js"></script>';
		echo '<script type="text/javascript" src="../javascripts/cielo.js"></script>';
	}
	if ($carregar_paypal) {
		echo '<script src="https://www.paypalobjects.com/api/checkout.js"></script>';
	}
	?>
</body>
</html>