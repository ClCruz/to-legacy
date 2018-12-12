<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 470, true)) {

    if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */
        $query = 'SELECT 1 FROM MW_ORIGEM WHERE DS_ORIGEM = ? AND ID_ORIGEM != ?';
        $params = array(utf8_encode2($_POST['descricao']), $_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);
        if (hasRows($result)) {
            echo 'Já existe um registro cadastrado com essa descrição.';
            exit();
        }

        $query = "UPDATE MW_ORIGEM SET DS_ORIGEM = ? WHERE ID_ORIGEM = ?";
        $params = array(utf8_encode2($_POST['descricao']), $_GET['id']);

        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Origem');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

        $query = 'SELECT 1 FROM MW_PEDIDO_VENDA WHERE ID_ORIGEM = ?';
        $params = array($_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);
        if (hasRows($result)) {
            echo 'Não foi possível excluir!<br/><br/>Esse registro já está em uso.';
            exit();
        }

        $query = 'DELETE FROM MW_ORIGEM WHERE ID_ORIGEM = ?';
        $params = array($_GET['id']);

        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Origem');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
            
            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }
    }

    if (is_array($retorno)) {
        if ($retorno[0]['code'] == 547) {
            echo 'Não foi possível excluir!<br/><br/>Esse registro já está em uso.';
        } else {
            echo $retorno[0]['message'];
        }
    } else {
        echo $retorno;
    }
}
?>