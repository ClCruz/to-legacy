<?php
require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');
require('acessoLogado.php');

if ($_POST) {
	if ($_POST['alterar_assinatura']) {
		require('alterarDadosAssinatura.php');
	} else {
		require('processarDadosAssinatura.php');
	}
	die('Um erro inesperado ocorreu. Por favor, tente novamente e caso o erro persista entre em contato com o suporte.');
}

$mainConnection = mainConnection();

if ($_GET['action'] == 'alterar_assinatura') {
	$editar = true;
		$query = "SELECT 1, DC.DS_NOME_TITULAR, DC.CD_NUMERO_CARTAO
				FROM MW_DADOS_CARTAO DC
				INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_DADOS_CARTAO = DC.ID_DADOS_CARTAO
				WHERE AC.ID_ASSINATURA_CLIENTE = ? AND AC.ID_CLIENTE = ?";
	$params = array($_GET['id'], $_SESSION['user']);
	$rs = executeSQL($mainConnection, $query, $params, true);

	$cipher = new Cipher('1ngr3ss0s');

	$titular_cartao = $cipher->decrypt($rs['DS_NOME_TITULAR']);
	$numero_cartao = '************' . substr($cipher->decrypt($rs['CD_NUMERO_CARTAO']), -4);

} else {
	$query = "SELECT 1 FROM MW_ASSINATURA WHERE ID_ASSINATURA = ?";
	$params = array($_GET['id']);
	$rs = executeSQL($mainConnection, $query, $params, true);
}

