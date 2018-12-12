<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 500, true)) {

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

        $result = executeSQL($mainConnection, "SELECT DISTINCT P.ID_PACOTE, E.DS_EVENTO
                                                FROM MW_PACOTE P
                                                INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                                                INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                                                INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA AND AC.ID_USUARIO = ?
                                                INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
                                                INNER JOIN MW_PACOTE_RESERVA PR ON PR.ID_CLIENTE = B.ID_CLIENTE AND PR.ID_PACOTE = P.ID_PACOTE
                                                WHERE E.ID_BASE = ? AND PR.IN_ANO_TEMPORADA = ?
                                                ORDER BY DS_EVENTO",
                array($_SESSION['admin'], $_POST['local'], $_POST['ano']));

        $combo = '<select name="pacote_combo" class="inputStyle" id="pacote_combo"><option value="">Selecione um pacote...</option>';
        while ($rs = fetchResult($result)) {
            $combo .= '<option value="' . $rs['ID_PACOTE'] . '"' . (($_POST['pacote_combo'] == $rs['ID_PACOTE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
        }
        $combo .= '</select>';

        $retorno = $combo;
    }

    if (is_array($retorno)) {

        echo $retorno[0]['message'];

    } else {

        echo $retorno;

    }
}
?>