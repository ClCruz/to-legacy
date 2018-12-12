<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 27, true)) {

    if ($_GET['action'] == 'add') { /* ------------ INSERT ------------ */

        $query = "INSERT INTO DIM_TIPO_CHAMADO (DS_TIPO_CHAMADO,DT_ATUALIZACAO) VALUES (?, getdate())";
        $params = array(utf8_decode($_POST['nome']));

        if (executeSQL($connectionDw, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Tipos de Chamado (para BI)');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $query2 = "SELECT ID_TIPO_CHAMADO FROM DIM_TIPO_CHAMADO WHERE DS_TIPO_CHAMADO = ?";
            $params2 = array(utf8_encode2($_POST["nome"]));
            $rs = executeSQL($connectionDw, $query2, $params, true);
            $retorno = 'true?id='.$rs["ID_TIPO_CHAMADO"];
        }else{
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */
        $query = "UPDATE DIM_TIPO_CHAMADO SET DS_TIPO_CHAMADO = ?, DT_ATUALIZACAO = getdate() WHERE ID_TIPO_CHAMADO = ?";
        $params = array(utf8_encode2($_POST['nome']), $_GET['id']);

        if (executeSQL($connectionDw, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Tipos de Chamado (para BI)');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $retorno = 'true?id='.$_GET["id"];
        }else{
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */
        $query = 'DELETE FROM DIM_TIPO_CHAMADO WHERE ID_TIPO_CHAMADO = ?';
        $params = array($_GET['id']);
        $query2 = "SELECT ID_TIPO_CHAMADO FROM FATO_SAC WHERE ID_TIPO_CHAMADO = ?";
        $result = executeSQL($connectionDw, $query2, $params);
        if (hasRows($result)) {            
            $retorno = "Não é possível apagar este tipo de chamado, pois o mesmo está em uso!";
        } else {
            if (executeSQL($connectionDw, $query, $params)) {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Tipos de Chamado (para BI)');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);

                $retorno = 'true';
            } else {
                $retorno = sqlErrors();
            }
        }
    } else {
        $retorno = 'Este registro ainda está em uso!';
    }
}

if (is_array($retorno)) {
	if ($retorno[0]['code'] == 2627) {
		echo 'Já existe um registro cadastrado com essas informações.';
	} else {
		echo $retorno[0]['message'];
	}
} else {
	echo $retorno;
}
?>