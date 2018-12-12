<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
session_start();

function str_replace_once($search, $replace, $subject) {
    if (($pos = strpos($subject, $search)) !== false) {
        $ret = substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
    } else {
        $ret = $subject;
    }
    return($ret);
}

if (isset($_POST['email']) and isset($_POST['senha'])) {
	$mainConnection = mainConnection();
	
	$query = 'SELECT ID_CLIENTE FROM MW_CLIENTE WHERE CD_EMAIL_LOGIN = ? AND CD_PASSWORD = ?';
	$params = array($_POST['email'], md5($_POST['senha']));
	
	$rs = executeSQL($mainConnection, $query, $params, true);
	
	if ($rs['ID_CLIENTE']) {
		//setcookie('user', $rs['ID_CLIENTE'], $cookieExpireTime);
		$_SESSION['user'] = $rs['ID_CLIENTE'];

        if(isset($_GET['tag']) || isset($_POST['tag'])){
            $url = (($_POST['from'] == 'cadastro') ? '&tag=3._Identificaçao_-_Cadastre-se-TAG' : '&tag=3._Identificaçao_-_Autentique-se-TAG');
        }else{
            $url = '';
        }

        // se o operador nao estiver logado
		if (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) {
			$query = 'SELECT 1 FROM MW_CONFIRMACAO_EMAIL WHERE ID_CLIENTE = ?';
			
			$rs = executeSQL($mainConnection, $query, array($_SESSION['user']), true);

			// se o usuario ainda nao validou o email
			if ($rs[0]) {
				$_SESSION['confirmar_email'] = 1;
			}
		}

		$res = 'redirect.php?redirect=' . str_replace_once("&tag=3._Identificaçao_-_Autentique-se", "", $_GET['redirect']) . urlencode($url);
	} else {
		$res = false;
	}

	if ($res == "") {
		$res = getwhitelabel("uri");

		if ($_GET["isRegister"]=="true") {
			$res .= "?new=true";
		}
	}

	if ( httpReferer('etapa1') )
	{
		$arr = array();
		$arr['status'] = ( $res === false ) ? false : true;
		$res = json_encode($arr);
	}

	echo $res;

} else if (isset($_SESSION['operador']) and isset($_GET['id'])) {
	$_SESSION['user'] = $_GET['id'];
	echo 'redirect.php?redirect=' . str_replace_once("&tag=3._Identificaçao_-_Autentique-se", "", $_GET['redirect']) . urlencode($url);
}
?>