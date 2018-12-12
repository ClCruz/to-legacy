<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 430, true)) {

    if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

        $query = 'EXEC prc_apaga_promocao ?';
        $params = array($_GET['id']);
        $result = executeSQL($mainConnection, $query, $params);

        if ($result) {
            $retorno = 'true';

            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Gestão de Promoções');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
        } else {
            $retorno = sqlErrors();
        }

    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>