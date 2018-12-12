<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 370, true)) {

    if ($_GET['action'] == 'liberar') {

        $query = "UPDATE MW_PACOTE_RESERVA SET IN_STATUS_RESERVA = 'C', DT_HR_TRANSACAO = GETDATE() WHERE ID_PACOTE = ? AND ID_CLIENTE = ? AND ID_CADEIRA = ?";

        foreach ($_POST['pacote'] as $i => $pacote) {
            $params = array($pacote, $_POST['cliente'][$i], $_POST['cadeira'][$i]);

            executeSQL($mainConnection, $query, $params);

            $errors = sqlErrors();

            if (empty($errors)) {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Liberação de Lugar para Venda');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);

                $retorno = 'ok';
            } else {
                $retorno = $errors;
                break;
            }
        }

    } else if ($_GET['action'] == 'load_pacotes') {

        $retorno = comboPacote('pacote_combo', $_SESSION['admin'], $_POST['pacote_combo'], $_POST['local'], 3);
    }

    if (is_array($retorno)) {

        echo $retorno[0]['message'];

    } else {

        echo $retorno;

    }
}
?>