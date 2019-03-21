<?php
require_once('acessoLogadoDie.php');
require_once('../settings/functions.php');

$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 310, true)) {
    $pagina = basename(__FILE__);
    if (isset($_GET['action'])) {
        require('actions/' . $pagina);
    } else {
        if ($_GET["id_base"] != "" && (strlen($_GET["pedido"]) > 0 or strlen($_GET["cpf"]) > 10 or strlen($_GET["nome"]) > 2)) {

            $query = "SELECT TOP 100
                        PV.ID_PEDIDO_VENDA,
                        PV.DT_PEDIDO_VENDA,
                        C.CD_CPF,
                        C.DS_NOME,
                        C.DS_SOBRENOME,
                        C.DS_DDD_TELEFONE,
                        C.DS_TELEFONE,
                        PV.VL_TOTAL_PEDIDO_VENDA,
                        COUNT(1) QTD,
                        PV.IN_SITUACAO
                    FROM MW_PEDIDO_VENDA PV
                    INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON PV.ID_PEDIDO_VENDA = IPV.ID_PEDIDO_VENDA
                    INNER JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                    INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO
                    INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                    WHERE e.id_base=?
                    AND (PV.ID_PEDIDO_VENDA = ? OR ? = '')
                    AND (C.CD_CPF = ? OR ? = '')
                    AND (C.DS_NOME LIKE '%' + ? + '%' OR C.DS_SOBRENOME LIKE '%' + ? + '%')
                    GROUP BY
                        PV.ID_PEDIDO_VENDA,
                        PV.DT_PEDIDO_VENDA,
                        C.CD_CPF,
                        C.DS_NOME,
                        C.DS_SOBRENOME,
                        C.DS_DDD_TELEFONE,
                        C.DS_TELEFONE,
                        PV.VL_TOTAL_PEDIDO_VENDA,
                        PV.IN_SITUACAO
                    ORDER BY 1 DESC";

            $params = array($_GET["id_base"], $_GET["pedido"], $_GET["pedido"],
                            $_GET["cpf"], $_GET["cpf"],
                            utf8_decode($_GET["nome"]), utf8_decode($_GET["nome"]));
            
            $result = executeSQL($mainConnection, $query, $params);            
        }
?>
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>     
        <script type="text/javascript" language="javascript">
            $(function() {
                var pagina = '<?php echo basename($pagina, ".php"); ?>';

                $('.button').button().click(function(){
                    if ($("#cboLocal").val() == "") {
                        $.dialog({title: 'Alerta...', text: 'Selecione o local'});
                        return;
                    }
                   document.location.href = "?p=" + pagina + "&pedido=" + $("#pedido").val() + "&cpf=" + $('#cpf').val() + "&nome=" + $('#nome').val() + "&id_base="+$("#cboLocal").val();
                });
            });
        </script>
        <h2>Reimpressão de Pedido</h2>
        <form id="dados" name="dados" action="?p=consultaLog" method="POST" style="text-align: left;">
            <label>local
                <?php echo comboTeatroPorUsuario2('cboLocal', $_SESSION["admin"], $_GET["id_base"],""); ?>
            </label>
            <label>Pedido nº:
                <input type="text" id="pedido" name="pedido" value="<?php echo $_GET["pedido"]; ?>" />
            </label>
            <label>&nbsp;&nbsp;CPF:
                <input type="text" id="cpf" name="cpf" value="<?php echo $_GET["cpf"]; ?>" />
            </label>
            <label>&nbsp;&nbsp;Nome do Cliente:
                <input type="text" id="nome" name="nome" value="<?php echo $_GET["nome"]; ?>" />
            </label>
    <input type="button" class="button" id="btnProcurar" value="Buscar" /><br/><br/>

    <table id="tableLogs" class="ui-widget ui-widget-content">
        <thead>
            <tr class="ui-widget-header">
                <th>Pedido nº</th>
                <th>Data do Pedido</th>
                <th>CPF</th>
                <th>Cliente e Telefone</th>
                <th>Valor total</th>
                <th>Qtde Ingressos</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
<?php
        while ($dados = fetchResult($result)) {
?>
            <tr>

                <td><?php echo $dados["ID_PEDIDO_VENDA"]; ?></td>
                <td><?php echo $dados["DT_PEDIDO_VENDA"]->format("d/m/Y"); ?></td>
                <td><?php echo $dados["CD_CPF"]; ?></td>
                <td><?php echo utf8_encode2($dados["DS_NOME"] . ' ' . $dados["DS_SOBRENOME"]) . '<br/>' . $dados["DS_DDD_TELEFONE"] . ' ' . $dados["DS_TELEFONE"]; ?></td>
                <td><?php echo $dados["VL_TOTAL_PEDIDO_VENDA"]; ?></td>
                <td><?php echo $dados["QTD"]; ?></td>
                <td><?php echo comboSituacao('situacao', $dados["IN_SITUACAO"], false); ?></td>
                <td>
                <?php if ($dados['IN_SITUACAO'] == 'F') { ?>
                    <a href="<?php echo $pagina; ?>?action=reimprimir&pedido=<?php echo $dados['ID_PEDIDO_VENDA']; ?>" target="_blank">Reimprimir Pedido</a>
                <?php } ?>
                </td>
            </tr>
<?php
        }
?>
        </tbody>
    </table>
</form><br/>
<?php
    }
}
?>