<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
include('../settings/Log.class.php');
session_start();

if (isset($_POST['usuario']) and isset($_POST['senha'])) {
	$mainConnection = mainConnection();
	
	$query = 'SELECT ID_USUARIO, DS_NOME FROM MW_USUARIO WHERE CD_LOGIN = ? AND CD_PWW = ? AND IN_ADMIN = 1 AND IN_ATIVO = 1';
	$params = array($_POST['usuario'], md5($_POST['senha']));
	
	$rs = executeSQL($mainConnection, $query, $params, true);
	
	if ($rs['ID_USUARIO']) {
		//setcookie('admin', $rs['ID_USUARIO'], $cookieExpireTime);
		$_SESSION['admin'] = $rs['ID_USUARIO'];
		
		if ($_POST['senha'] == '123456') {
			$_SESSION['senha'] = true;
		}

                try{
                    $log = new Log($_SESSION['admin']);
                    $log->__set('funcionalidade', 'Login');
                    $log->__set('log', $rs['DS_NOME']);
                    $log->save($mainConnection);
                }  catch (Exception $e){
                    echo $e->getMessage();
                }
                

                echo 'redirect.php?redirect=' . $_GET['redirect'];
                
	} else {
		echo 'Usuário e/ou senha inválidos!';
	}
} else if (isset($_POST['senhaOld'])) {
	if ($_POST['senhaOld'] != $_POST['senha1']) {
		if ($_POST['senha1'] == $_POST['senha2']) {
			if (isset($_POST['senha1']) and strlen($_POST['senha1']) >= 6) {
				$mainConnection = mainConnection();
				
				$query = 'SELECT ID_USUARIO FROM MW_USUARIO WHERE ID_USUARIO = ? AND CD_PWW = ? AND IN_ADMIN = 1 AND IN_ATIVO = 1';
				$params = array($_SESSION['admin'], md5($_POST['senhaOld']));
				$rs = executeSQL($mainConnection, $query, $params, true);
				
				if ($rs['ID_USUARIO']) {
					$query = 'UPDATE MW_USUARIO SET CD_PWW = ? WHERE ID_USUARIO = ? AND CD_PWW = ? AND IN_ADMIN = 1 AND IN_ATIVO = 1';
					$params = array(md5($_POST['senha1']), $_SESSION['admin'], md5($_POST['senhaOld']));
					$result = executeSQL($mainConnection, $query, $params);
					
					if ($result) {
						unset($_SESSION['senha']);
						echo 'redirect.php?redirect=' . $_GET['redirect'];
					} else {
						echo 'Ocorreu um erro ao alterar a senha.<br><br>Tente novamente.<br><br>Se o erro persistir, favor entrar em contato com o suporte.';
					}
				} else {
					echo 'Senha atual não confere.';
				}
			} else {
				echo 'A senha nova deve ter, no mínimo, 6 caracteres.';
			}
		} else {
			echo 'A confirmação de senha não confere.';
		}
	} else {
		echo 'A nova senha deve ser diferente da atual.';
	}
}
?>