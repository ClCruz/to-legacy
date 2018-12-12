<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
	
if (isset($_GET['email'])) {
	$mail_sent = false;

	require_once('../settings/functions.php');

	$mainConnection = mainConnection();

	$query = 'SELECT DS_NOME FROM MW_CLIENTE WHERE CD_EMAIL_LOGIN = ?';
	$params = array($_GET['email']);
	
	
	$rs = executeSQL($mainConnection, $query, $params, true);
	if (!empty($rs)) {
		require_once('../settings/settings.php');
		require_once('../settings/Template.class.php');

		$novaSenha = substr(md5(date('r', time())), -8);
		

		$query = 'UPDATE MW_CLIENTE SET CD_PASSWORD = ? WHERE CD_EMAIL_LOGIN = ?';
		$params = array(md5($novaSenha), $_GET['email']);

		if (executeSQL($mainConnection, $query, $params)) {
			$nameto = $rs['DS_NOME'];
			$to = $_GET['email'];
			$subject = "Solicitação de Nova Senha";
			$namefrom = multiSite_getTitle();

			$valores['nome'] = $rs['DS_NOME'];
			$valores['novaSenha'] = $novaSenha;

			$caminhoHtml = getwhitelabeltemplate("email:recover");
			
			
			
			$tpl = new Template($caminhoHtml);
			
			foreach ($valores as $key => $value) {
				if (is_array($value)) {
						foreach ($value as $detalhes) {
								foreach ($detalhes as $key2 => $value2) {
										try { $tpl->$key2 = $value2; } catch (Exception $e) { /* variaveis nao encontradas */ }
								}
								$tpl->parseBlock(strtoupper($key), true);
						}
				} else {
						try { $tpl->$key = $value; } catch (Exception $e) { /* variaveis nao encontradas */ }
				}
			}		

			//$subject = '=?UTF-8?b?' . base64_encode('Solicitação de Nova Senha') . '?=';

			//$namefrom = '=?UTF-8?b?' . base64_encode(multiSite_getTitle()).'?=';
			
			$from = '';


			//define the body of the message.
			ob_start(); //Turn on output buffering

			$tpl->show();
		?>
		<?php
			//copy current buffer contents into $message variable and delete current output buffer
			$message = ob_get_clean();

			$mail_sent = authSendEmail($from, $namefrom, $to, $nameto, $subject, $message);
						
		}

		if ( httpReferer('etapa1') )
		{
			$arr = array();
			$arr['status'] = $mail_sent;

			//Msg só é exibida se mail_sent for FALSE
			$arr['msg'] = 'Verifique o endereço informado e tente novamente. Se o erro persistir, favor entrar em contato com o suporte.';

			$resp = json_encode($arr);
		}
		else
		{
			$resp = ($mail_sent === true ? 'true' : 'Verifique o endereço informado e tente novamente.<br><br>Se o erro persistir, favor entrar em contato com o suporte.');
		}


	} else {

		if ( httpReferer('etapa1') )
		{
			$arr = array();
			$arr['status'] = false;
			$arr['msg'] = 'Este e-mail ainda não esta cadastrado';

			$resp = json_encode($arr);
		}
		else
		{
			$resp = 'Esse e-mail não está cadastrado ainda.<br><br>Clique no botão ao lado para se cadastrar!';
		}
	}

	echo $resp;
}

?>
