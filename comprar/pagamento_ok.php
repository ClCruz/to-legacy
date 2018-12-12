<?php
session_start();
require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');

require('acessoLogado.php');
require_once('../settings/settings.php');
require_once('../settings/MCAPI.class.php');

$mainConnection = mainConnection();
if ( isset($_POST['action']) && $_POST['action'] == 'clube_rchlo' ) {
	$query = 'INSERT INTO mw_riachuelo_clube (id_cliente, id_pedido_venda) VALUES(?,?)';
	$paramns = array(
		$_SESSION['user'],
		$_GET['pedido']
	);

	fetchAssoc( executeSQL($mainConnection, $query, $paramns) );
	$status = array(
		'status' => true
	);

	//TODO - ajustar cipopup para poder receber um Popup como callback, adaptação técnica para aguardar alguns segundos para evitar bug
	sleep(3);
	
	echo json_encode($status);
	die();
}


$json = json_encode(array('descricao' => '8. chamada do pagamento_ok - codigo_pedido=' . $_GET['pedido'], 'Post='=>$_GET ));
include('logiPagareChamada.php');

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));

$query = 'SELECT
			C.DS_NOME,
			PV.VL_TOTAL_PEDIDO_VENDA,
			PV.VL_TOTAL_TAXA_CONVENIENCIA,
			PV.VL_FRETE,
			ISNULL(PV.DS_CIDADE_ENTREGA, C.DS_CIDADE) DS_CIDADE,
			ISNULL(E1.DS_ESTADO, E2.DS_ESTADO) DS_ESTADO
			FROM MW_PEDIDO_VENDA PV
			INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
			LEFT JOIN MW_ESTADO E1 ON E1.ID_ESTADO = PV.ID_ESTADO
			LEFT JOIN MW_ESTADO E2 ON E2.ID_ESTADO = C.ID_ESTADO
			WHERE PV.ID_PEDIDO_VENDA = ? AND PV.ID_CLIENTE = ?';
$params = array($_GET['pedido'], $_SESSION['user']);
$rs = executeSQL($mainConnection, $query, $params, true);

$valorPagamento = $rs['VL_TOTAL_PEDIDO_VENDA'];
$valorServico = $rs['VL_TOTAL_TAXA_CONVENIENCIA'];
$valorFrete = $rs['VL_FRETE'];
$cidade = utf8_encode2($rs['DS_CIDADE']);
$estado = utf8_encode2($rs['DS_ESTADO']);
$nome = $rs['DS_NOME'];

$json = json_encode(array('descricao' => '9. fim da chamada do pagamento_ok - codigo_pedido=' . $_GET['pedido'], 'Post='=>$_GET ));
include('logiPagareChamada.php');

$scriptTransactionAnalytics = "
_gaq.push(['_addTrans',
	'" . $_GET['pedido'] . "',
	'".multiSite_getName()."',
	'" . $valorPagamento . "',
	'" . $valorServico . "',
	'" . $valorFrete . "',
	'" . $cidade . "',
	'" . $estado . "',
	'BRA'
]);";

$dados_pedido = array(
	'id' => $_GET['pedido'],
	'email_id' => $_COOKIE['mc_eid'],
	'total' => $valorPagamento,
	'shipping' => $valorFrete,
	'tax' => $valorServico,
	'store_id' => 1,
	'store_name' => multiSite_getName(),
	'campaign_id' => $_COOKIE['mc_cid']
);

