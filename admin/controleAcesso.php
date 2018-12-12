<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

/**
 * Função que retorna a data formatada
 * @param <Date> $data
 * @return <String> Data formatada no padrão (aaaa/mm/dd)
 */
function tratarData($data){
    $array = explode("/",$data);
    $dia = $array[0];
    $mes = $array[1];
    $ano = $array[2];
    return $ano."/".$mes."/".$dia;
}

if (acessoPermitido($mainConnection, $_SESSION['admin'], 21, true)) {

$pagina = basename(__FILE__);
$MesAtual = (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y");
$MesAnterior = (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d")."/". (date("m") - 1) ."/".date("Y");

if(isset($_GET["gerar"]) && $_GET["gerar"] == "true"){
    $connGeral = getConnection($_GET["local"]);
    $DataIni   = (isset($_GET["dt_inicial"]) && !empty($_GET["dt_inicial"])) ? tratarData($_GET["dt_inicial"]) : "null";
    $DataFim   = (isset($_GET["dt_final"]) && !empty($_GET["dt_final"])) ? tratarData($_GET["dt_final"]) : "null";
    $CodPeca   = (isset($_GET["cod_peca"]) && !empty($_GET["cod_peca"])) ? $_GET["cod_peca"] : "null";
    $var_url   = "relControleAcesso.php?dt_inicial=". tratarData($DataIni) ."&dt_final=". tratarData($DataFim) ."&local=". $_GET["local"] ."&cod_peca=". $CodPeca;

    $strIdEvento = "SELECT CODPECA  FROM CI_MIDDLEWAY..MW_EVENTO WHERE ID_EVENTO = ?";
    $pRSIdEVento = executeSQL($connGeral, $strIdEvento, array($CodPeca), true);

    // Monta e executa query principal do relatório
    $strGeral = "SELECT
                        P.NOMPECA,
                        CONVERT(CHAR(10), A.DATAPRESENTACAO,103) AS DATAPRESENTACAO,
                        A.HORSESSAO,
                        S.NOMSETOR,
                        T.TIPBILHETE,
                        COUNT(1) AS QTD,
                        T.STATIPBILHMEIA
                FROM
                        TABCONTROLESEQVENDA CS
                        INNER JOIN
                        TABAPRESENTACAO     A
                        ON  A.CODAPRESENTACAO = CS.CODAPRESENTACAO
                        INNER JOIN
                        TABPECA             P
                        ON  P.CODPECA = A.CODPECA
                        INNER JOIN
                        TABSETOR            S
                        ON  S.CODSETOR = SUBSTRING(CODBAR, 6,1)
                        AND S.CODSALA  = A.CODSALA
                        INNER JOIN
                        TABTIPBILHETE       T
                        ON  T.CODTIPBILHETE = SUBSTRING(CODBAR, 15,3)
                WHERE
                        STATUSINGRESSO = 'U'
                AND P.CODPECA = ?
                AND A.DATAPRESENTACAO BETWEEN ? AND ?
                GROUP BY
                        P.NOMPECA,
                        CONVERT(CHAR(10), A.DATAPRESENTACAO,103),
                        A.HORSESSAO,
                        S.NOMSETOR,
                        T.TIPBILHETE,
                        T.STATIPBILHMEIA
                ORDER BY
                        P.NOMPECA,A.DATAPRESENTACAO
                ";
    $paramsGeral = array($pRSIdEVento["CODPECA"], $DataIni, $DataFim);
    $pRSGeral = executeSQL($connGeral, $strGeral, $paramsGeral);
}
if(sqlErrors())
    print_r(sqlErrors());
?>
<script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
<script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
<script type="text/javascript" language="javascript">
$(function() {
    var pagina = '<?php echo $pagina; ?>'
    $('.button').button();
    //$(".datepicker").datepicker();
        $('input.datepicker').datepicker({
              changeMonth: true,
              changeYear: true,
              onSelect: function(date, e) {
                  if ($(this).is('#dt_inicial')) {
               $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                  }
              }
                 }).datepicker('option', $.datepicker.regional['pt-BR']);
    
    //Gera relatorio
    $("#btnRelatorio").click(function(){
            if($("#local").val() == "")
                $.dialog({title: 'Alerta...', text: 'Selecione o local!'});
            else if($("#evento").val() == "")
                $.dialog({title: 'Alerta...', text: 'Selecione o evento!'});
            else{
                var url = "&dt_inicial="+ $("#dt_inicial").val() + "&dt_final="+
                    $("#dt_final").val() +"&local="+ $("#local").val() +
                    '&cod_peca='+ $("#evento").val() +"&gerar=true";
                    document.location = '?p=' + pagina.replace('.php', '') + ''+ url + '';
            }
    });

        $("#btnExportar").click(function(){
            if($("#local").val() == "")
                $.dialog({title: 'Alerta...', text: 'Selecione o local!'});
            else if($("#evento").val() == "")
                $.dialog({title: 'Alerta...', text: 'Selecione o evento!'});
            else{
                var url = "?dt_inicial="+ $("#dt_inicial").val() + "&dt_final="+
                        $("#dt_final").val() +"&local="+ $("#local").val() +
                        '&cod_peca='+ $("#evento").val() +"&gerar=true";
                window.open("relControleAcesso.php" + url, "", "width=920, scrollbars=yes, height=600", "");
            }
        });

        $('tr:not(.ui-widget-header)').hover(function() {
        $(this).addClass('ui-state-hover');
    }, function() {
        $(this).removeClass('ui-state-hover');
    });

        $("#local").change(function(){
        document.location = '?p=' + pagina.replace('.php', '') +
                    '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+
                    $("#dt_final").val() + '&local=' + $("#local").val()+ '';
    });

        $("#evento").change(function(){
            if($("#local").val() == "")
                $.dialog({title: 'Alerta...', text: 'Selecione o local!'});
            else if($("#evento").val() == "")
                $.dialog({title: 'Alerta...', text: 'Selecione o evento!'});
            else{
                var url = "&dt_inicial="+ $("#dt_inicial").val() + "&dt_final="+
                    $("#dt_final").val() +"&local="+ $("#local").val() +
                    '&cod_peca='+ $("#evento").val() +"&gerar=true";
                    document.location = '?p=' + pagina.replace('.php', '') + ''+ url + '';
            }
        });
});
</script>
<style type="text/css">
#paginacao{
    width: 100%;
    text-align: center;
    margin-top: 10px;   
}
.tableData{
    width: 600px !important;
}
</style>
<h2>Relatório de Controle de Acesso</h2>
<p style="width:1150px;">Data Inicial&nbsp;&nbsp;<input type="text" value="<?php echo $MesAnterior; ?>"
                 class="datepicker" id="dt_inicial" name="dt_inicial" />&nbsp;&nbsp;
    Data Final&nbsp;&nbsp;<input type="text" class="datepicker" value="<?php echo $MesAtual; ?>"
                 id="dt_final" name="dt_final" />&nbsp;&nbsp;
    Local&nbsp;&nbsp;<?php echo comboTeatro("local", $_GET["local"]); ?>&nbsp;&nbsp;
    <?php
        $params = array($_SESSION["admin"], $_GET["local"]);
        $name = "evento";
        $selected = $_GET["cod_peca"];
        $resultEventos = executeSQL($mainConnection, "SELECT E.ID_EVENTO, E.DS_EVENTO
        FROM MW_EVENTO E
        INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_USUARIO = ? AND AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA
        WHERE E.ID_BASE = ? AND E.IN_ATIVO = '1' ORDER BY DS_EVENTO", $params);
        $combo = '<select name="'.$name.'" class="inputStyle" id="'.$name.'"><option value="">Selecione um evento...</option>';

        while ($rs = fetchResult($resultEventos)) {
                $combo .= '<option value="'.$rs['ID_EVENTO'].'"' .
                            (($selected == $rs['ID_EVENTO']) ? ' selected' : '') .
                            '>'.utf8_encode2($rs['DS_EVENTO']).'</option>';
        }
        $combo .= '</select>';
    ?>
    Eventos&nbsp;&nbsp;<?php echo $combo; ?>&nbsp;&nbsp;
    <input type="button" class="button" id="btnRelatorio" value="Buscar" />&nbsp;
    <?php
        if(hasRows($pRSGeral)){
    ?>
            <input type="button" class="button" id="btnExportar" value="Exportar Excel" />
    <?php
        }
    ?><br><br>

<table width="760" class="ui-widget ui-widget-content" >
    <thead>
    <tr class="ui-widget-header">
        <th align="left" width="240" class="titulogrid">Data de Apresentação</th>
        <th align="center" width="104" class="titulogrid">Sessão</th>
        <th align="center" width="104" class="titulogrid">Setor</th>
        <th align="center" width="104" class="titulogrid">Tipo</th>
        <th align="center" width="104" class="titulogrid">Qtd</th>
    </tr>
    </thead>

    <?php
        $totQuantidade  = 0;
        $cont = 0;
        while($dados = fetchResult($pRSGeral)){
    ?>
    <tbody>
    <tr>
        <td align="left"  class="texto"><?php echo $dados["DATAPRESENTACAO"]; ?></td>
        <td align="center"  class="texto"><?php echo $dados["HORSESSAO"];  ?></td>
        <td align="center" class="texto"><?php echo utf8_encode2($dados["NOMSETOR"]); ?></td>
        <td align="center" class="texto"><?php echo utf8_encode2($dados["TIPBILHETE"]); ?></td>
        <td align="center" class="texto"><?php echo $dados["QTD"]; ?></td>
    </tr>
    <?php
            $totQuantidade += $dados['STATIPBILHMEIA'] != 'S' ? $dados["QTD"] : 0;
        }
    ?>
    <tr>
        <td align="left" colspan="4" class="titulogrid">Quantidade Total</td>
        <td align="center" width="104" class="texto"><?php echo $totQuantidade; ?></td>
    </tr>
    </tbody>
</table><br>
<?php
}
?>