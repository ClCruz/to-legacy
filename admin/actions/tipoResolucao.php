<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 27, true)) {

    if ($_GET['action'] == 'add') { /* ------------ INSERT ------------ */

        $query = "INSERT INTO DIM_TIPO_RESOLUCAO (DS_TIPO_RESOLUCAO,DT_ATUALIZACAO) VALUES (?, getdate())";
        $params = array(utf8_encode2($_POST['nome']));

        if (executeSQL($connectionDw, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Tipos de Resolução (para BI)');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $query2 = "SELECT ID_TIPO_RESOLUCAO FROM DIM_TIPO_RESOLUCAO WHERE DS_TIPO_RESOLUCAO = ?";
            $params2 = array($_POST["nome"]);
            $rs = executeSQL($connectionDw, $query2, $params, true);
            $retorno = 'true?id='.$rs["ID_TIPO_RESOLUCAO"];
        }else{
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */
        $query = "UPDATE DIM_TIPO_RESOLUCAO SET DS_TIPO_RESOLUCAO = ?, DT_ATUALIZACAO = getdate() WHERE ID_TIPO_RESOLUCAO = ?";
        $params = array(utf8_encode2($_POST['nome']), $_GET['id']);

        if (executeSQL($connectionDw, $query, $params)) {    
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Tipos de Resolução (para BI)');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $retorno = 'true?id='.$_GET["id"];
        }else{
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */
        $query = 'DELETE FROM DIM_TIPO_RESOLUCAO WHERE ID_TIPO_RESOLUCAO = ?';
        $params = array($_GET['id']);
        $query2 = "SELECT ID_TIPO_RESOLUCAO FROM FATO_SAC WHERE ID_TIPO_RESOLUCAO = ?";
        $result = executeSQL($connectionDw, $query2, $params);
        if (hasRows($result)) {            
            $retorno = "Não é possível apagar este tipo de resolução, pois o mesmo está em uso!";
        } else {
            if (executeSQL($connectionDw, $query, $params)) {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Tipos de Resolução (para BI)');
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