$query = "SELECT COUNT(1) QUANTIDADE, R.ID_APRESENTACAO_BILHETE,
				E.ID_EVENTO, E.DS_EVENTO, ISNULL(LE.DS_LOCAL_EVENTO, B.DS_NOME_TEATRO) DS_NOME_TEATRO, B.ds_nome_base_sql,
				AB.VL_LIQUIDO_INGRESSO, AB.DS_TIPO_BILHETE, B.DS_MSG_DEPOIS_VENDA, B.DS_URL_DEPOIS_VENDA
			FROM MW_ITEM_PEDIDO_VENDA R
			INNER JOIN MW_PEDIDO_VENDA PV ON PV.ID_PEDIDO_VENDA = R.ID_PEDIDO_VENDA
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO AND A.IN_ATIVO = '1'
			INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = '1'
			INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
			INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE AND AB.IN_ATIVO = '1'
			LEFT JOIN MW_LOCAL_EVENTO LE ON E.ID_LOCAL_EVENTO = LE.ID_LOCAL_EVENTO
			WHERE R.ID_PEDIDO_VENDA = ? AND PV.ID_CLIENTE = ?
			GROUP BY R.ID_APRESENTACAO_BILHETE, B.ds_nome_base_sql,
				E.ID_EVENTO, E.DS_EVENTO, ISNULL(LE.DS_LOCAL_EVENTO, B.DS_NOME_TEATRO),
				AB.VL_LIQUIDO_INGRESSO, AB.DS_TIPO_BILHETE,
				B.DS_MSG_DEPOIS_VENDA, B.DS_URL_DEPOIS_VENDA";
$params = array($_GET['pedido'], $_SESSION['user']);
$result = executeSQL($mainConnection, $query, $params);

$has_riachuelo = false;
while ($rs = fetchResult($result)) {
	$evento_info = getEvento($rs['ID_EVENTO']);

	$id_item = $rs['ID_EVENTO'] . '_' . $rs['ID_APRESENTACAO_BILHETE'];
	$ds_item = utf8_encode2($rs['DS_EVENTO'] . ' - ' . $evento_info['nome_teatro']);
	$tipo = utf8_encode2($rs['DS_TIPO_BILHETE']);
	$valor = $rs['VL_LIQUIDO_INGRESSO'];
	$quantidade = $rs['QUANTIDADE'];
	$id_evento = $rs['ID_EVENTO'];

	$scriptTransactionAnalytics .= "
	_gaq.push(['_addItem',
		'" . $_GET['pedido'] . "',
		'" . $id_item . "',
		'" . $ds_item . "',
		'" . $tipo . "',
		'" . $valor . "',
		'" . $quantidade . "'
	]);";

	$dados_pedido['items'][] = array(
		'product_id' => $id_item,
		'product_name' => $ds_item,
		'category_id' => $rs['ID_APRESENTACAO_BILHETE'],
		'category_name' => $tipo,
		'qty' => $quantidade,
		'cost' => $valor
	);

	//if ($rs['ds_nome_base_sql'] == 'CI_THEATRO_MUNICIPAL') {
	if ($rs['ds_nome_base_sql'] == 'CI_RIACHUELO') {
		//Verifica se o cliente ja aceitou alguma vez
		$query = 'SELECT COUNT(1) as participa FROM mw_riachuelo_clube WHERE id_cliente = '.$_SESSION['user'];
		$participa = fetchAssoc( executeSQL($mainConnection, $query) );
		$participa = $participa[0]['participa'];

		if ($participa == 0) {
			$has_riachuelo = true;
		}
	}

	if ($rs['DS_MSG_DEPOIS_VENDA']) {
		$msg_pos_venda = $rs['DS_MSG_DEPOIS_VENDA'];
		$link_pos_venda = $rs['DS_URL_DEPOIS_VENDA'];
	}

}

if ($_COOKIE['mc_eid'] and $_COOKIE['mc_cid']) {
	$mcap = new MCAPI($MailChimp['api_key']);
	$mcap->campaignEcommOrderAdd($dados_pedido);
}