if ($rs[0] != 1) header("Location: " . multiSite_getURI("URI_SSL"));
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

	<script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
	<script src="../javascripts/common.js" type="text/javascript"></script>

	<script src="../javascripts/jquery.are-you-sure.js" type="text/javascript"></script>

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

			<form id="dadosPagamento" action="assinaturaPagamento.php" method="post">
				<?php if ($_GET['action'] == 'alterar_assinatura') { ?>
				<input type="hidden" name="alterar_assinatura" value="1"/>
				<?php } ?>
				<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>"/>
				<div class="centraliza">
					<div class="descricao_pag">
						<div class="img">
							<img src="../images/ico_black_passo5.png">
						</div>
						<div class="descricao">
							<p class="nome"><?php echo $editar ? 'Alterar ' : ''; ?>Pagamento</p>
							<p class="descricao">
								escolha a bandeira de sua preferência
							</p>
						</div>
						<?php
						$valor_primeiro_pagamento = getPrimeiroValorAssinatura($_SESSION['user'], $_GET['id']);
 
				    	$query = "SELECT cd_meio_pagamento, ds_meio_pagamento, nm_cartao_exibicao_site 
				                      from mw_meio_pagamento
				                      where in_ativo = 1 and id_meio_pagamento in (select id_meio_pagamento from mw_assinatura_meio_pagamento where in_ativo = 1)
				                      order by ds_meio_pagamento";
				    	$result = executeSQL($mainConnection, $query);

				    	$parcelas = 1;
				    	?>
				        <input type="hidden" name="usuario_pdv" value="<?php echo (isset($_SESSION["usuario_pdv"])) ? $_SESSION["usuario_pdv"] : 0; ?>" />

				    	<div class="container_cartoes">
				    		<p class="frase">Escolha o meio de pagamento</p>
				    		<div class="inputs">
				    			<?php
				    			if ($_ENV['IS_TEST']) {
				    			?>
				    			<div class="container_cartao">
				    				<input id="997" type="radio" name="codCartao" class="radio" value="997"
				    					imgHelp="../images/cartoes/help_default.png" formatoCartao="0000-0000-0000-0000" formatoCodigo="000">
				    				<label class="radio" for="997">
				    					<img src="../images/cartoes/ico_default.png"><br>
				    				</label>
				    				<p class="nome">teste</p>
				    			</div>
				    			<?php
				    			}
				    			while ($rs = fetchResult($result)) {
				                    if ($bin != '' and in_array($rs['cd_meio_pagamento'], array('892', '893'))) continue;
				    			?>
				    			<div class="container_cartao">
				    				<input id="<?php echo $rs['cd_meio_pagamento']; ?>" type="radio" name="codCartao" class="radio" value="<?php echo $rs['cd_meio_pagamento']; ?>"
				    					imgHelp="../images/cartoes/help_<?php echo file_exists('../images/cartoes/help_'.$rs['nm_cartao_exibicao_site'].'.png') ? utf8_encode2($rs['nm_cartao_exibicao_site']) : 'default'; ?>.png"
				    					formatoCartao="<?php echo $rs['nm_cartao_exibicao_site'] == 'Amex' ? '0000-000000-00000' : '0000-0000-0000-0000'; ?>"
				    					formatoCodigo="<?php echo $rs['nm_cartao_exibicao_site'] == 'Amex' ? '0000' : '000'; ?>">
				    				<label class="radio" for="<?php echo $rs['cd_meio_pagamento']; ?>">
				    					<img src="../images/cartoes/ico_<?php echo file_exists('../images/cartoes/ico_'.$rs['nm_cartao_exibicao_site'].'.png') ? utf8_encode2($rs['nm_cartao_exibicao_site']) : 'default'; ?>.png"><br>
				    				</label>
				    				<p class="nome"><?php echo $rs['nm_cartao_exibicao_site'] ? utf8_encode2($rs['nm_cartao_exibicao_site']) : utf8_encode2($rs['ds_meio_pagamento']); ?></p>
				    			</div>
				    			<?php
				    			}
				    			?>

				    		</div>
				    	</div>
				    	<div class="container_dados" style="display:block;">
				                <?php
				                if($_SESSION['usuario_pdv'] == 0){
				                ?>
				                <p class="frase"><span class="alt">Dados do cartão</span></p>
				                <div class="linha">
				                    <div class="input">
				                        <p class="titulo">nome do titular</p>
				                        <input type="text" name="nomeCartao" value="<?php echo $titular_cartao; ?>">
				                        <div class="erro_help">
				                            <p class="help">como impresso no cartão</p>
				                        </div>
				                    </div>
				                <?php
				                }
				                ?>
				                    <div class="input parcelas">
				                        <p class="titulo">forma de pagamento</p>
				                        <select name="parcelas">
				                            <?php
				                            if ($valor_primeiro_pagamento > 0) {
					                            for ($i = 1; $i <= $parcelas; $i++) {
					                                $valor = number_format(str_replace(',', '.', $valor_primeiro_pagamento) / $i, 2, ',', '');
					                                $desc = $i == 1 ? 'à vista' : $i . 'x';

					                                echo "<option value='$i'>$desc - R$ $valor</option>";
					                            }
					                        } else {
					                        	echo "<option value='1'>GRÁTIS</option>";
					                        }
				                            ?>
				                        </select>
				                    </div>
				                <?php
				                if($_SESSION['usuario_pdv'] == 0){
				                ?>
				                </div>
				                <div class="linha">
				                    <div class="input">
				                        <p class="titulo">número do cartão</p>
				                        <input type="text" name="numCartao" value="<?php echo $numero_cartao; ?>">
				                        <div class="erro_help">
				                            <p class="help">XXXX-XXXX-XXXX-XXXX</p>
				                        </div>
				                    </div>
				                    <div class="input codigo">
				                        <p class="titulo">código de segurança</p>
				                        <input type="text" name="codSeguranca">
				                        <div class="erro_help">
				                            <p class="help"><a href="#" class="meu_codigo_cartao">onde está meu código?</a></p>
				                        </div>
				                    </div>
				                    <div class="input data">
				                        <p class="titulo">validade</p>
				                        <div class="mes">
				                            <?php echo comboMeses('validadeMes', '', true, true); ?>
				                        </div>
				                        <div class="ano">
				                            <?php echo comboAnos('validadeAno', '', date('Y'), date('Y') + 15, true); ?>
				                        </div>
				                        <div class="erro_help">
				                            <p class="help">insira a data de validade</p>
				                        </div>
				                    </div>
				                </div>
				                <?php
				                }
				                ?>
				                <?php if (!isset($_SESSION['operador'])) { ?>
				                <div class="linha">
				                    <p class="frase" style="margin-bottom: -10px;">Autenticidade</p>
				                </div>
				                <?php } ?>
				    	</div>
					</div>
					
					<div class="container_botoes_etapas">
						<div class="centraliza">
							<?php if (!$editar) { ?>
							<a href="assinatura.php?id=<?php echo $_GET['id']; ?>" class="botao voltar passo4">confirmação</a>
							<?php } ?>

							<a href="assinaturaPagamento.php" class="<?php echo 'botao avancar passo6 botao_pagamento'.($editar ? ' submit salvar_dados' : ($valor_primeiro_pagamento == 0 ? ' finalizar' : '')); ?>">pagamento</a>
						</div>
					</div>
					<div class="img_cod_cartao"><img src=""><p></p></div>

					<?php if (!isset($_SESSION['operador'])) { ?>
					<div class="compra_captcha">
						<script type="text/javascript">var brandcaptchaOptions = {lang: 'pt'};</script>
						<?php
						require_once('../settings/brandcaptchalib.php');
						echo brandcaptcha_get_html($recaptcha['public_key']);
						?>
					</div>
					<?php } ?>
				</div>
			
			</form>

		</div>

		<div id="texts">
			<div class="centraliza">
				<p>Escolha o cartão de crédito de sua preferência, preencha os dados e clique em Pagar para finalizar o seu pedido.</p>
			</div>
		</div>

		<?php include "footer.php"; ?>

		<?php //include "selos.php"; ?>
	</div>

	<script>
		(function (a, b, c, d, e, f, g) {a['CsdpObject'] = e; a[e] = a[e] || function() {(a[e].q = a[e].q || []).push(arguments)}, a[e].l = 1 * new Date(); f = b.createElement(c), g = b.getElementsByTagName(c)[0]; f.async = 1; f.src = d; g.parentNode.insertBefore(f, g)})(window, document, 'script', '//device.clearsale.com.br/p/fp.js', 'csdp');
		csdp('app', 'ae6af083e9');
		csdp('sessionid', '<?php echo session_id(); ?>');
	</script>
</body>
</html>