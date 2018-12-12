<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 480, true)) {
    function inserir_eventos_para_verificacao($conn, $eventos, $pagamento) {
        $query = 'INSERT INTO MW_EXCECAO_PAGAMENTO (ID_EVENTO, ID_GATEWAY) VALUES (?, ?)';

        foreach ($eventos as $key => $value) {
            $params = array($value, $pagamento[$key]);
            executeSQL($conn, $query, $params);

            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Anti-fraude - Eventos');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($conn);
        }
    }

    function pega_gateway($conn, $id_evento){
        // $gateway_default = 8;// 8 = cielo
        $gateway_default = 6;// 6 = pagarme

        $query = "SELECT ID_GATEWAY FROM MW_EXCECAO_PAGAMENTO WHERE ID_EVENTO = ?";
        $result = executeSQL($conn, $query, array($id_evento));
        while($rr = fetchResult($result)){
            $resp = $rr['ID_GATEWAY'];
        }
        return $resp ? $resp : $gateway_default;
    }

    function remover_eventos_da_verificacao($conn, $eventos) {
        $query = 'DELETE FROM MW_EXCECAO_PAGAMENTO WHERE ID_EVENTO = ?';

        foreach ($eventos as $key => $value) {
            $params = array($value);
            executeSQL($conn, $query, $params);
        
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Anti-fraude - Eventos');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($conn);
        }
    }

    if ($_GET['action'] == 'getEventos' and isset($_GET['cboLocal'])) {

        $_GET['cboLocal'] = $_GET['cboLocal'] == 'TODOS' ? -1 : $_GET['cboLocal'];

        $query = "SELECT
                        E.ID_EVENTO,
                        E.DS_EVENTO,
                        B.DS_NOME_TEATRO, MIN(A.DT_APRESENTACAO) DT_INICIO, MAX(A.DT_APRESENTACAO) DT_FIM
                    FROM MW_EVENTO E
                    INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA
                    INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
                    INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO AND A.IN_ATIVO = 1
                    WHERE (E.ID_BASE = ? OR ? = -1) AND AC.ID_USUARIO = ? AND E.IN_ATIVO = 1
                    GROUP BY E.ID_EVENTO, E.DS_EVENTO, B.DS_NOME_TEATRO HAVING CONVERT(VARCHAR, MAX(A.DT_APRESENTACAO), 112) >= CONVERT(VARCHAR, GETDATE(), 112)
                    ORDER BY DS_EVENTO, DS_NOME_TEATRO";

        $result = executeSQL($mainConnection, $query, array($_GET['cboLocal'], $_GET['cboLocal'], $_SESSION['admin']));

        ob_start();
        $contador = 0;
        while ($rs = fetchResult($result)) {
            $id = $rs['ID_PROMOCAO'];
            $contador++;
        ?>  

            <input type="hidden" name="codEvento[]" value="<?php echo $rs['ID_EVENTO'] ?>" >
            <tr class="rs">
                <td><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
                <td><?php echo utf8_encode2($rs['DS_NOME_TEATRO']); ?></td>
                <td><?php echo $rs['DT_INICIO']->format('d/m/Y'); ?></td>
                <td><?php echo $rs['DT_FIM']->format('d/m/Y'); ?></td>
                <td class="combo_pagamento"><?php echo comboGateway('cbPagamento', pega_gateway($mainConnection, $rs['ID_EVENTO'])); ?></td>
            </tr>
        <?php
        }

        $retorno = ob_get_clean();

    } elseif ($_GET['action'] == 'save') { /* ------------ SALVAR EDICAO ------------ */

        $tamanhoArray = count($_POST['codEvento']);
        if($tamanhoArray > 0){
            remover_eventos_da_verificacao($mainConnection, $_POST['codEvento']);
            inserir_eventos_para_verificacao($mainConnection, $_POST['codEvento'], $_POST['cbPagamento']);
        }

        $retorno = true;
        

        // $_POST['evento'] = isset($_POST['evento']) ? $_POST['evento'] : array();
        // $eventos_atuais = explode(' ', $_POST['eventos_atuais']);
        // $eventos_atuais = $eventos_atuais[0] == '' ? array() : $eventos_atuais;
        
        // // ------------------------------------------------------------------------------

        // $eventos_para_remover = array_diff($eventos_atuais, $_POST['evento']);

        // if (!empty($eventos_para_remover)) {
        //     remover_eventos_da_verificacao($mainConnection, $eventos_para_remover);
        // }

        // // ------------------------------------------------------------------------------

        // $eventos_para_inserir = array_diff($_POST['evento'], $eventos_atuais);

        // if (!empty($eventos_para_inserir)) {
        //     inserir_eventos_para_verificacao($mainConnection, $eventos_para_inserir);
        // }

        // // ------------------------------------------------------------------------------

        // $retorno = true;

    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>