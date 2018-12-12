<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('../log4php/log.php');
session_start();

if (isset($_POST['login']) and isset($_POST['senha'])) {
    $mainConnection = mainConnection();

    log_trace("Consultando Operador... ");
    $query = 'SELECT ID_USUARIO, IN_TELEMARKETING, IN_PDV FROM MW_USUARIO WHERE CD_LOGIN = ? AND CD_PWW = ? AND IN_ATIVO = 1';
    //$query = 'SELECT ID_USUARIO, IN_TELEMARKETING, IN_PDV FROM MW_USUARIO WHERE CD_LOGIN = ? AND IN_ATIVO = 1';
    $params = array($_POST['login'], md5($_POST['senha']));

    $result = executeSQL($mainConnection, $query, $params);
    $rs = fetchResult($result);

    log_trace("Retorno: " . print_r($rs, true));

    if (isset($_POST['pdv']) and $_POST['pdv'] == 1) {
        // VALIDA OPERADOR DE PDV
        if (hasRows($result) and !$rs['IN_PDV']) {
            echo 'Acesso Negado!';
        } else if ($rs['ID_USUARIO']) {
            $_SESSION['operador'] = $rs['ID_USUARIO'];
            $_SESSION['usuario_pdv'] = 1;
            if ($_POST['senha'] == '123456') {
                $_SESSION['senha'] = true;
            }
            echo 'redirect.php?redirect=' . $_GET['redirect'];
        } else {
            echo 'Combinação de login/senha inválida<br>Por favor tente novamente.';
        }
    } else {
        // VALIDA OPERADOR TELEMARKETING
        if (hasRows($result) and !$rs['IN_TELEMARKETING']) {
            echo 'Acesso Negado!!';
        } else if ($rs['ID_USUARIO']) {
            //setcookie('user', $rs['ID_CLIENTE'], $cookieExpireTime);
            $_SESSION['operador'] = $rs['ID_USUARIO'];
            $_SESSION['usuario_pdv'] = 0;
            if ($_POST['senha'] == '123456') {
                $_SESSION['senha'] = true;
            }
            echo 'redirect.php?redirect=' . $_GET['redirect'];
        } else {
            echo 'Combinação de login/senha inválida<br>Por favor tente novamente.';
        }
    }
} else if (isset($_POST['senhaOld'])) {

    if ($_POST['senhaOld'] != $_POST['senha1']) {
        if ($_POST['senha1'] == $_POST['senha2']) {
            if (isset($_POST['senha1']) and strlen($_POST['senha1']) >= 6) {
                $mainConnection = mainConnection();

                $query = 'SELECT ID_USUARIO FROM MW_USUARIO WHERE ID_USUARIO = ? AND CD_PWW = ? AND IN_ATIVO = 1';
                $params = array($_SESSION['operador'], md5($_POST['senhaOld']));
                $rs = executeSQL($mainConnection, $query, $params, true);

                if ($rs['ID_USUARIO']) {
                    $query = 'UPDATE MW_USUARIO SET CD_PWW = ? WHERE ID_USUARIO = ? AND CD_PWW = ? AND IN_ATIVO = 1';
                    $params = array(md5($_POST['senha1']), $_SESSION['operador'], md5($_POST['senhaOld']));
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