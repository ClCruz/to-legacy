<?php
require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');
session_start();
$edicao = true;

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));

require('verificarServicosPedido.php');
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

	<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
	<script type="text/javascript" src="../javascripts/contagemRegressiva.js?until=<?php echo tempoRestante(); ?>"></script>
	<script type="text/javascript" src="../javascripts/carrinho.js"></script>
	<script type="text/javascript" src="../javascripts/dadosEntrega.js"></script>
	<?php echo $scriptServicosPorPedido; ?>
	
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

	<?php
	$mainConnection = mainConnection();
	
	$query = 'EXEC dbo.pr_clear_morethanone_presentation ?';
	$params = array(session_id());
	$result = executeSQL($mainConnection, $query, $params);
	
	$query = 'WITH PARTICIPACOES_NA_RESERVA AS (
					SELECT DISTINCT ASS.ID_ASSINATURA, PC.CODTIPPROMOCAO
					FROM MW_RESERVA R
					INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
					INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
					LEFT JOIN MW_CONTROLE_EVENTO CE ON CE.ID_EVENTO = E.ID_EVENTO
					INNER JOIN MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = CE.ID_PROMOCAO_CONTROLE OR PC.ID_BASE = E.ID_BASE OR (PC.IN_TODOS_EVENTOS = 1 AND PC.ID_BASE IS NULL AND PC.IN_ATIVO = 1)
					INNER JOIN MW_ASSINATURA_PROMOCAO AP ON AP.ID_PROMOCAO_CONTROLE = PC.ID_PROMOCAO_CONTROLE
					INNER JOIN MW_ASSINATURA ASS ON ASS.ID_ASSINATURA = AP.ID_ASSINATURA
					WHERE R.ID_SESSION = ? AND PC.CODTIPPROMOCAO IN (8,9)
				)
				SELECT
	                A.DS_ASSINATURA,
	                SUM(CASE WHEN P.ID_PROMOCAO IS NULL THEN 0 ELSE 1 END) AS QT_BILHETES_DISPONIVEIS,
	                PC.CODTIPPROMOCAO,
	                MAX(PC.PERC_DESCONTO_VR_NORMAL) AS PERC_DESCONTO_VR_NORMAL,
	                PC.IN_VALOR_SERVICO
                
                FROM MW_ASSINATURA A
                INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_ASSINATURA = A.ID_ASSINATURA
                INNER JOIN MW_ASSINATURA_PROMOCAO AP ON AP.ID_ASSINATURA = AC.ID_ASSINATURA
                INNER JOIN MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = AP.ID_PROMOCAO_CONTROLE AND PC.CODTIPPROMOCAO IN (8,9)
                LEFT JOIN MW_PROMOCAO P ON P.ID_ASSINATURA_CLIENTE = AC.ID_ASSINATURA_CLIENTE AND ID_PEDIDO_VENDA IS NULL
                
                INNER JOIN PARTICIPACOES_NA_RESERVA PNR ON PNR.ID_ASSINATURA = AC.ID_ASSINATURA AND PNR.CODTIPPROMOCAO = PC.CODTIPPROMOCAO
                
                WHERE AC.ID_CLIENTE = ? AND (AC.IN_ATIVO = 1 OR (AC.IN_ATIVO = 0 AND AC.DT_PROXIMO_PAGAMENTO > GETDATE()))
                
                GROUP BY A.DS_ASSINATURA, PC.CODTIPPROMOCAO, PC.IN_VALOR_SERVICO
                ORDER BY DS_ASSINATURA, CODTIPPROMOCAO';
	$params = array(session_id(), $_SESSION['user']);
	$result = executeSQL($mainConnection, $query, $params);

	if (hasRows($result)) {
		$msg = '';
		$lastRS = array();
		while ($rs = fetchResult($result)) {
			if ($rs['CODTIPPROMOCAO'] == 8 AND $rs['QT_BILHETES_DISPONIVEIS'] > 0) {
				$msg .= "Você tem {$rs['QT_BILHETES_DISPONIVEIS']} ingresso(s) disponível(eis) de {$rs['DS_ASSINATURA']} que pode(rão) ser utilizado(s) para este evento.<br/>";
			} elseif ($rs['CODTIPPROMOCAO'] == 9) {
				if ($lastRS['DS_ASSINATURA'] == $rs['DS_ASSINATURA']) {
					$rs['PERC_DESCONTO_VR_NORMAL'] = number_format($rs['PERC_DESCONTO_VR_NORMAL'], 0);
					$msg = substr($msg, 0, -6) . ",<br/>além disto você também pode utilizar seu DESCONTO de até {$rs['PERC_DESCONTO_VR_NORMAL']}%";
				} else {
					$msg .= "Você pode utilizar seu DESCONTO de {$rs['DS_ASSINATURA']}";
				}

				if (!$rs['IN_VALOR_SERVICO']) {
					$msg .= " e ainda não pagar a taxa de serviço para este evento";
				}

				$msg .= ".<br/>";
			}
			$lastRS = $rs;
		}

		if ($msg != '')
			echo '<script type="text/javascript">$(function(){
				$.confirmDialog({
					text:"",
					detail:"'.$msg.'",
					uiOptions: {
						buttons: {
							"Ok, entendi": ["", function(){
								fecharOverlay();
							}]
						}
					}
				})
			})</script>';
	}
	?>
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
				<div class=" row descricao_pag">
					<div class="img">
						<img src="../images/ico_black_passo2.png">
					</div>
					<div class="descricao">
						<p class="title__page">2. Seleção de tipo</p>
						<div class="sessao">
							<p class="tempo" id="tempoRestante"></p>
							<p class="mensagem">
								Após essse prazo seu pedido será cancelado<br>
								automaticamente e os lugares liberados
							</p>
						</div>
					</div>
				
				<?php require "resumoPedido.php"; ?>
				<br />
				<br />
				<div class="container_botoes_etapas">
					<div class="centraliza">
						<a href="etapa1.php?<?php echo $_COOKIE['lastEvent']; ?><?php echo $campanha['tag_voltar']; ?>" class="botao voltar passo1">Voltar</a>
						<div class="resumo_carrinho">
							<span class="quantidade"></span>
							<span class="frase">selecionado(s) <br>para essa compra</span>
						</div>
						<a href="etapa3.php?redirect=<?php echo urlencode('etapa4.php?eventoDS=' . $_GET['eventoDS'] . $campanha['tag_avancar']); ?><?php echo $campanha['tag_avancar']; ?>" class="botao avancar passo3 botao_avancar">Avançar</a>
					</div>
				</div>
			</div>
				
				<div id="texts">
				<div class="centraliza">
				
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
			Passo <b>2 de 5</b> escolha descontos e vantagens
			
		</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
		<p>Confira o(s) assento(s) escolhido(s), o preço, a forma de entrega e clique em avançar para continuar com o processo de compra.</p>
		
		<p>Formas de entrega:</p>
		<p>1. E-ticket</p>
		<p>A compra será enviada para o e-mail do seu cadastro no site. Se houver necessidade de reimpressão, acesse o menu "Minha conta" para acessar o seu pedido.</p>
		<p>No caso de promoções é obrigatório a apresentação do documento que comprove o benefício no local.</p>
		</div>
	</div>
</div>
</div>
</body>
</html>