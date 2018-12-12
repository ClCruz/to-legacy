<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 400, true)) {

    if ($_GET['action'] == 'busca' and isset($_GET['cboTeatro']) and isset($_GET['txtTemporada'])) {

        $conn = getConnection($_GET['cboTeatro']);

        $result = executeSQL($conn,
                            "SELECT C.DS_NOME + ' ' + C.DS_SOBRENOME AS NOME
                                    ,C.DS_ENDERECO AS ENDERECO
                                    ,C.DS_COMPL_ENDERECO AS COMPL_ENDERECO
                                    ,C.DS_BAIRRO AS BAIRRO
                                    ,C.DS_CIDADE AS CIDADE
                                    ,E.SG_ESTADO AS ESTADO
                                    ,C.CD_CEP AS CEP
                                    ,C.DS_DDD_TELEFONE AS DDD_TELEFONE
                                    ,C.DS_TELEFONE AS TELEFONE
                                    ,C.DS_DDD_CELULAR AS DDD_CELULAR
                                    ,C.DS_CELULAR AS CELULAR
                                    ,C.CD_EMAIL_LOGIN AS EMAIL_LOGIN
                                    ,E2.DS_EVENTO AS PACOTE
                                    ,TS.NOMSETOR COLLATE SQL_LATIN1_GENERAL_CP1_CI_AS AS SETOR
                                    ,PR.DS_LOCALIZACAO AS LOCALIZACAO
                                    ,(SELECT TIPBILHETE
                                        FROM TABLUGSALA TLS 
                                              INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.CODAPRESENTACAO = TLS.CODAPRESENTACAO
                                              INNER JOIN TABTIPBILHETE TB ON TB.CODTIPBILHETE = TLS.CODTIPBILHETE
                                        WHERE A2.ID_EVENTO = A.ID_EVENTO
                                              AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO 
                                              AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                                              AND TLS.INDICE = TSD.INDICE
                                        ) AS TIPO_BILHETE
                                FROM CI_MIDDLEWAY..MW_CLIENTE C
                                INNER JOIN CI_MIDDLEWAY..MW_ESTADO E ON E.ID_ESTADO = C.ID_ESTADO
                                INNER JOIN CI_MIDDLEWAY..MW_PACOTE_RESERVA PR ON PR.ID_CLIENTE = C.ID_CLIENTE
                                INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                                INNER JOIN CI_MIDDLEWAY..MW_EVENTO E2 ON E2.ID_EVENTO = A.ID_EVENTO
                                INNER JOIN TABSALDETALHE TSD ON TSD.INDICE = PR.ID_CADEIRA
                                INNER JOIN TABSETOR TS ON TS.CODSALA = TSD.CODSALA
                                    AND TS.CODSETOR = TSD.CODSETOR
                                WHERE E2.ID_BASE = ?
                                    AND PR.IN_STATUS_RESERVA = 'R'
                                    AND PR.IN_ANO_TEMPORADA = ?
                            ORDER BY NOME, PACOTE, SETOR, LOCALIZACAO",
                            array($_GET['cboTeatro'], $_GET['txtTemporada']));

        ob_start();

        while ($rs = fetchResult($result)) {
        ?>
            <tr>
                <td><?php echo utf8_encode2($rs['NOME']); ?></td>
                <td><?php echo utf8_encode2($rs['ENDERECO']); ?></td>
                <td><?php echo utf8_encode2($rs['COMPL_ENDERECO']); ?></td>
                <td><?php echo utf8_encode2($rs['BAIRRO']); ?></td>
                <td><?php echo utf8_encode2($rs['CIDADE']); ?></td>
                <td><?php echo utf8_encode2($rs['ESTADO']); ?></td>
                <td><?php echo utf8_encode2($rs['CEP']); ?></td>
                <td><?php echo utf8_encode2($rs['DDD_TELEFONE']); ?></td>
                <td><?php echo utf8_encode2($rs['TELEFONE']); ?></td>
                <td><?php echo utf8_encode2($rs['DDD_CELULAR']); ?></td>
                <td><?php echo utf8_encode2($rs['CELULAR']); ?></td>
                <td><?php echo utf8_encode2($rs['EMAIL_LOGIN']); ?></td>
                <td><?php echo utf8_encode2($rs['PACOTE']); ?></td>
                <td><?php echo utf8_encode2($rs['SETOR']); ?></td>
                <td><?php echo utf8_encode2($rs['LOCALIZACAO']); ?></td>
                <td><?php echo utf8_encode2($rs['TIPO_BILHETE']); ?></td>
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

    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>