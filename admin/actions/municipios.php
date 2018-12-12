<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 28, true)) {

    if ($_GET['action'] == 'add') { /* ------------ INSERT ------------ */

        $query = "INSERT INTO MW_MUNICIPIO (DS_MUNICIPIO, ID_ESTADO) VALUES (?, ?); SELECT @@IDENTITY AS 'ID';";
        $params = array(utf8_encode2($_POST['nome']), $_POST["idestado"]);

        $rs = executeSQL($mainConnection, $query, $params);
        $retorno = 'true?id=' . $rs["ID"];
        if (sqlErrors ()) {
            $retorno = sqlErrors();
        } else {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Municípios (para BI)');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
        }
    } else if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */
        $query = "UPDATE MW_MUNICIPIO SET DS_MUNICIPIO = ?, ID_ESTADO = ? WHERE ID_MUNICIPIO = ?";
        $params = array(utf8_encode2($_POST['nome']), $_POST['idestado'], $_GET["id"]);

        if (executeSQL($mainConnection, $query, $params)) {
            $retorno = 'true?id=' . $_GET["id"];
                
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Municípios (para BI)');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */
        $query = 'DELETE FROM MW_MUNICIPIO WHERE ID_MUNICIPIO = ?';
        $params = array($_GET['id']);
        $query2 = "SELECT ID_LOCAL_EVENTO FROM MW_LOCAL_EVENTO WHERE ID_MUNICIPIO = ?";
        $result = executeSQL($mainConnection, $query2, $params);
        if (hasRows($result)) {
            $retorno = "Não é possível apagar este município, pois o mesmo está em uso!";
        } else {
            if (executeSQL($mainConnection, $query, $params)) {
                $retorno = 'true';
                
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Municípios (para BI)');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);
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
