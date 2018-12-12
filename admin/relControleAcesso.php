<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
if(isset($_GET["exportar"]) && $_GET["exportar"] == "true"){
	header("Content-type: application/vnd.ms-excel");
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=relatorio.xls");
	header("Pragma: no-cache");
}

require_once("../settings/functions.php");
$connGeral = getConnection($_GET["local"]);
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

// Variaveis passadas por parametro pela url
$DataIni   = (isset($_GET["dt_inicial"]) && !empty($_GET["dt_inicial"])) ? tratarData($_GET["dt_inicial"]) : "null";
$DataFim   = (isset($_GET["dt_final"]) && !empty($_GET["dt_final"])) ? tratarData($_GET["dt_final"]) : "null";
$CodPeca   = (isset($_GET["cod_peca"]) && !empty($_GET["cod_peca"])) ? $_GET["cod_peca"] : "null";
$var_url   = "relControleAcesso.php?dt_inicial=". tratarData($DataIni) ."&dt_final=". tratarData($DataFim) ."&local=". $_GET["local"] ."&cod_peca=". $CodPeca;

$strIdEvento = "SELECT CODPECA, DS_EVENTO, B.DS_NOME_TEATRO FROM CI_MIDDLEWAY..MW_EVENTO E INNER JOIN CI_MIDDLEWAY..MW_BASE B ON B.ID_BASE = E.ID_BASE WHERE ID_EVENTO = ?";
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
                P.NOMPECA,A.DATAPRESENTACAO";
$paramsGeral = array($pRSIdEVento["CODPECA"], $DataIni, $DataFim);
$pRSGeral = executeSQL($connGeral, $strGeral, $paramsGeral);

if(sqlErrors())
    $err = "<br>Erro #001 <br>". var_dump($paramsGeral) ."<br>". $strGeral."<br>";

if(!isset($err) && $err == ""){

if(hasRows($pRSGeral)){
?>
<html>
<title>Relatório - Controle de Acessos</title>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<style type="text/css">
    body {margin:0px 0px 0px 0px;}
  @media print {
    body {margin: 0px 0px 0px 0px;}
    .boxmenu {display: none;}
  }
  @media screen {
    .top {border-left-width: 0em;}
  }
</style>
</head>
<link rel="stylesheet" type="text/css" href="../stylesheets/estilos_ra.css">
<link rel="stylesheet" type="text/css" href="../stylesheets/padraoRelat.CSS">
<body>
    <?php
        $visible = true;
        if(!isset($_GET["exportar"])){
            $bgColor = "bgcolor=\"LightGrey\"";
            $visible = false;
        }
    ?>
    <table width="770" class="tabela" border="0">
        <tr>
            <td colspan="1" rowspan="2"><img align="left" border="0" src="<?php echo multiSite_getLogo(); ?>" alt=""></td>
            <td colspan="1" height="15"></td>
        </tr>
        <tr>
            <td class="tabela" align="center" bgcolor="LightGrey"><font size=4 face="tahoma,verdana,arial"><b>Controle de Acesso</b></font></td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr><td colspan="2"><b>Local:</b> <?php echo utf8_encode2($pRSIdEVento["DS_NOME_TEATRO"]); ?></td></tr>
        <tr><td colspan="2"><b>Evento:</b> <?php echo utf8_encode2($pRSIdEVento["DS_EVENTO"]); ?></td></tr>        
        <tr><td colspan="2"><b>Período:</b> <?php echo $_GET["dt_inicial"] ." à ". $_GET["dt_final"];?></td></tr>
        <tr><td colspan="2"></td></tr>
    </table><br><br>
    <?php
    //        $bgColor = "bgcolor=\"LightGrey\"";
    //    }
    ?>

    <table width="760" class="tabela" border="0" <?php echo $bgColor; ?>>
        <tr>
            <td align="center" colspan="6"><font size="2" face="tahoma,verdana,arial"><B>CONTABILIZAÇÃO DOS ACESSOS</B></font></td>
        </tr>
        <tr>
            <td	align="left" width="240" class="titulogrid">Data de Apresentação</td>
            <td	align="center" width="104" class="titulogrid">Sessão</td>
            <td	align="center" width="104" class="titulogrid">Setor</td>
            <td	align="center" width="104" class="titulogrid">Tipo</td>
            <td	align="center" width="104" class="titulogrid">Qtd</td>
        </tr>

        <?php
            $totQuantidade  = 0;
            while($dados = fetchResult($pRSGeral)){
        ?>
        <tr>
            <td	align="left"  class="texto"><?php echo $dados["DATAPRESENTACAO"]; ?></td>
            <td	align="center"  class="texto"><?php echo $dados["HORSESSAO"];  ?></td>
            <td	align="center" class="texto"><?php echo utf8_encode2($dados["NOMSETOR"]); ?></td>
            <td	align="center" class="texto"><?php echo utf8_encode2($dados["TIPBILHETE"]); ?></td>
            <td	align="center" class="texto"><?php echo $dados["QTD"]; ?></td>
        </tr>
        <?php
                $totQuantidade += $dados['STATIPBILHMEIA'] != 'S' ? $dados["QTD"] : 0;
            }
        ?>
        <tr>
            <td align="left" colspan="4" class="titulogrid">Quantidade Total</td>
            <td	align="center" width="104" class="texto"><?php echo $totQuantidade; ?></td>
        </tr>
    </table><br>

<?php
}else{
    echo "<font color=\"red\" size=\"13\" align=\"center\"><center>Nenhum registro encontrado!</center></font>";
}
?>
    <br>
    <table width="770" border=0>
        <tr>
            <td align="middle">
                <br>
                <input class="botao" type="button" value="Imprimir Relatório" name="cmdImprimi" onClick="javascript:window.print();">
                <input class="botao" type="button" value="Fechar Janela" name="cmdFecha" onClick="javascript:window.close()">
                <input class="botao" type="button" value="Exportar Excel"
                       name="cmdExportar" onClick="document.location.href = '<?php echo $var_url."&exportar=true"; ?>';">
            </td>
        </tr>
    </table>
<?php
}else{
    echo "<br>".$err ."<br>". print_r(sqlErrors());
}
?>
</body>
</html>