limparCookies();
unset($_SESSION['origem']);
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

    <!-- Criteo GTM Integration -->
    <script type="text/javascript" src="//static.criteo.net/js/ld/ld.js" async="true"></script>
    <!-- Criteo GTM Integration -->

	<script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
	<script src="../javascripts/cicompra.js" type="text/javascript"></script>

	<script src="../javascripts/jquery.cookie.js" type="text/javascript"></script>
	<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
	<script src="../javascripts/common.js" type="text/javascript"></script>
	<script src="../javascripts/simpleFunctions.js" type="text/javascript"></script>
	<script src="../javascripts/cipopup.js" type="text/javascript"></script>
	
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
	  _gaq.push(['_setAccount', '<?php echo multiSite_getGoogleAnalytics(); ?>']);
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

	<?php if($has_riachuelo): ?>
		<script type="text/javascript">
			$(document).ready(function () {
				ciPopup.init('rlo');
			});

			function clubeRchlo() {
				$.ajax({
					method: 'post',
					data: { action: 'clube_rchlo' },
					dataType: 'json',
					success: function (data) {
						if (data.status) {
							console.log('success');
							console.log(data);
							//ciPopup.init('rlo_ok',3000);
						}
					},
					error: function (data) {
						console.log('error');
						console.log(data);
					}
				})
			}
		</script>
	<?php endif;?>

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

	<?php
	if ($msg_pos_venda) {
		
		$_search = array("'", "\r", "\n");
		$_replace = array('"', "<br/>", "<br/>");

		$msg_pos_venda = str_replace($_search, $_replace, $msg_pos_venda);

		echo "<script>$(function(){";

		if (!$link_pos_venda){
			?>
			$.confirmDialog({
				text: '',
				detail: '<?php echo utf8_encode2($msg_pos_venda); ?>',
				uiOptions: {
					buttons: {
						'Ok, entendi': ['', fecharOverlay]
					}
				}
			});
			<?php
		} else {
			?>
			$.confirmDialog({
				text: '',
				detail: '<?php echo str_replace("'", '"', utf8_encode2($msg_pos_venda)); ?>',
				uiOptions: {
					buttons: {
						'Não, obrigado': ['', fecharOverlay],
						'Sim, por favor': ['', function(){window.open('<?php echo str_replace("'", '"', $link_pos_venda); ?>');fecharOverlay();}]
					}
				}
			});
			<?php
		}
		echo "})</script>";
	}
	?>

	<title><?php echo multiSite_getTitle()?></title>
