<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 384, true)) {

    if ($_GET['action'] == 'busca' and isset($_GET['id'])) {

        require_once('../settings/Paginator.php');

        function mask($val, $mask) {
            $maskared = '';
            $k = 0;
            for($i = 0; $i<=strlen($mask)-1; $i++) {
                if($mask[$i] == '#') {
                    if(isset($val[$k]))
                        $maskared .= $val[$k++];
                } else {
                    if(isset($mask[$i]))
                        $maskared .= $mask[$i];
                }
            }
            return $maskared;
        }

        if ($_GET['excel']) {

            $query = "SELECT
                            PC.DS_PROMOCAO,
                            P.CD_PROMOCIONAL,
                            P.ID_PEDIDO_VENDA,
                            P.ID_SESSION,
                            P.CD_CPF_PROMOCIONAL
                        FROM MW_PROMOCAO P
                        INNER JOIN MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = P.ID_PROMOCAO_CONTROLE
                        WHERE PC.ID_PROMOCAO_CONTROLE = ? AND PC.IN_ATIVO = 1
                        ORDER BY PC.DS_PROMOCAO, P.CD_PROMOCIONAL";

        } else {

            $offset = $_GET["offset"] > 0 ? $_GET["offset"] : 1;
            $por_pagina = $_GET["por_pagina"] > 0 ? $_GET["por_pagina"] : 50;
            $offset_final = ($offset + $por_pagina) - 1;

            $query = "WITH RESULTADO AS (
                            SELECT
                                PC.DS_PROMOCAO,
                                P.CD_PROMOCIONAL,
                                P.ID_PEDIDO_VENDA,
                                P.ID_SESSION,
                                P.CD_CPF_PROMOCIONAL,
                                ROW_NUMBER() OVER(ORDER BY PC.DS_PROMOCAO, P.CD_PROMOCIONAL) AS 'LINHA'
                            FROM MW_PROMOCAO P
                            INNER JOIN MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = P.ID_PROMOCAO_CONTROLE
                            WHERE PC.ID_PROMOCAO_CONTROLE = ? AND PC.IN_ATIVO = 1
                        )
                        SELECT *
                        FROM RESULTADO
                        WHERE LINHA BETWEEN " . $offset . " AND " . $offset_final ."
                        ORDER BY DS_PROMOCAO, CD_PROMOCIONAL";

        }

        $result = executeSQL($mainConnection, $query, array($_GET['id']));

        ob_start();

        while ($rs = fetchResult($result)) {
            $id = $rs['ID_PROMOCAO'];
        ?>
            <tr>
                <td class="text"><?php echo utf8_encode2($rs['DS_PROMOCAO']); ?></td>
                <td class="text"><?php echo utf8_encode2($rs['CD_PROMOCIONAL']); ?></td>
                <td class="text"><?php echo $rs['ID_SESSION']; ?></td>
                <td class="text"><?php echo $rs['ID_PEDIDO_VENDA']; ?></td>
                <td class="text"><?php echo $rs['CD_CPF_PROMOCIONAL'] ? mask($rs['CD_CPF_PROMOCIONAL'],'###.###.###-##') : ' - '; ?></td>
            </tr>
        <?php
        }

        if (!$_GET['excel']) {
            $rs = executeSQL($mainConnection,
                            "SELECT count(1)
                            FROM MW_PROMOCAO
                            WHERE ID_PROMOCAO_CONTROLE = ?",
                            array($_GET['id']), true);
            $total_registros = $rs[0];

            ?>
            <tr>
                <td id="paginacao" colspan="6" style="text-align: center;">
                    <?php
                        unset($_GET['offset']);
                        $link = $pagina . '?' . http_build_query($_GET) . '&offset=';
                        Paginator::paginate($offset, $total_registros, $por_pagina, $link, false);
                    ?>
                </td>
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