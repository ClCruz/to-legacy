<?php
require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');
session_start();

if (isset($_SESSION['operador']) and !isset($_SESSION['user'])) {
	header("Location: etapa3_2.php?assinatura=1&redirect=".urlencode(getCurrentUrl()));
	die();
}

require('acessoLogado.php');
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

	<script src="../javascripts/jquery-ui.js" type="text/javascript"></script>

	<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
	<script src="../javascripts/common.js" type="text/javascript"></script>
	<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>

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
			$('a.botao.avancar').on('click', function(e) {
				e.preventDefault();
				
				if ($(':checkbox:checked').length == 2) {
					document.location = $(this).attr('href');
				} else {
					$.dialog({text: 'Antes de continuar você deve ler e aceitar os termos do regulamento e os termos da política de privacidade.'});
				}
			});
		});
	</script>

	<style type="text/css">
		div#content {
			color: #FFF;
			background: #626567;
		    background: -moz-linear-gradient(top, #626567 0%, #2f2d2e 100%);
		    background: -webkit-linear-gradient(top, #626567 0%,#2f2d2e 100%);
		    background: linear-gradient(to bottom, #626567 0%,#2f2d2e 100%);
		    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#626567', endColorstr='#2f2d2e',GradientType=0 );
		}

		div.resumo_espetaculo {
    		margin-left: 180px;
    	}

		div.resumo_espetaculo div.resumo {
			margin: 40px 0 0 10px;
			width: 520px;
		}

		table#pedido_resumo {
			color: #000;
			background: #fff;
			background: -moz-linear-gradient(left,  #ffffff 40%, #eaeaea 100%);
			background: -webkit-linear-gradient(left,  #ffffff 40%,#eaeaea 100%);
			background: linear-gradient(to right,  #ffffff 40%,#eaeaea 100%);
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#eaeaea',GradientType=1 );
		}

		table#pedido_resumo thead td{
			padding:8px 0;
		}

		div.resumo_espetaculo div.resumo p.nome {
			text-align: center;
    		border-bottom: 3px solid #fff;
		}

		div.resumo_espetaculo div.resumo p.descricao {
			text-align: center;
		    font-size: 20px;
		    margin-top: 15px;
		}

		div.espetaculo_img img {
			width: 170px;
		}

		div.espetaculo_img {
			margin: 0 0 0 -10px;
		}

		input[type=checkbox].checkbox + label.checkbox {
			margin: 0;
			line-height: 36px;
		}

		div#botoes {
			padding: 15px 0 0;
		    float: left;
		    width: 100%;
		    border-top: 1px solid #E6E6E6;
		    background: #FFF;
		}

		div#botoes p {
		    color: #000;
		    font-size: 18px;
		    line-height: 1.6em;
		    margin-bottom: 20px;
		}

		div.resumo p.detalhe {
			font-size: 13px;
		    text-align: right;
		    padding: 10px 40px 0 0;
		}

		div#content .borda_lateral {
			position: absolute;
			display: block;
		}

		div#content .borda_lateral.esquerda {
			width: 867px;
			background: url('../images/BG_ASSINANTE_A_polygon_esq.png') no-repeat;
			background-position: center right;
		}

		div#content .borda_lateral.direita {
			width: 731px;
			background: url('../images/BG_ASSINANTE_A_polygon_direita.png') no-repeat;
			background-position: center left;
		}

		@media only screen and (max-width:640px){
			div.resumo_espetaculo {
	    		margin-left: 0;
	    	}

	    	div.resumo_espetaculo div.resumo {
	    		margin: 20px 0 0 0;
	    		width: 293px !important;
	    	}

	    	table#pedido_resumo thead td:nth-child(1), table#pedido_resumo tbody td:nth-child(1) {
	    		display: none;
	    	}

	    	table thead td, table#pedido_resumo thead td {
	    		font-size: 13px !important;
	    	}

	    	table tbody td, table#pedido_resumo tbody td {
	    		font-size: 12px !important;
	    	}

	    	div#content .borda_lateral {
				display: none;
			}

			table#pedido_resumo {
			    margin-top: 20px !important;
			}
		}
	</style>

	<script type="text/javascript">
	$(function(){
		function position() {
			var $relative_obj = $('.resumo_espetaculo'),
				content_height = $('#content').outerHeight(true);

			$('.borda_lateral.esquerda, .borda_lateral.direita')
				.height(content_height)
				.css('background-size', '100% '+(content_height*2)+'px');

			$('.borda_lateral.esquerda').position({
				of: $relative_obj,
				my: 'right center',
				at: 'left+50 center',
				collision: 'none'
			});

			$('.borda_lateral.direita').position({
				of: $relative_obj,
				my: 'left center',
				at: 'right+25 center',
				collision: 'none'
			});
		}

		$(window).on('resize', position).trigger('resize');
	});
	</script>

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

			<div class="centraliza">
				<?php
				$mainConnection = mainConnection();

				$query = 'SELECT ID_ASSINATURA, DS_ASSINATURA, DS_IMAGEM, QT_DIAS_CANCELAMENTO FROM MW_ASSINATURA WHERE ID_ASSINATURA = ?';
				$params = array($_GET['id']);
				$rs = executeSQL($mainConnection, $query, $params, true);

				$qt_dias_cancelamento = $rs['QT_DIAS_CANCELAMENTO'];
				$ds_assinatura = $rs['DS_ASSINATURA'];

				$query = 'WITH RESULTADO AS (
								SELECT AV.QT_MES_VIGENCIA, AV.VL_ASSINATURA, MAX(AV.VL_ASSINATURA) VALOR_MAXIMO
								FROM MW_ASSINATURA_VALOR AV
								WHERE AV.ID_ASSINATURA = ?
								GROUP BY AV.QT_MES_VIGENCIA, AV.VL_ASSINATURA
							)
							SELECT QT_MES_VIGENCIA, VL_ASSINATURA, (SELECT TOP 1 1 FROM MW_ASSINATURA_CLIENTE WHERE ID_CLIENTE = ?) AS IS_CLIENTE
							FROM RESULTADO
							WHERE (EXISTS (SELECT TOP 1 1 FROM MW_ASSINATURA_CLIENTE WHERE ID_CLIENTE = ?) AND VL_ASSINATURA IN (SELECT MAX(VL_ASSINATURA) FROM RESULTADO))
									OR
									(NOT EXISTS (SELECT TOP 1 1 FROM MW_ASSINATURA_CLIENTE WHERE ID_CLIENTE = ?))
							ORDER BY QT_MES_VIGENCIA';
				$params = array($_GET['id'], $_SESSION['user'], $_SESSION['user'], $_SESSION['user']);
				$result = executeSQL($mainConnection, $query, $params);

				// sqlsrv_num_rows nao esta funcionando - while para obter o numero de registros e execucao da query novamente

				$registros = 0;
				$is_cliente = false;
				while ($rsAux = fetchResult($result)) {
					$registros++;
					$is_cliente = ($rsAux['IS_CLIENTE'] == 1);
				}

				$result = executeSQL($mainConnection, $query, $params);

				if ($registros == 1) {
					$rsAux = fetchResult($result);
					$valor_unico_mes = 'R$ '.number_format($rsAux['VL_ASSINATURA'], 2, ',', '');
				} else {
					$ordinal = array('primeiro', 'segundo', 'terceiro', 'quarto', 'quinto', 'sexto', 'sétimo', 'oitavo', 'nono', 'décimo');
				}
				?>
				<div class="borda_lateral esquerda"></div>
				<div class="borda_lateral direita"></div>
				<div class="resumo_espetaculo" data-evento="<?php echo $rs['ID_ASSINATURA']; ?>">
					<div class="espetaculo_img assinatura"><?php echo ($rs['DS_IMAGEM'] ? '<img src="'.$rs['DS_IMAGEM'].'" />' : '<img src="../images/assinante_a.png" />'); ?></div>
					<div class="resumo">
						<p class="nome">FALTA APENAS UM PASSO!</p>

						<p class="descricao">
							Se você decidir por não continuar a assinatura, tudo bem,<br/>
							sem compromisso. Cancele online* em "minha conta".
						</p>

						<p class="detalhe">*Após <?php echo $qt_dias_cancelamento; ?> dias de adesão.</p>
					</div>

					<table id="pedido_resumo">
						<thead>
							<tr>
								<td width="100"></td>
								<td width="448">Período</td>
								<td width="148">Valor</td>
							</tr>
						</thead>
						<tbody>
						<?php
						if ($registros > 1) {
							while ($rs = fetchResult($result)) { ?>
							<tr>
								<td></td>
								<td>
									<?php
									if ($rs['QT_MES_VIGENCIA'] == 0) {
										echo "no ".$ordinal[$rs['QT_MES_VIGENCIA']]." mês";
									} else {
										echo "a partir do ".$ordinal[$rs['QT_MES_VIGENCIA']]." mês";
									}
									?>
								</td>
								<td>
									<?php
									if ($rs['VL_ASSINATURA'] > 0) {
										echo 'R$ '.number_format($rs['VL_ASSINATURA'], 2, ',', '');
									} else {
										echo "GRÁTIS";
									}
									?>
								</td>
							</tr>
						<?php
							}
						} else {
						?>
							<tr>
								<td></td>
								<td>valor mensal</td>
								<td><?php echo $valor_unico_mes; ?></td>
							</tr>
						<?php
						}
						?>
						</tbody>
					</table>
				</div>

				<div class="container_botoes_etapas">
					<div class="centraliza">
						<a href="assinaturaPagamento.php?id=<?php echo $_GET['id']; ?>" class="botao avancar passo5 botao_pagamento">pagamento</a>
					</div>
				</div>

			</div>
		</div>

		<div id="botoes">
			<div class="centraliza">
				<p>Confira atentamente os dados do seu pedido e as condições e endereço de entrega se for o caso.</p>

				<p>
					<input id="radio_regulamento" type="checkbox" name="regulamento" class="checkbox" value="R" />
					<label class="checkbox" for="radio_regulamento">Aceito os <a href="#" class="termos_de_uso">termos do regulamento</a> do <?php echo $ds_assinatura; ?></label>
					<br/>
					<input id="radio_privacidade" type="checkbox" name="privacidade" class="checkbox" value="P" />
					<label class="checkbox" for="radio_privacidade">Aceito os <a href="#" class="politica_de_privacidade">termos da política de privacidade</a> do <?php echo $ds_assinatura; ?></label>
				</p>

				<p align="right">Clique em avançar para concluir sua assinatura.</p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php if ($is_cliente) { ?>
		<script type="text/javascript">
			$(function(){
				$.confirmDialog({
				    text: 'Você já possui uma assinatura.',
				    detail: 'Deseja efetuar uma nova contratação?',
				    uiOptions: {
						buttons: {
						    'Não': ['leve-me para minha assinatura', function() {
								document.location = 'minha_conta.php?assinaturas=1';
						    }],
						    'Sim': ['quero uma nova assinatura', function() {
								fecharOverlay();
						    }]
						}
					}
				});
			});
		</script>
		<?php } ?>
	</div>

	<div id="overlay">
		<?php require 'termosUsoAssinatura.php'; ?>
	</div>
</body>
</html>