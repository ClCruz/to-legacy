<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 420, true)) {

    if ($_GET['action'] == 'busca' and isset($_GET['dtInicial']) and isset($_GET['dtFinal']) and isset($_GET['situacao'])) {

        // obtem canal de venda e local do evento
        function getCanalLocal($conn, $codvenda, $indice) {
            $query = "SELECT
                            CV.DS_CANAL_VENDA,
                            LE.DS_LOCAL_EVENTO,
                            G.TIPPECA
                        FROM TABLUGSALA TLS

                        INNER JOIN TABCAIXA TC ON TC.CODCAIXA = TLS.CODCAIXA
                        INNER JOIN CI_MIDDLEWAY..MW_CANAL_VENDA CV ON CV.ID_CANAL_VENDA = TC.ID_CANAL_VENDA

                        INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = TLS.CODAPRESENTACAO
                        INNER JOIN TABPECA TP ON TP.CODPECA = TA.CODPECA
                        INNER JOIN CI_MIDDLEWAY..MW_LOCAL_EVENTO LE ON LE.ID_LOCAL_EVENTO = TP.ID_LOCAL_EVENTO

                        INNER JOIN TABTIPPECA G ON G.CODTIPPECA = TP.CODTIPPECA

                        WHERE TLS.CODVENDA = ? AND TLS.INDICE = ?";
            $params = array($codvenda, $indice);
            return executeSQL($conn, $query, $params, true);
        }

        $mainConnection = mainConnection();

        $dt_inicial = explode('/', $_GET['dtInicial']);
        $dt_inicial = $dt_inicial[2].'-'.$dt_inicial[1].'-'.$dt_inicial[0];

        $dt_final = explode('/', $_GET['dtFinal']);
        $dt_final = $dt_final[2].'-'.$dt_final[1].'-'.$dt_final[0];

        $result = executeSQL($mainConnection,
                            "SELECT
                                PV.ID_PEDIDO_VENDA,
                                U.DS_NOME OPERADOR,
                                E.DS_EVENTO,
                                A.DT_APRESENTACAO,
                                A.HR_APRESENTACAO,
                                PV.DT_PEDIDO_VENDA,
                                PV.IN_SITUACAO,
                                MP.DS_MEIO_PAGAMENTO REDE,
                                NM_CARTAO_EXIBICAO_SITE BANDEIRA,
                                PV.CD_NUMERO_TRANSACAO NSU,
                                CASE WHEN PV.NR_PARCELAS_PGTO > 1 THEN 'PARCELADO' ELSE 'NÃO PARCELADO' END FORMA_PAGAMENTO,
                                PV.NR_PARCELAS_PGTO PARCELAS,
                                VL_TOTAL_PEDIDO_VENDA - VL_TOTAL_TAXA_CONVENIENCIA VALOR_SEM_SERVICO,
                                PV.VL_TOTAL_TAXA_CONVENIENCIA,
                                PV.VL_TOTAL_PEDIDO_VENDA,
                                C.DS_NOME + ' ' + C.DS_SOBRENOME NOME_CLIENTE,
                                C.CD_CPF,
                                PV.CD_BIN_CARTAO,
                                COUNT(1) QUANTIDADE_INGRESSOS,
                                PV.NM_TITULAR_CARTAO,
                                
                                --dados para obter o canal de venda e local do evento
                                E.ID_BASE,
                                MAX(IPV.CODVENDA) CODVENDA,
                                MAX(IPV.INDICE) INDICE
                                
                            FROM MW_PEDIDO_VENDA PV
                            LEFT JOIN MW_USUARIO U ON U.ID_USUARIO = PV.ID_USUARIO_CALLCENTER
                            INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                            INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
                            INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                            INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO
                            INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE

                            WHERE PV.DT_PEDIDO_VENDA BETWEEN CONVERT(DATETIME, ?, 20) AND CONVERT(DATETIME, ? + ' 23:59:59', 20)
                            AND (PV.IN_SITUACAO = ? OR ? = 'TODOS')
                            
                            GROUP BY
                                PV.ID_PEDIDO_VENDA,
                                U.DS_NOME,
                                E.DS_EVENTO,
                                A.DT_APRESENTACAO,
                                A.HR_APRESENTACAO,
                                PV.DT_PEDIDO_VENDA,
                                PV.IN_SITUACAO,
                                MP.DS_MEIO_PAGAMENTO,
                                NM_CARTAO_EXIBICAO_SITE,
                                PV.CD_NUMERO_TRANSACAO,
                                CASE WHEN PV.NR_PARCELAS_PGTO > 1 THEN 'PARCELADO' ELSE 'NÃO PARCELADO' END,
                                PV.NR_PARCELAS_PGTO,
                                VL_TOTAL_PEDIDO_VENDA - VL_TOTAL_TAXA_CONVENIENCIA,
                                PV.VL_TOTAL_TAXA_CONVENIENCIA,
                                PV.VL_TOTAL_PEDIDO_VENDA,
                                C.DS_NOME + ' ' + C.DS_SOBRENOME,
                                C.CD_CPF,
                                PV.CD_BIN_CARTAO,
                                E.ID_BASE,
                                PV.NM_TITULAR_CARTAO

                            ORDER BY PV.ID_PEDIDO_VENDA, CODVENDA",
                            array($dt_inicial, $dt_final, $_GET['situacao'], $_GET['situacao']));

        ob_start();

        while ($rs = fetchResult($result)) {

            $conn[$rs['ID_BASE']] = $conn[$rs['ID_BASE']] ? $conn[$rs['ID_BASE']] : getConnection($rs['ID_BASE']);

            $info = getCanalLocal($conn[$rs['ID_BASE']], $rs['CODVENDA'], $rs['INDICE']);
        ?>
            <tr>
                <td class="text"><?php echo $rs['ID_PEDIDO_VENDA']; ?></td>
                <td><?php echo utf8_encode2($info['DS_CANAL_VENDA']); ?></td>
                <td><?php echo $rs['OPERADOR']; ?></td>
                <td><?php echo utf8_encode2($info['DS_LOCAL_EVENTO']); ?></td>
                <td><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
                <td><?php echo $rs['DT_APRESENTACAO']->format("d/m/Y"); ?></td>
                <td><?php echo $rs['HR_APRESENTACAO']; ?></td>
                <td><?php echo $rs['DT_PEDIDO_VENDA']->format("d/m/Y"); ?></td>
                <td><?php echo $rs['DT_PEDIDO_VENDA']->format("H:i:s"); ?></td>
                <td><?php echo combosituacao('', $rs['IN_SITUACAO'], false); ?></td>
                <td><?php echo utf8_encode2($rs['REDE']); ?></td>
                <td><?php echo utf8_encode2($rs['BANDEIRA']); ?></td>
                <td class="text"><?php echo $rs['NSU']; ?></td>
                <td><?php echo $rs['FORMA_PAGAMENTO']; ?></td>
                <td><?php echo $rs['PARCELAS']; ?></td>
                <td><?php echo $rs['QUANTIDADE_INGRESSOS']; ?></td>
                <td class="money"><?php echo number_format($rs['VALOR_SEM_SERVICO'], 2, ',', '.'); ?></td>
                <td class="money"><?php echo number_format($rs['VL_TOTAL_TAXA_CONVENIENCIA'], 2, ',', '.'); ?></td>
                <td class="money"><?php echo number_format($rs['VL_TOTAL_PEDIDO_VENDA'], 2, ',', '.'); ?></td>
                <td><?php echo utf8_encode2($rs['NOME_CLIENTE']); ?></td>
                <td class="text"><?php echo $rs['CD_CPF']; ?></td>
                <td class="text"><?php echo $rs['CD_BIN_CARTAO']; ?></td>
                <td><?php echo utf8_encode2($info['TIPPECA']); ?></td>
                <td class="text"><?php echo $rs['NM_TITULAR_CARTAO']; ?></td>
            </tr>
        <?php
        }

        $retorno = ob_get_clean();

    }

    if (is_array($retorno)) {
        echo $retorno[0]['message'];
    } else {
        echo $retorno;
    }
}
?>