</head>
<body>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNN2XTF" 
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->

	<div class="hidden">
		<div id="rlo">
			Quero receber maiores informações e participar do Clube de Vantagens.
			<div id="clube_riachuelo">
				<a class="btn_cancel" href="#" onclick="ciPopup.hide()">Não, obrigado!</a>
				<a class="btn_gradient" href="#" onclick="ciPopup.hide(clubeRchlo())">Sim, eu quero!</a>
			</div>
			<img src="../images/promocional/rchlo_popup_logo.jpg" style="margin-top: 25px">
		</div>
		<div id="rlo_ok">
			Obrigado por se Cadastrar no Clube do Teatro Riachuelo!
		</div>
	</div>
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
							<b>Seus ingressos foram enviados para
							seu e-mail,</b> verifique a caixa de spam
							se não encontrar a mensagem.
						</p>
					</div>
					<div class="numero_pedido">
						<p class="numero">
							Seu pedido com o número
							<a href="minha_conta.php?pedido=<?php echo $_GET['pedido']; ?>" <?php echo (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) ? 'target="_blank"' : ''; ?>><?php echo $_GET['pedido']; ?></a> foi realizado.
						</p>
						<p class="minha_conta">
							Você pode conferir essa compra e as
							anteriores acessando a <a href="minha_conta.php" <?php echo (isset($_SESSION['operador']) and is_numeric($_SESSION['operador'])) ? 'target="_blank"' : ''; ?>>minha conta</a>
						</p>
					</div>
				</div>
				<div class="row container__imprima">
				<div class="imprima_agora"><a href="reimprimirEmail.php?pedido=<?php echo $_GET['pedido']; ?>" target="_new"><div class="icone"></div>Imprima agora seus ingressos.</a></div>
				<?php if ((isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) { ?>
					<div class="imprima_agora nova_venda"><a href="etapa0.php">NOVA VENDA</a></div>
				<?php } ?>
				<div class="euvou" style="display: none">
            	<a href="javascript:popup('https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($homeSite . 'espetaculos/' . $id_evento); ?>', 600, 350);"><div class="icone"></div>Eu vou! Convide seus amigos no <img src="../images/ico_facebook_logo.png"></a></div>

            	<?php require 'detalhes_pedido.php';?>
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
		</div>


		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>
	<!-- Google Code for Compra de Ingresso Conversion Page -->
	<script type="text/javascript">
	/* <![CDATA[ */
	var google_conversion_id = 1038667940;
	var google_conversion_language = "en";
	var google_conversion_format = "3";
	var google_conversion_color = "ffffff";
	var google_conversion_label = "IwGiCLKwrQMQpKGj7wM";
	var google_conversion_value = <?php echo $valorPagamento; ?>;
	/* ]]> */
	</script>
	<script type="text/javascript" src="https://www.googleadservices.com/pagead/conversion.js">
	</script>
	<noscript>
	<div style="display:inline;">
	<img height="1" width="1" style="border-style:none;" alt="" src="https://www.googleadservices.com/pagead/conversion/1038667940/?value=<?php echo $valorPagamento; ?>&amp;label=IwGiCLKwrQMQpKGj7wM&amp;guid=ON&amp;script=0"/>
	</div>
	</noscript>

	<?php
	$query = "SELECT
				STUFF((SELECT DISTINCT ','+CONVERT(VARCHAR, E.ID_BASE)
						FROM MW_ITEM_PEDIDO_VENDA I
						INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
						INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
						WHERE I.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA
				FOR XML PATH('')),1,1,'')
				FROM MW_PEDIDO_VENDA P
				WHERE P.ID_PEDIDO_VENDA = ?";
	$params = array($_GET['pedido']);
	$rs = executeSQL($mainConnection, $query, $params, true);

	if (in_array(186, explode(',', $rs[0]))) {
		?>
		<!-- Facebook Pixel Code -->
		<script>
		!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
		n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
		document,'script','https://connect.facebook.net/en_US/fbevents.js');

		fbq('init', '1505133836395917');
		fbq('track', "PageView");
		fbq('track', 'Purchase', {value: '0.00', currency:'BRL'});
		</script>
		<noscript><img height="1" width="1" style="display:none"
		src="https://www.facebook.com/tr?id=1505133836395917&ev=PageView&noscript=1"
		/></noscript>

		<!-- Google Code for Compra - Compreingressos.com Conversion Page -->
		<script type="text/javascript">
		/* <![CDATA[ */
		var google_conversion_id = 985944725;
		var google_conversion_language = "en";
		var google_conversion_format = "3";
		var google_conversion_color = "ffffff";
		var google_conversion_label = "ABTNCOvqwGYQlaWR1gM";
		var google_remarketing_only = false;
		/* ]]> */
		</script>
		<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
		</script>
		<noscript>
		<div style="display:inline;">
		<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/985944725/?label=ABTNCOvqwGYQlaWR1gM&amp;guid=ON&amp;script=0"/>
		</div>
		</noscript>
		<?php
	}
	?>
    <!-- Criteo data layer -->
    <script type="text/javascript">
        var $resumoEspetaculo = $('.resumo_espetaculo');
        var product_list = [];
        var dataLayer = dataLayer || [];

        var DataLayer = (function() {
            var product_list = [];

            function Ticket(idProduct, sellPrice, quantity) {
                this.idProduct = idProduct;
                this.sellPrice = sellPrice;
                this.quantity = quantity;
            }

            return {

                init: function($espetaculo) {
                    this.$resumoEspetaculo = $espetaculo;
                    this.eventoId = this.$resumoEspetaculo.data('evento');
                    this.product_list = [];
                    this.cacheDOM();
                },

                cacheDOM: function() {
                    this.$pedidoResumo = this.$resumoEspetaculo.find('#pedido_resumo');
                    this.$tiposIngressoCel = this.$pedidoResumo.find('td.tipo');
                    this.$spanTotalIngresso = this.$pedidoResumo.find('span.valorIngresso');
                },

                build: function() {
                    var totalIngressos = this.$spanTotalIngresso.length,
                        eventoId = this.eventoId;

                    var ticket = new Ticket(eventoId, this.$spanTotalIngresso.eq(0).text(), totalIngressos);
                    product_list.push(ticket);
                },

                getProductList: function() {
                    return product_list;
                }
            }
        } ());

        $resumoEspetaculo.each(function() {
            DataLayer.init($(this));
            DataLayer.build();
        });

        dataLayer.push({
            'PageType': 'Transactionpage',
            'HashedEmail': '',
            'ProductTransactionProducts': DataLayer.getProductList(),
            'TransactionID': $('.numero').children('a').text()
        });
    </script>

</body>
</html>
