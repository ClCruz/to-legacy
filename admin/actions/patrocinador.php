<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 22, true)) {

    if ($_GET['action'] == 'add') { /* ------------ INSERT ------------ */
        $query = 'SELECT 1 FROM MW_PATROCINADOR WHERE DS_NOMPATROCINADOR = ?';
        $params = array(utf8_encode2($_POST['nome']));
        $result = executeSQL($mainConnection, $query, $params);
        if (hasRows($result)) {
            echo 'Já existe um registro cadastrado com esse nome.';
            exit();
        }

        $query = "INSERT INTO MW_PATROCINADOR
					(DS_NOMPATROCINADOR)
					VALUES (?)";
        $params = array(utf8_encode2($_POST['nome']));

        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Patrocinadores');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $query = 'SELECT ID_PATROCINADOR FROM MW_PATROCINADOR WHERE DS_NOMPATROCINADOR = ?';
            $params = array(utf8_encode2($_POST['nome']));
            $rs = executeSQL($mainConnection, $query, $params, true);

            $retorno = 'true?id=' . $rs['ID_PATROCINADOR'];
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */
        $query = "UPDATE MW_PATROCINADOR SET
					DS_NOMPATROCINADOR = ?
				WHERE
					ID_PATROCINADOR = ?";
        $params = array(utf8_encode2($_POST['nome']), $_GET['id']);

        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Patrocinadores');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $retorno = 'true?id=' . $_GET['id'];
        } else {
            $retorno = sqlErrors();
        }
    } else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */
        $query = 'DELETE FROM MW_PATROCINADOR WHERE ID_PATROCINADOR = ?';
        $params = array($_GET['id']);

        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Patrocinadores');
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