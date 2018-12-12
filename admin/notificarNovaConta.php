<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');

if (isset($_GET['email'])) {
	require_once('../settings/functions.php');
	
	$mainConnection = mainConnection();
	
	$query = 'SELECT DS_NOME FROM MW_USUARIO WHERE DS_EMAIL = ?';
	$params = array($_GET['email']);
	$rs = executeSQL($mainConnection, $query, $params, true);
	
	if (!empty($rs)) {
		$novaSenha = substr(md5(date('r', time())), -8);
		
		$query = 'UPDATE MW_USUARIO SET CD_PWW = ? WHERE DS_EMAIL = ?';
		$params = array(md5($novaSenha), $_GET['email']);
		
		if (executeSQL($mainConnection, $query, $params)) {
			$nameto = $rs['DS_NOME'];
			$to = $_GET['email'];
			$subject = utf8_decode('Solicitação de Nova Senha');
			
			$from = multiSite_getEmail("lembrete");
			$namefrom = utf8_decode(multiSite_getTitle());

			//define the body of the message.
			ob_start(); //Turn on output buffering
		?>
<p>&nbsp;</p>
<div style="background-color: rgb(255, 255, 255); padding-top: 5px; padding-right: 5px; padding-bottom: 5px; padding-left: 5px; margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px; ">
<p style="text-align: left; font-family: Arial, Verdana, sans-serif; font-size: 12px; ">&nbsp;<img alt="" src="<?php echo multiSite_getLogoFullURI()?>" /><span style="font-family: Verdana; "><strong>GEST&Atilde;O E ADMINISTRA&Ccedil;&Atilde;O DE INGRESSOS</strong></span></p>
<h3 style="font-family: Arial, Verdana, sans-serif; font-size: 12px; "><strong>&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;</strong><strong>SOLICIT</strong><strong>A&Ccedil;&Atilde;O&nbsp;DE&nbsp;NOVA SENHA</strong></h3>
<h2 style="margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">Ol&aacute;,&nbsp;</span><span style="color: rgb(181, 9, 56); "><span style="font-size: smaller; "><span style="font-family: Verdana, sans-serif; "><?php echo $rs['DS_NOME']; ?></span></span></span><span style="font-size: medium; "><span style="font-family: Verdana; "><strong><span><br />
</span></strong></span></span></h2>
<p style="text-align: left; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 97, 97); "><span style="font-family: Verdana; "><span style="font-size: 10pt; ">Voc&ecirc; solicitou uma nova senha no nosso site.</span></span></span><br />
&nbsp;</p>
<p style="text-align: left; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 97, 97); "><span style="font-family: Verdana; "><span style="font-size: 10pt; ">Para efetuar o login, a partir de agora, voc&ecirc; deve utilizar a seguinte senha:</span></span></span></p>
<div style="line-height: normal; margin-left: 40px; "><strong><em><?php echo $novaSenha; ?></em></strong></div>
<p style="text-align: left; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><em><span style="font-size: small; "><span style="color: rgb(97, 97, 98); "><span style="font-family: Verdana, sans-serif; ">obs-Voc&ecirc; pode alterar sua senha a qualquer momento no <a href="<?php echo multiSite_getURICompra("admin/login.php?action=trocarSenha"); ?>">nosso site</a>.</span></span></span></em></p>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;</span></div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Atenciosamente</span></div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; ">&nbsp;</div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo multiSite_getName()?>&nbsp;&nbsp;</span><span style="color: rgb(98, 98, 97); "><?php echo multiSite_getPhone()?></span></div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
<div style="line-height: normal; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="font-family: Verdana, sans-serif; font-size: 8pt; ">&nbsp;</span><span style="font-family: Verdana, sans-serif; font-size: 8pt; "><br />
</span></div>
<p style="margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); "><span style="font-size: smaller; ">Esse &eacute; um e-mail autom&aacute;tico. N&atilde;o &eacute; necess&aacute;rio respond&ecirc;-lo.</span></span></p>
</div>
<p>&nbsp;</p>
		<?php
			//copy current buffer contents into $message variable and delete current output buffer
			$message = ob_get_clean();
		}
	}
}

require_once('header.php');
?>
<div id='content'>
	<div id='app'>
		<?php if (authSendEmail($from, $namefrom, $to, $nameto, $subject, $message)) { ?>
		<h2>Nova senha gerada com sucesso!</h2>
		<p>Em alguns minutos você deve receber um e-mail com a nova senha.</p>
		<?php } ?>
	</div>
</div>

<?php require_once('footer.php'); ?>