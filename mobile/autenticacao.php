<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (isset($_POST['usuario']) and isset($_POST['senha'])) {
    
    $query = 'SELECT ID_USUARIO, DS_NOME FROM MW_USUARIO WHERE CD_LOGIN = ? AND CD_PWW = ? AND IN_ADMIN = 1 AND IN_ATIVO = 1';
    $params = array($_POST['usuario'], md5($_POST['senha']));

    $rs = executeSQL($mainConnection, $query, $params, true);

    if ($rs['ID_USUARIO']) {
        $_SESSION['admin'] = $rs['ID_USUARIO'];

        if ($_POST['senha'] == '123456') {
            $_SESSION['senha'] = true;
        }
        try {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Login');
            $log->__set('log', $rs['DS_NOME']);
            $log->save($mainConnection);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $json = array('retorno' => 'OK', 'id' =>  $rs['ID_USUARIO']);
        echo json_encode($json);
    } else {
        echo json_encode(array('retorno' => 'Usuário e/ou senha inválidos!'));
    }
}else if(isset($_POST['id_usuario'])){
    $query = 'SELECT CONVERT(INT, IN_TELEMARKETING) AS IN_TELEMARKETING FROM MW_USUARIO WHERE ID_USUARIO = ? AND IN_ATIVO = 1';
    $params = array($_POST['id_usuario']);

    $rs = executeSQL($mainConnection, $query, $params, true);
    if($rs['IN_TELEMARKETING']){
        $json = array('retorno' => 'OK', 'operador' => $rs['IN_TELEMARKETING']);
        echo json_encode($json);
    }else{
        $json = array('retorno' => 'OK', 'operador' => 0);
        echo json_encode($json);
    }
}
?>