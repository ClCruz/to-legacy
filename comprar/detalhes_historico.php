<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
session_start();

$idHistorico = $_GET['historico'];
$origem = $_GET['origem'];
$mainConnection = mainConnection();
if($origem=="PACOTE"){
    $query = "SELECT DISTINCT
                E.DS_EVENTO
                ,CONVERT(VARCHAR, A.DT_APRESENTACAO, 103) +' '+ A.HR_APRESENTACAO AS DT_APRESENTACAO
                ,A.DT_APRESENTACAO AS DT_APR
              FROM
                MW_PACOTE_APRESENTACAO PA
              INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = PA.ID_APRESENTACAO
              INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
              WHERE
                PA.ID_PACOTE = ?
              ORDER BY
                DT_APRESENTACAO";
}else{
    $query = "SELECT DISTINCT
                HAD.DS_APRESENTACAO AS DS_EVENTO
                ,CONVERT(VARCHAR, HAD.DT_HR_APRESENTACAO, 103) + ' ' + CONVERT(VARCHAR(5), HAD.DT_HR_APRESENTACAO, 114) AS DT_APRESENTACAO
                ,HAD.DT_HR_APRESENTACAO AS DT_APR
              FROM MW_HIST_ASSINATURA_DETALHE HAD
              WHERE
                HAD.ID_HISTORICO = ?
              ORDER BY
                HAD.DT_HR_APRESENTACAO";
}
$params = array($idHistorico);
$result = executeSQL($mainConnection, $query, $params);
?>
<span class="pedido_resumo">
<?php
if(hasRows($result)){
?>
<table>
    <thead>
        <tr>
            <td width="208">Apresentação</td>
            <td width="130">Data e Hora</td>
        </tr>
    </thead>
    <tbody>
        <?php
        while ($rs = fetchResult($result)) {
        ?>
            <tr>
                <td><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
                <td><?php echo $rs['DT_APRESENTACAO']; ?></td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>
<?php
}else{
?>
<h3 style="float: left;">Não encontrado apresentações.</h3>
<?php
}
?>
</span>