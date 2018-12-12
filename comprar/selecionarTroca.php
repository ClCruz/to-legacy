<?php
session_start();

require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');

require('acessoLogado.php');

$mainConnection = mainConnection();
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

	<script type="text/javascript">
	$(function(){
		$('form#dados').on('click', '.pacote a', function(event) {
	        event.preventDefault();
	        var $this = $(this),
	        	href = $this.attr('href').split('?'),
	        	$target = $(this).closest('tr').next('tr').find('td:first');

        	if (!$target.is('.hidden')) {
                $target.addClass('hidden');
		    } else {
		    	if ($target.html() != '') {
		    		$target.removeClass('hidden');
		    	} else {
			        $.ajax({
			            url: href[0],
			            data: href[1],
			            success: function(data) {
			                $target.html(data).removeClass('hidden');
			            }
			        });
			    }
		    }
	    });
	});
	</script>

	<title><?php echo multiSite_getTitle()?></title>

	<style>
		form#dados table tr td {
			padding-bottom: 20px;
		}

		form#dados span.pedido_resumo table {
			margin: 0;
		}

		form#dados span.pedido_resumo tr td {
			padding-left: 15px;
			padding-bottom: 10px;
		}

		div.pacotes {
			min-width: 700px;
		}
	</style>
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
						<img src="../images/ico_black_passo2.png">
					</div>
					<div class="descricao">
						<p class="nome">Efetuar troca de lugares</p>
						<p class="descricao">
			                Selecione uma das assinaturas abaixo para continuar o processo de troca<br>
			                de lugares. Ao clicar no título das assinaturas serão exibidas as apresentações.
			            </p>
					</div>
				</div>

				<div class="pacotes">
		            <p class="titulo">Você solicitou a troca de <b><?php echo count($_SESSION['assinatura']['cadeira']); ?></b> lugar(es).</p>
		            <p class="mini_titulo">Pacotes</p>

					<form name="dados" id="dados">
					<!-- <p>
						Selecione uma das assinaturas abaixo para continuar o processo de troca de lugares.<br/>
						Ao clicar sobre o título das assinaturas, serão exibidas as apresentações que compõem cada assinatura.
					</p> -->
	                    <table>
	                        <tbody>
	                            <?php
	                            $result = executeSQL($mainConnection, "SELECT ID_PACOTE, DS_EVENTO, P.ID_APRESENTACAO
																		FROM MW_PACOTE P
																		INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
																		INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
																		WHERE DT_FIM_FASE3 >= GETDATE()
																		AND EXISTS (SELECT 1 FROM MW_PACOTE_APRESENTACAO PA WHERE PA.ID_PACOTE = P.ID_PACOTE)
																		ORDER BY DS_EVENTO");
	                            while ($rs = fetchResult($result)) {
	                                ?>
	                                <tr>
	                                    <td class="pacote titulo"><a href="<?php echo "detalhes_historico.php?origem=PACOTE&historico=" . $rs['ID_PACOTE']; ?>"><?php echo utf8_encode2($rs['DS_EVENTO']); ?></a></td>
	                                    <td class="selecionar"><a href="<?php echo 'etapa1.php?apresentacao=' . $rs['ID_APRESENTACAO'] . '&eventoDS=' . utf8_encode2($rs['DS_EVENTO']); ?>">Selecionar lugares para troca</a></td>
	                                </tr>
	                                <tr><td colspan="2" class="hidden"></td></tr>
	                                <?php
	                            }
	                            ?>
	                        </tbody>
	                    </table>
					</form>
				</div>

				<div class="container_botoes_etapas">
				<a href="minha_conta.php?assinaturas=1" class="botao voltar passo0">voltar</a>
				</div>
			</div>
		</div>

		<div id="texts">
			<div class="centraliza">
				<p></p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>
</body>
</html>