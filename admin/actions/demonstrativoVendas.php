<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 410, true)) {

    if ($_GET['action'] == 'busca' and isset($_GET['cboPeca']) and (isset($_GET['cboData']) and isset($_GET['cboHora'])) || (isset($_GET['getDataInicio']) and isset($_GET['getDataTermino']))) {

        $conn = getConnectionTsp();

        $nm_sql_teatro = executeSQL($mainConnection, 'SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ?', array($_GET['cboTeatro']), true);
        $nm_sql_teatro = $nm_sql_teatro[0];

        if($_GET['getDataInicio'] != ""){
            $data1 = explode("/", $_GET['getDataInicio']);
            $data2 = explode("/", $_GET['getDataTermino']);

            $_GET['getDataInicio'] = $data1[2]."".$data1[1]."".$data1[0];
            $_GET['getDataTermino'] = $data2[2]."".$data2[1]."".$data2[0];
            $vetorValores = array($_GET['getDataInicio'], $_GET['getDataTermino'], $_GET['cboPeca'], '', $nm_sql_teatro, '');
        } else {
            $vetorValores = array($_GET['cboData'], $_GET['cboData'], $_GET['cboPeca'], $_GET['cboHora'], $nm_sql_teatro, $_GET['cboSetor']);
        }

        $result = executeSQL($conn,
                            "EXEC SP_REL_DEMO_AVULSO_ASSINATURA ?,?,?,?,?,?",
                            $vetorValores);

        $lines = array();
        while ($rs = fetchResult($result)) {
            $lines[$rs['NomSetor'].$rs['CodTipBilhete']]['NOMSETOR'] = $rs['NomSetor'];
            $lines[$rs['NomSetor'].$rs['CodTipBilhete']]['TIPBILHETE'] = $rs['TipBilhete'];

            $lines[$rs['NomSetor'].$rs['CodTipBilhete']]['qtd_'.($rs['in_from_pacote'] ? 'assinatura' : 'avulso')] += $rs['QtdeVendidos'];
            $lines[$rs['NomSetor'].$rs['CodTipBilhete']]['valor_'.($rs['in_from_pacote'] ? 'assinatura' : 'avulso')] += $rs['Total'];
        }

        $total['qtd_assinatura'] = 0;
        $total['valor_assinatura'] = 0;
        $total['qtd_avulso'] = 0;
        $total['valor_avulso'] = 0;
        $total['qtd_total'] = 0;
        $total['valor_total'] = 0;

        ob_start();

        foreach ($lines as $rs) {
            $total['qtd_assinatura'] += $rs['qtd_assinatura'];
            $total['valor_assinatura'] += $rs['valor_assinatura'];
            $total['qtd_avulso'] += $rs['qtd_avulso'];
            $total['valor_avulso'] += $rs['valor_avulso'];
            $total['qtd_total'] += $rs['qtd_assinatura'] + $rs['qtd_avulso'];
            $total['valor_total'] += $rs['valor_assinatura'] + $rs['valor_avulso'];
        ?>
            <tr>
                <td><?php echo utf8_encode2($rs['NOMSETOR']); ?></td>
                <td><?php echo utf8_encode2($rs['TIPBILHETE']); ?></td>

                <td align="right"><?php echo $rs['qtd_assinatura'] ? $rs['qtd_assinatura'] : 0; ?></td>
                <td align="right"><?php echo number_format($rs['valor_assinatura'], 2, ",", "."); ?></td>

                <td align="right"><?php echo $rs['qtd_avulso'] ? $rs['qtd_avulso'] : 0; ?></td>
                <td align="right"><?php echo number_format($rs['valor_avulso'], 2, ",", "."); ?></td>

                <td align="right"><?php echo $rs['qtd_assinatura'] + $rs['qtd_avulso'] ? $rs['qtd_assinatura'] + $rs['qtd_avulso'] : 0; ?></td>
                <td align="right"><?php echo number_format($rs['valor_assinatura'] + $rs['valor_avulso'], 2, ",", "."); ?></td>
            </tr>
        <?php
        }
        ?>
            <tr style="font-weight: bold;">
                <td colspan="2">Totais</td>

                <td align="right"><?php echo $total['qtd_assinatura'] ? $total['qtd_assinatura'] : 0; ?></td>
                <td align="right"><?php echo number_format($total['valor_assinatura'], 2, ",", "."); ?></td>

                <td align="right"><?php echo $total['qtd_avulso'] ? $total['qtd_avulso'] : 0; ?></td>
                <td align="right"><?php echo number_format($total['valor_avulso'], 2, ",", "."); ?></td>

                <td align="right"><?php echo $total['qtd_total'] ? $total['qtd_total'] : 0; ?></td>
                <td align="right"><?php echo number_format($total['valor_total'], 2, ",", "."); ?></td>
            </tr>
        <?php

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

        $query = "SELECT TBPC.CODPECA, TBPC.NOMPECA
                    FROM TABAPRESENTACAO TBAP (NOLOCK)
                    INNER JOIN TABPECA TBPC (NOLOCK)
                        ON TBPC.CODPECA = TBAP.CODPECA
                    INNER JOIN CI_MIDDLEWAY..MW_ACESSO_CONCEDIDO IAC (NOLOCK)
                        ON IAC.ID_BASE = ?
                        AND IAC.ID_USUARIO = ?
                        AND IAC.CODPECA = TBAP.CODPECA
                                        
                    INNER JOIN CI_MIDDLEWAY..MW_EVENTO E
                        ON E.CODPECA = TBPC.CODPECA
                        AND E.ID_BASE = IAC.ID_BASE
                        AND E.ID_EVENTO IN (
                            SELECT E2.ID_EVENTO
                            FROM CI_MIDDLEWAY..MW_APRESENTACAO A
                            INNER JOIN CI_MIDDLEWAY..MW_EVENTO E2
                                ON E2.ID_EVENTO = A.ID_EVENTO
                                AND E2.ID_BASE = E.ID_BASE
                            INNER JOIN CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA
                                ON A.ID_APRESENTACAO = PA.ID_APRESENTACAO
                        )

                    GROUP BY TBPC.CODPECA, TBPC.NOMPECA
                    ORDER BY TBPC.NOMPECA";
        $params = array($_GET['cboTeatro'], $_SESSION['admin']);
        $result = executeSQL($conn, $query, $params);

        $combo = '<option value="">Selecione...</option>';

        while($rs = fetchResult($result)){
            $combo .= '<option value="'. $rs["CODPECA"] .'"' . (($_GET['cboPeca'] == $rs['CODPECA']) ? ' selected' : '') . '>'. utf8_encode2($rs["NOMPECA"]) .'</option>'; 
            if ($_GET['excel'] and $_GET['cboPeca'] == $rs['CODPECA']) {
                $text = utf8_encode2($rs['NOMPECA']);
                break;
            }
        }

        $retorno = $_GET['excel'] ? $text : $combo;

    } elseif ($_GET['action'] == 'cboData' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca'])) {

        $conn = getConnection($_GET['cboTeatro']);

        $query = "SELECT TBAP.DATAPRESENTACAO
                    FROM TABAPRESENTACAO TBAP (NOLOCK)
                    INNER JOIN TABPECA TBPC (NOLOCK)
                        ON TBPC.CODPECA = TBAP.CODPECA
                    INNER JOIN CI_MIDDLEWAY..MW_ACESSO_CONCEDIDO IAC (NOLOCK)
                        ON IAC.ID_BASE = ?
                        AND IAC.ID_USUARIO = ?
                        AND IAC.CODPECA = TBAP.CODPECA
                                        
                    INNER JOIN CI_MIDDLEWAY..MW_EVENTO E
                        ON E.CODPECA = TBPC.CODPECA
                        AND E.ID_BASE = IAC.ID_BASE
                        AND E.ID_EVENTO IN (
                            SELECT E2.ID_EVENTO
                            FROM CI_MIDDLEWAY..MW_APRESENTACAO A
                            INNER JOIN CI_MIDDLEWAY..MW_EVENTO E2
                                ON E2.ID_EVENTO = A.ID_EVENTO
                                AND E2.ID_BASE = E.ID_BASE
                            INNER JOIN CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA
                                ON A.ID_APRESENTACAO = PA.ID_APRESENTACAO
                        )

                    WHERE TBPC.CODPECA = ?
                    GROUP BY TBAP.DATAPRESENTACAO
                    ORDER BY TBAP.DATAPRESENTACAO";
        $params = array($_GET['cboTeatro'], $_SESSION['admin'], $_GET['cboPeca']);
        $result = executeSQL($conn, $query, $params);

        $combo = '<option value="">Selecione...</option>';

        while($rs = fetchResult($result)){
            $combo .= '<option value="'. $rs["DATAPRESENTACAO"]->format('Ymd') .'"' . (($_GET['cboData'] == $rs['DATAPRESENTACAO']->format('Ymd')) ? ' selected' : '') . '>'. $rs['DATAPRESENTACAO']->format("d/m/Y") .'</option>';
            if ($_GET['excel'] and $_GET['cboData'] == $rs['DATAPRESENTACAO']->format('Ymd')) {
                $text = "<b>Data: </b><br />".$rs['DATAPRESENTACAO']->format("d/m/Y");
                break;
            } else
            {
                $text = "";
            }
        }

        $retorno = $_GET['excel'] ? $text : $combo;

    } elseif($_GET['action'] == 'getDataInicio' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca'])){
        $conn = getConnection($_GET['cboTeatro']);

        $query = "SELECT TBAP.DATAPRESENTACAO
                    FROM TABAPRESENTACAO TBAP (NOLOCK)
                    INNER JOIN TABPECA TBPC (NOLOCK)
                        ON TBPC.CODPECA = TBAP.CODPECA
                    INNER JOIN CI_MIDDLEWAY..MW_ACESSO_CONCEDIDO IAC (NOLOCK)
                        ON IAC.ID_BASE = ?
                        AND IAC.ID_USUARIO = ?
                        AND IAC.CODPECA = TBAP.CODPECA
                                        
                    INNER JOIN CI_MIDDLEWAY..MW_EVENTO E
                        ON E.CODPECA = TBPC.CODPECA
                        AND E.ID_BASE = IAC.ID_BASE
                        AND E.ID_EVENTO IN (
                            SELECT E2.ID_EVENTO
                            FROM CI_MIDDLEWAY..MW_APRESENTACAO A
                            INNER JOIN CI_MIDDLEWAY..MW_EVENTO E2
                                ON E2.ID_EVENTO = A.ID_EVENTO
                                AND E2.ID_BASE = E.ID_BASE
                            INNER JOIN CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA
                                ON A.ID_APRESENTACAO = PA.ID_APRESENTACAO
                        )

                    WHERE TBPC.CODPECA = ?
                    GROUP BY TBAP.DATAPRESENTACAO
                    ORDER BY TBAP.DATAPRESENTACAO";
        $params = array($_GET['cboTeatro'], $_SESSION['admin'], $_GET['cboPeca']);
        $result = executeSQL($conn, $query, $params);

        $array = array();

        while($rs = fetchResult($result)){
            if($_GET['excel'] and $_GET['getDataInicio'] == $rs['DATAPRESENTACAO']->format("d/m/Y")){
                $text = "<b>Dt. Apresentação Inicial</b><br />".$rs['DATAPRESENTACAO']->format("d/m/Y");
                break;
            }
            else
            {
                $text = "";
            }

            $valores = array("data_apresentacao" => $rs['DATAPRESENTACAO']->format("d/m/Y"));
            $array[] = $valores;
        }

        $retorno = $_GET['excel'] ? $text : json_encode($array);

    } elseif ($_GET['action'] == 'cboHora' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca']) and isset($_GET['cboData'])) {

        $conn = getConnection($_GET['cboTeatro']);

        $query = "EXEC SP_PEC_CON009;7 ?,?,?,?";
        $params = array($_SESSION['admin'], $_GET['cboTeatro'], $_GET['cboPeca'], $_GET['cboData']);
        $result = executeSQL($conn, $query, $params);

        $combo = '<option value="">Selecione...</option>';

        while($rs = fetchResult($result)){
            $combo .= '<option value="'. $rs["HorSessao"] .'"' . (($_GET['cboHora'] == $rs['HorSessao']) ? ' selected' : '') . '>'. $rs['HorSessao'] .'</option>';
            if ($_GET['excel'] and $_GET['cboHora'] == $rs['HorSessao']) {
                $text = "<b>Hora:</b><br />".$rs['HorSessao'];
                break;
            }
            else{
                $text = "";
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