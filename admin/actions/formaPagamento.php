<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 7, true)) {

if ($_GET['action'] != 'delete') {
    $_POST['in_transacao_pdv'] = $_POST['in_transacao_pdv'] == 'on' ? 1 : 0;
}

if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/	
    $query = "INSERT INTO MW_MEIO_PAGAMENTO_FORMA_PAGAMENTO
              (ID_BASE, ID_MEIO_PAGAMENTO, CODFORPAGTO, DS_FORPAGTO)
              VALUES (?, ?, ?, ?)";
    $params = array($_POST['teatro'],
                    $_POST['idMeioPagamento'],
                    $_POST['idFormaPagamento'],
                    $_POST['ds_forpagto']);
    if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Formas de Pagamento');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

        $query = "UPDATE MW_MEIO_PAGAMENTO
                  SET IN_TRANSACAO_PDV = ?
                  WHERE ID_MEIO_PAGAMENTO = ?";
        $params = array($_POST['in_transacao_pdv'], $_POST['idMeioPagamento']);
        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Formas de Pagamento');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $retorno = 'true?idMeioPagamento='.$_POST['idMeioPagamento'].'&idBase='.$_POST['teatro'];
        }else{
            $retorno = sqlErrors();
        }
    } else {
        $retorno = sqlErrors();
    }
} else if ($_GET['action'] == 'update' and isset($_GET['idMeioPagamento']) and isset($_GET['idBase'])) { /*------------ UPDATE ------------*/	
    $query ="UPDATE MW_MEIO_PAGAMENTO_FORMA_PAGAMENTO SET
             ID_MEIO_PAGAMENTO = ?
             ,CODFORPAGTO = ?
             ,DS_FORPAGTO = ?
             WHERE ID_BASE = ? AND ID_MEIO_PAGAMENTO = ?";
    $params = array($_POST['idMeioPagamento'],
                    $_POST['idFormaPagamento'],
                    $_POST['ds_forpagto'],
                    $_GET['idBase'],
                    $_GET['idMeioPagamento']);
    if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Formas de Pagamento');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

        $query ="UPDATE MW_MEIO_PAGAMENTO
                 SET IN_TRANSACAO_PDV = ?
                 WHERE ID_MEIO_PAGAMENTO = ?";
        $params = array($_POST['in_transacao_pdv'], $_POST['idMeioPagamento']);
        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Formas de Pagamento');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $retorno = 'true?idMeioPagamento='.$_POST['idMeioPagamento'].'&idBase='.$_POST['teatro'];
        }else{
            $retorno = sqlErrors();
        }
    } else {
        $retorno = sqlErrors();
    }
} else if ($_GET['action'] == 'delete' and isset($_GET['idMeioPagamento']) and isset($_GET['idBase'])) {

    function preventDelete()
    {
        $retorno = 'Este registro não pode ser deletado. Favor entrar em contato com o Administrador.';
        return $retorno;
    }

    function deleteReg($mainConnection)
    {
        $query = 'DELETE FROM MW_MEIO_PAGAMENTO_FORMA_PAGAMENTO WHERE ID_BASE = ? AND ID_MEIO_PAGAMENTO = ?';
        $params = array($_GET['idBase'], $_GET['idMeioPagamento']);

        if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Formas de Pagamento');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

            $retorno = 'true';
        } else {
            $retorno = sqlErrors();
        }

        return $retorno;
    }

    /*
     * Previnir o DELETE de coisas que ja vem do VB para WEB
     * */
    switch ($_GET['idMeioPagamento'])
    {
        case '65': case '66': case '67': case '69': case '70': case '71':
        $retorno = preventDelete();
        break;

        default:
            $retorno = deleteReg($mainConnection);
    }
}

if (is_array($retorno)) {
    if ($retorno[0]['code'] == 2627) {
            echo 'Esse meio de pagamento já está cadastrado.';
    } else {
            echo $retorno[0]['message'];
    }
} else {
    echo $retorno;
}

}
?>