<?php
if (isset($_GET["exportar"]) && $_GET["exportar"] == "true") {
    header("Content-type: application/vnd.ms-excel");
    header("Content-type: application/force-download");
    header("Content-Disposition: attachment; filename=relatorio.xls");
}

function tratarData($data) {
    $data = explode("/", $data);
    return $data[2] . $data[1] . $data[0];
}

require_once('../settings/functions.php');
if (isset($_GET["local"])) {
    $mainConnection = getConnection($_GET["local"]);
}
session_start();

$pagina = basename(__FILE__);
?>
<html>
    <title>Relatório de Faturamento Resumido</title>
    <HEAD>
        <style type="text/css">
            body {margin:0px 0px 0px 0px;}
            @media print {
                body {margin: 0px 0px 0px 0px;}
                .boxmenu {display: none;}
                input{display: none;}
            }
            @media screen {
                .top {border-left-width: 0em;}
                input{cursor: pointer;}
            }
        </style>
    </HEAD>
    <link rel="stylesheet" type="text/css" href="../stylesheets/estilos_ra.css">
    <link rel="stylesheet" type="text/css" href="../stylesheets/padraoRelat.css">
    <body leftmargin="0" topmargin="0">
        <?php

        function Cabec($nPag, $nLin, $descricao) {
            if ($nPag > 1) {
                echo "<br clear=\"all\" style=\"page-break-after:always;\">";
            }
        ?>
            <table width="670" class="tabela" border="<?php echo (!isset($_GET["exportar"])) ? 0 : 1; ?>">
                <tr>
                    <td rowspan="3" width="200" align="center"><img src="../images/ci.gif" border="0"></td>
                    <td align="center" width="410"><font size="1" face="tahoma,verdana,arial"><b>COMPRE INGRESSOS – AGÊNCIA DE VENDAS DE INGRESSOS LTDA<br>CNPJ 07.421.862/0001-37</b></font></td>
                    <td align="right" width="60"><font size="1" face="tahoma,verdana,arial"><b>Data: <?php echo date("d/m/Y"); ?></b></font></td>
                </tr>
                <tr>
                    <td align="center" rowspan="2"><font size="1" face="tahoma,verdana,arial"><b><?php echo $descricao; ?></b></font></td>
                    <td align="right" width="60"><font size="1" face="tahoma,verdana,arial"><b>Hora: <?php echo date("G:i:s"); ?></b></font></td>
                </tr>
                <tr>
                    <td align="right"><font size="1" face="tahoma,verdana,arial"><b>Página: <?php echo $nPag; ?></b></font></td>
                </tr>
            </table>
            <br clear=all>
        <?php
            $nPag = $nPag + 1;
            $nLin = 3;
        }
        ?>


        <?php
        //carrega variaveis
        $var_Teatro = $_GET["teatro"];
        $codPeca = ($_GET["eventos"] == "null") ? "Null" : $_GET["eventos"];
        $dataInicial = $_GET["dt_inicial"];
        $dataFinal = $_GET["dt_final"];
        $var_Papel = $_GET["Papel"];
        $var_DescPeca = $_GET["DescPeca"];
        $var_NomePeca = $_GET["local"];

        // URL usada para exportar dados para excel
        $var_url = "relFaturamentoPorPecaRes.php?dt_inicial=" . $dataInicial . "&dt_final=" . $dataFinal . "&local=" . $var_NomePeca . "&DescPeca=" . $var_DescPeca . "&eventos=" . $_GET["eventos"] . "&teatro=" . $var_Teatro;

        if (isset($_GET["periodo"]) && $_GET["periodo"] == "ocorrencia") {
            $gSQL = "EXECUTE SP_REL_FAT002 '" . tratarData($dataInicial) . "', '" . tratarData($dataFinal) . "' ," . $codPeca;
            $descricao = "Relatório de Faturamento/Repasse por Espetáculo (Resumido)";
        } else {
            $gSQL = "EXECUTE SP_REL_FAT002a '" . tratarData($dataInicial) . "', '" . tratarData($dataFinal) . "' ," . $codPeca;
            $descricao = "Relatório de Faturamento/Repasse por Espetáculo <BR>(Resumido - Base na data da Venda)";
        }

        $stmt = executeSQL($mainConnection, $gSQL);
        if (sqlErrors($stmt) == "") {
            if (hasRows($stmt)) {
                $nPag = 1;
                $nLin = 0;
                $bPularSubTotal = false;
                // Mostra cabeçalho somento no modo HTML e não no Excel
                if (!isset($_GET["exportar"]))
                    Cabec(&$nPag, &$nLin, $descricao);
        ?>                
                <table width="670" border="<?php echo (!isset($_GET["exportar"])) ? 0 : 1; ?>" bgcolor="<?php echo (!isset($_GET["exportar"])) ? "LightGrey" : ""; ?>" class="tabela">
                    <tr height="15">
                        <td	width="100" align="left"><font class="label">Local: </font></td>
                        <td width="350" align="left" class="texto" colspan="3"><?php echo $var_Teatro; ?></td>
                        <td	width="100" align="right"><font class="label">Evento: </font></td>
                        <td width="350" align="left" class="texto"><?php echo $var_DescPeca; ?></td>
                    </tr>
                    <tr height="15">
                        <td	width="100" align="left"><font class="label">Data Inicial:</font></td>
                        <td width="125" align="left" class="texto"><?php echo $dataInicial; ?></td>
                        <td	width="100" align="right"><font class="label">Data Final:</font></td>
                        <td width="125" align="left" class="texto"><?php echo $dataFinal; ?></td>
                    </tr>
                </table>

                <br clear="all">
        <?php
                $bPularSubTotal = true;
		$pRs = fetchResult($stmt);
                while ($pRs) {
                    if ($var_NomePeca != $pRs["NomPeca"]) {
                        if ($bPularSubTotal == false) {
        ?>
                        <tr height=2px><td colspan="7">&nbsp</td></tr>
                        <tr>
                            <td align="left" class="label"><strong>Subtotal:</strong></td>
                            <td align="right" class="texto"><strong><?php echo $cont2_2_sub; ?></strong></td>
                            <td align="right" class="texto"><strong><?php echo number_format($cont9_9_sub, 2, ",", "."); ?></strong></td>
                            <td align="right" class="texto"><strong><?php echo number_format($cont3_3_sub, 2, ",", "."); ?></strong></td>
                            <td align="right" class="texto"><strong><?php echo number_format($cont7_7a_sub, 2, ",", "."); ?></strong></td>
                            <td align="right" class="texto"><strong><?php echo number_format($cont8_8_sub, 2, ",", "."); ?></strong></td>
                            <td align="right" class="texto"><strong><?php echo number_format($cont5_5_sub, 2, ",", "."); ?></strong></td>
                        </tr>
                    </table>
<?php
                            $cont1_1_sub = $cont2_2_sub = $cont3_3_sub = $cont4_4_sub =
					   $cont5_5_sub = $cont6_6_sub = $cont7_7_sub = $cont7_7a_sub =
					   $cont8_8_sub = $cont9_9_sub = 0;
                        }
                        $bPularSubTotal = false;
?>
                        <br clear=all>
                        <table width="670px" border="1" bgcolor="<?php echo (!isset($_GET["exportar"])) ? "LightGrey" : ""; ?>" class="tabela">
                            <tr>
                                <td colspan=7 align=left width="900" class=label style="font-size: 12;"><STRONG>Nome da Evento</STRONG>:   <?php echo utf8_encode2($pRs["NomPeca"]); ?></td>
                            </tr>
                        </table>
                        <table width="670" border="<?php echo (!isset($_GET["exportar"])) ? 0 : 1; ?>" bgcolor="<?php echo (!isset($_GET["exportar"])) ? "LightGrey" : ""; ?>" class="tabela">
                            <tr>
                                <td align="center" class="titulogrid" style="with: 170px;">Forma de Pagamento</td>
                                <td align="center" class="titulogrid" style="with: 100px;">Qtd Bilhetes</td>
                                <td align="center" class="titulogrid" style="with: 100px;">Comissão</td>
                                <td align="center" class="titulogrid" style="with: 100px;">Tx. Conveniência</td>
                                <td align="center" class="titulogrid" style="with: 100px;">Spread</td>
                                <td align="center" class="titulogrid" style="with: 100px;">Resultado</td>
                                <td align="center" class="titulogrid" style="with: 100px;">Valor do Repasse</td>
                            </tr>
    <?php
                        $var_NomePeca = $pRs["NomPeca"];
                    }

                    $var_forPagto = $pRs["forpagto"];
                    $cont1 = $cont2 = $cont3 = $cont4 = $cont5 = $cont6 =
			     $cont7 = $cont7a = $cont8 = $cont9 = 0;

                    // Repete valores faturados           
                    while ($pRs) {                        
                        // Condição para criação de funções de cálculo                        
                        if ($var_forPagto == $pRs["forpagto"]) {
                            $formula1 = $pRs["totfat"] - $pRs["TotTxConveniencia"] - $pRs["TotSpread"];

                            if ($pRs["PcTxAdm"]) {
                                $formula3 = $pRs["PcTxAdm"] / 100;
                                $PcTxAdm = $pRs["PcTxAdm"];
                            } else {
                                $formula3 = 0;
                                $PcTxAdm = 0;
                            }
                            $formula4 = $pRs["totfat"] * $formula3;

                            if ($pRs["vlCms"]) {
                                $formula5 = $pRs["totfat"] - $formula4 - $formula1 + $pRs["vlCms"];
                            }

                            $cont1 += round($pRs["totfat"], 2);
                            $cont2 += round($pRs["qtdBilh"], 2);
                            $cont3 += round($pRs["TotTxConveniencia"], 2);
                            $cont4 += round($pRs["TotSpread"], 2);
                            $cont5 += round($formula1, 2);
                            $cont6 += round($PcTxAdm, 2);
                            $cont7 += round($formula4, 2);
                            $cont7a += round(($pRs["TotSpread"] - $formula4), 2);
                            $cont8 += round($formula5, 2);
                            if ($pRs["vlCms"]) {
                                $cont9 += round($pRs["vlCms"], 2);
			    }
                        }else {
                            break;
                        }// Fecha else
			$pRs = fetchResult($stmt);
                    } //fecha while
    ?>
                    <tr>
                        <td align="left" class="label"><strong><?php echo utf8_encode2($var_forPagto); ?></strong></td>
                        <td align="right" class="texto"><strong><?php echo $cont2; ?></strong></td>
                        <td align="right" class="texto"><strong><?php echo number_format($cont9, 2, ",", "."); ?></strong></td>
                        <td align="right" class="texto"><strong><?php echo number_format($cont3, 2, ",", "."); ?></strong></td>
                        <td align="right" class="texto"><strong><?php echo number_format($cont7a, 2, ",", "."); ?></strong></td>
                        <td align="right" class="texto"><strong><?php echo number_format($cont8, 2, ",", "."); ?></strong></td>
                        <td align="right" class="texto"><strong><?php echo number_format(round($cont5, 2), 2, ",", "."); ?></strong></td>
                    </tr>
    <?php
                    $cont1_1_sub += $cont1;
                    $cont2_2_sub += $cont2;
                    $cont3_3_sub += $cont3;
                    $cont4_4_sub+= $cont4;
                    $cont5_5_sub += $cont5;
                    $cont6_6_sub += $cont6;
                    $cont7_7_sub += $cont7;
                    $cont7_7a_sub += $cont7a;
                    $cont8_8_sub += $cont8;
                    $cont9_9_sub += $cont9;

                    $cont1_1 += $cont1;
                    $cont2_2 += $cont2;
                    $cont3_3 += $cont3;
                    $cont4_4 += $cont4;
                    $cont5_5 += $cont5;
                    $cont6_6 += $cont6;
                    $cont7_7 += $cont7;
                    $cont7_7a += $cont7a;
                    $cont8_8 += $cont8;
                    $cont9_9 += $cont9;
                }
    ?>
                <tr height=2px><td colspan="7">&nbsp</td></tr>
                <tr>
                    <td align="left" class="label"><strong>Subtotal:</strong></td>
                    <td align="right" class="texto"><strong><?php echo $cont2_2_sub; ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont9_9_sub, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont3_3_sub, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont7_7a_sub, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont8_8_sub, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont5_5_sub, 2, ",", "."); ?></strong></td>
                </tr>

                <tr><td colspan="7">&nbsp;</td></tr>
                <tr>
                    <td align="left" class="label"><strong>Total Geral:</strong></td>
                    <td align="right" class="texto"><strong><?php echo $cont2_2; ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont9_9, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont3_3, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont7_7a, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont8_8, 2, ",", "."); ?></strong></td>
                    <td align="right" class="texto"><strong><?php echo number_format($cont5_5, 2, ",", "."); ?></strong></td>
                </tr>
            </table>
            <br>
            <table width="670" border="0">
                <tr>
                    <td align="middle">
                        <br>
                        <input class="botao" type="button" value="Imprimir Relatório" name="cmdImprimi" onClick="window.print();">
                        <input class="botao" type="button" value="Fechar Janela" name="cmdFecha" onClick="window.close()">
                        <input class="botao" type="button" value="Exportar Excel" name="cmdExportar" onClick="document.location.href = '<?php echo $var_url . "&exportar=true"; ?>';">
                    </td>
                </tr>
            </table>
<?php
            } else {
?>
                <br><br><br>
                <table border="0" width="500" align="center">
                    <tr>
                        <td	align="center"><font color="red" size="5">Não existem registros para esta especificação !!!</font></td>
                    </tr>
                    <tr height="70">
                        <td align="center"><input class="botao" type="button" value="Fechar Janela" name="cmdFecha" onClick="window.close()"></td>
                    </tr>
                </table>
<?php
            }
        } else {
            print_r(sqlErrors());
        }// Fecha if / else sqlErrors
?>
</body>
</html>