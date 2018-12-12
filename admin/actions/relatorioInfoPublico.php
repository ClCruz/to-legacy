<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 390, true)) {

    if ($_GET['action'] == 'busca' and isset($_GET['cboPeca']) and isset($_GET['cboData']) and isset($_GET['cboHora'])) {

        $conn = getConnection($_GET['cboTeatro']);

        $result = executeSQL($conn,
                            "SELECT DISTINCT
                                    C.NOME,
                                    S.NOMSALA,
                                    ST.NOMSETOR,
                                    SD.NOMOBJETO,
                                    CSV.DATHRENTRADA,
                                    LG.CODVENDA,
                                    CLI.IN_ASSINANTE,
                                    TB.TIPBILHETE,
                                    CSV.CODAPRESENTACAO,
                                    CSV.INDICE,
                                    CASE WHEN P.ID_PACOTE IS NOT NULL THEN 1 ELSE 0 END AS PACOTE,
                                    CASE WHEN PA.ID_PACOTE IS NOT NULL THEN 1 ELSE 0 END AS APRESENTACAO_FILHA,
                                    CASE WHEN PR.ID_PACOTE IS NOT NULL THEN 1 ELSE 0 END AS IN_ASSINANTE_EVENTO

                                FROM TABLUGSALA LG

                                INNER JOIN TABCONTROLESEQVENDA CSV ON
                                CSV.INDICE = LG.INDICE
                                AND CSV.CODAPRESENTACAO = LG.CODAPRESENTACAO

                                INNER JOIN TABLANCAMENTO L ON
                                L.CODAPRESENTACAO = CSV.CODAPRESENTACAO
                                AND L.INDICE = CSV.INDICE
                                AND L.CODTIPLANCAMENTO = 1
                                AND NOT EXISTS (SELECT 1 FROM TABLANCAMENTO L2
                                                WHERE L2.CODTIPLANCAMENTO = 2
                                                AND L.CODAPRESENTACAO = L2.CODAPRESENTACAO
                                                AND L.INDICE = L2.INDICE
                                                AND L.NUMLANCAMENTO = L2.NUMLANCAMENTO)

                                LEFT JOIN TABHISCLIENTE HC ON
                                HC.CODAPRESENTACAO = CSV.CODAPRESENTACAO
                                AND HC.INDICE = CSV.INDICE
                                AND HC.NUMLANCAMENTO = L.NUMLANCAMENTO

                                LEFT JOIN TABCLIENTE C ON
                                C.CODIGO = HC.CODIGO

                                INNER JOIN TABSALDETALHE SD ON
                                SD.INDICE = CSV.INDICE 

                                INNER JOIN TABAPRESENTACAO A ON
                                A.CODAPRESENTACAO = CSV.CODAPRESENTACAO 

                                INNER JOIN TABSALA S ON
                                S.CODSALA = A.CODSALA

                                LEFT JOIN TABSETOR ST ON
                                ST.CODSALA = A.CODSALA 
                                AND CONVERT(VARCHAR, ST.CODSETOR) = SUBSTRING(CSV.CODBAR, 6 ,1)

                                INNER JOIN TABTIPBILHETE TB ON
                                TB.CODTIPBILHETE = LG.CODTIPBILHETE

                                LEFT JOIN CI_MIDDLEWAY..MW_ITEM_PEDIDO_VENDA IPV ON
                                IPV.INDICE = CSV.INDICE
                                AND IPV.CODVENDA = LG.CODVENDA COLLATE LATIN1_GENERAL_CI_AS

                                LEFT JOIN CI_MIDDLEWAY..MW_PEDIDO_VENDA PED ON
                                PED.ID_PEDIDO_VENDA = IPV.ID_PEDIDO_VENDA

                                LEFT JOIN CI_MIDDLEWAY..MW_CLIENTE CLI ON
                                CLI.ID_CLIENTE = PED.ID_CLIENTE
                                
                                LEFT JOIN CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA ON
                                PA.ID_APRESENTACAO = IPV.ID_APRESENTACAO
                                
                                LEFT JOIN CI_MIDDLEWAY..MW_PACOTE_RESERVA PR ON
                                PR.ID_CLIENTE = CLI.ID_CLIENTE
                                AND PR.ID_PACOTE = PA.ID_PACOTE
                                AND PR.ID_CADEIRA = IPV.INDICE
                                AND PR.IN_STATUS_RESERVA = 'R'
                                
                                LEFT JOIN CI_MIDDLEWAY..MW_PACOTE P ON
                                P.ID_APRESENTACAO IN (
                                    SELECT A.ID_APRESENTACAO
                                    FROM CI_MIDDLEWAY..MW_APRESENTACAO A
                                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON
                                    A2.ID_EVENTO = A.ID_EVENTO
                                    AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO
                                    AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                                    WHERE A2.ID_APRESENTACAO = IPV.ID_APRESENTACAO
                                )

                                WHERE CODPECA = ?
                                AND A.DATAPRESENTACAO = ?
                                AND A.HORSESSAO = ?
                                AND CSV.STATUSINGRESSO IN ('L', 'U')
                                AND (CONVERT(VARCHAR, A.CODSALA) = ? OR ? = 'TODOS')

                                ORDER BY 1,2,3",
                            array($_GET['cboPeca'], $_GET['cboData'], $_GET['cboHora'], $_GET['cboSetor'], $_GET['cboSetor']));

        ob_start();

        while ($rs = fetchResult($result)) {
        ?>
            <tr>
                <td><?php echo utf8_encode2($rs['NOME']); ?></td>
                <td><?php echo utf8_encode2($rs['NOMSALA']); ?></td>
                <td><?php echo utf8_encode2($rs['NOMSETOR'] ? $rs['NOMSETOR'] : $rs['NOMSALA']); ?></td>
                <td><?php echo utf8_encode2($rs['NOMOBJETO']); ?></td>
                <td><?php echo $rs['DATHRENTRADA'] ? $rs['DATHRENTRADA']->format("d/m/Y H:i:s") : $rs['DATHRENTRADA']; ?></td>
                <td><?php echo $rs['PACOTE'] ? 'Sim' : ($rs['IN_ASSINANTE_EVENTO'] ? 'Sim' : 'Não'); ?></td>
                <td><?php echo $rs['IN_ASSINANTE'] == 'S' ? 'Sim' : 'Não'; ?></td>
                <td><?php echo $rs['TIPBILHETE']; ?></td>
            </tr>
        <?php
        }

        $retorno = ob_get_clean();

    } elseif ($_GET['action'] == 'cboTeatro') {

        $query = "SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO
                    FROM MW_BASE B
                    INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE
                    WHERE AC.ID_USUARIO = ? AND B.IN_ATIVO = '1'
                    ORDER BY B.DS_NOME_TEATRO";
        $result = executeSQL($mainConnection, $query, array($_SESSION['admin']));

        $combo = '<option value="">Selecione...</option>';
        while ($rs = fetchResult($result)) {
            $combo .= '<option value="' . $rs['ID_BASE'] . '"' . (($_GET['cboTeatro'] == $rs['ID_BASE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_NOME_TEATRO']) . '</option>';
            if ($_GET['excel'] and $_GET['cboTeatro'] == $rs['ID_BASE']) {
                $text = utf8_encode2($rs['DS_NOME_TEATRO']);
                break;
            }
        }

        $retorno = $_GET['excel'] ? $text : $combo;

    } elseif ($_GET['action'] == 'cboPeca' and isset($_GET['cboTeatro'])) {

        $conn = getConnection($_GET['cboTeatro']);

        $query = "EXEC SP_PEC_CON009;5 ?,?";
        $params = array($_SESSION['admin'], $_GET['cboTeatro']);
        $result = executeSQL($conn, $query, $params);

        $combo = '<option value="">Selecione...</option>';

        while($rs = fetchResult($result)){
            $combo .= '<option value="'. $rs["CodPeca"] .'"' . (($_GET['cboPeca'] == $rs['CodPeca']) ? ' selected' : '') . '>'. utf8_encode2($rs["nomPeca"]) .'</option>'; 
            if ($_GET['excel'] and $_GET['cboPeca'] == $rs['CodPeca']) {
                $text = utf8_encode2($rs['nomPeca']);
                break;
            }
        }

        $retorno = $_GET['excel'] ? $text : $combo;

    } elseif ($_GET['action'] == 'cboData' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca'])) {

        $conn = getConnection($_GET['cboTeatro']);

        $query = "EXEC SP_PEC_CON009;6 ?,?,?";
        $params = array($_SESSION['admin'], $_GET['cboTeatro'], $_GET['cboPeca']);
        $result = executeSQL($conn, $query, $params);

        $combo = '<option value="">Selecione...</option>';

        while($rs = fetchResult($result)){
            $combo .= '<option value="'. $rs["DatApresentacao"]->format('Ymd') .'"' . (($_GET['cboData'] == $rs['DatApresentacao']->format('Ymd')) ? ' selected' : '') . '>'. $rs['DatApresentacao']->format("d/m/Y") .'</option>';
            if ($_GET['excel'] and $_GET['cboData'] == $rs['DatApresentacao']->format('Ymd')) {
                $text = $rs['DatApresentacao']->format("d/m/Y");
                break;
            }
        }

        $retorno = $_GET['excel'] ? $text : $combo;

    } elseif ($_GET['action'] == 'cboHora' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca']) and isset($_GET['cboData'])) {

        $conn = getConnection($_GET['cboTeatro']);

        $query = "EXEC SP_PEC_CON009;7 ?,?,?,?";
        $params = array($_SESSION['admin'], $_GET['cboTeatro'], $_GET['cboPeca'], $_GET['cboData']);
        $result = executeSQL($conn, $query, $params);

        $combo = '<option value="">Selecione...</option>';

        while($rs = fetchResult($result)){
            $combo .= '<option value="'. $rs["HorSessao"] .'"' . (($_GET['cboHora'] == $rs['HorSessao']) ? ' selected' : '') . '>'. $rs['HorSessao'] .'</option>';
            if ($_GET['excel'] and $_GET['cboHora'] == $rs['HorSessao']) {
                $text = $rs['HorSessao'];
                break;
            }
        }

        $retorno = $_GET['excel'] ? $text : $combo;

    } elseif ($_GET['action'] == 'cboSetor' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca']) and isset($_GET['cboData']) and isset($_GET['cboHora'])) {

        $rs = executeSQL($mainConnection, "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ?", array($_GET['cboTeatro']), true);

        $conn = getConnectionTsp();

        $query = "EXEC SP_REL_BORDERO_VENDAS;7 ?,?,?,?";
        $params = array($_GET['cboData'], $_GET['cboPeca'], $_GET['cboHora'], $rs['DS_NOME_BASE_SQL']);
        $result = executeSQL($conn, $query, $params);

        $text = '&lt; TODOS &gt;';

        $combo = "<option value=''>Selecione...</option><option value='TODOS'".(($_GET['cboSetor'] == 'TODOS') ? ' selected' : '').">&lt; TODOS &gt;</option>";

        while($rs = fetchResult($result)){
            $combo .= '<option value="'. $rs["codsala"] .'"' . (($_GET['cboSetor'] == $rs['codsala']) ? ' selected' : '') . '>'. utf8_encode2($rs['nomSala']) .'</option>';
            if ($_GET['excel'] and $_GET['cboSetor'] == $rs['codsala']) {
                $text = utf8_encode2($rs['nomSala']);
                break;
            }
        }

        $retorno = $_GET['excel'] ? $text : $combo;

    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>