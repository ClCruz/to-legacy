<?php
if (isset($_GET["exportar"]) && $_GET["exportar"] == "true") {
    header("Content-type: application/vnd.ms-excel");
    header("Content-type: application/force-download");
    header("Content-Disposition: attachment; filename=relatorio.xls");
}

function tratarData($data){
    $array = explode("/",$data);
    $dia = $array[0];
    $mes = $array[1];
    $ano = $array[2];
    return $ano."".$mes."".$dia;
}

require_once('../settings/functions.php');
if (isset($_GET["local"])) {
    $mainConnection = getConnection($_GET["local"]);
}
session_start();

$pagina = basename(__FILE__);
?>
<html>
    <title>Relatório - Faturamento</title>
    <HEAD>
        <style type="text/css">
            .div1 {
                font-size:8px;
                padding:0;
                spacing:0;
                width:120px;
                overflow:hidden;
            }
            .div2 {
                font-size:8px;
                padding:0;
                spacing:0;
                width:60px;
                overflow:hidden;
            }
            .div3 {
                font-size:8px;
                padding:0;
                spacing:0;
                width:52px;
                overflow:hidden;
            }
            .div4 {
                font-size:8px;
                padding:0;
                spacing:0;
                width:90px;
                overflow:hidden;
            }
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
            <table width="900" class="tabela" border="<?php echo (!isset($_GET["exportar"])) ? 0 : 1; ?>">
                <tr>
                    <td rowspan="3" width="70" align="center"><img src="../images/ci.gif" border="0"></td>
                    <td align="center" width="620"><font size="2" face="tahoma,verdana,arial"><b>COMPRE INGRESSOS – AGÊNCIA DE VENDAS DE INGRESSOS LTDA<br>CNPJ 07.421.862/0001-37</b></font></td>
                    <td align="right" width="150"><font size="1" face="tahoma,verdana,arial"><b>Data: <?php echo date("d/m/Y"); ?></b></font></td>
                </tr>
                <tr>
                    <td align="center" rowspan="2"><font size="2" face="tahoma,verdana,arial"><b><?php echo $descricao; ?></b></font></td>
                    <td align="right" width="150"><font size="1" face="tahoma,verdana,arial"><b>Hora: <?php echo date("G:i:s"); ?></b></font></td>
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
        $var_NomeBase = $_GET["local"];

        $var_url = "relFaturamentoPorPeca.php?dt_inicial=" . $dataInicial . "&dt_final=" . $dataFinal . "&local=" . $var_NomeBase . "&DescPeca=" . $var_DescPeca . "&eventos=" . $_GET["eventos"] . "&teatro=" . $var_Teatro;


        if (isset($_GET["periodo"]) && $_GET["periodo"] == "ocorrencia") {
            $gSQL = "EXECUTE SP_REL_FAT002 '" . tratarData($dataInicial) . "', '" . tratarData($dataFinal) . "' ," . $codPeca;
            $descricao = "Repasses por Espetáculo e Forma de Pagamento (Detalhado)";
        } else {
            $gSQL = "EXECUTE SP_REL_FAT002a '" . tratarData($dataInicial) . "', '" . tratarData($dataFinal) . "' ," . $codPeca;
            $descricao = "Repasses por Espetáculo e Forma de Pagamento<BR>(Detalhado - Base na data da Venda)";
        }


        $stmt = executeSQL($mainConnection, $gSQL);

        if (sqlErrors($stmt) == "") {
            if (hasRows($stmt)) {
                $nPag = 1;
                $nLin = 0;
                $nPrimeiraVez = 0;
                // Mostra cabeçalho somento no modo HTML e não no Excel
                if (!isset($_GET["exportar"]))
                    Cabec(&$nPag, &$nLin, $descricao);
        ?>
                <form name="frmVisaoSint" method="post">
                    <table width="900" border="<?php echo (!isset($_GET["exportar"])) ? 0 : 1; ?>" bgcolor="<?php echo (!isset($_GET["exportar"])) ? "LightGrey" : ""; ?>" class="tabela">
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
                    <br clear="all"/>
            <?php
                $bPularSubTotal = true;

                $cont1_1_sub = $cont2_2_sub = $cont3_3_sub = $cont4_4_sub =
			       $cont5_5_sub = $cont6_6_sub = $cont7_7_sub = $cont7_7a_sub =
			       $cont8_8_sub = 0;

                $nLin = $nLin + 3;
                // Repete todos os registros de faturamento
		$pRs = fetchResult($stmt);
                while ($pRs) {
                    if ($var_NomePeca != $pRs["NomPeca"]) {
                        if ($bPularSubTotal == false) {
            ?>
                            <tr>
                                <td	align="left"   class="div1"><STRONG>Total por Evento:</STRONG></td>
                                <td	align="center" class="div4">---</td>
                                <td	align="right"  class="div3"><STRONG><?php echo number_format($cont1_1_sub, 2, ",", "."); ?></STRONG></td>
                                <td	align="right"  class="div3"><STRONG><?php echo $cont2_2_sub; ?></STRONG></td>
                                <td	align="right"  class="div3"><STRONG><?php echo number_format($cont6_6_sub, 2, ",", "."); ?></STRONG></td>
                                <td	align="right"  class="div2"><STRONG><?php echo number_format($cont3_3_sub, 2, ",", "."); ?></STRONG></td>
                                <td	align="center" class="div1">---</td>
                                <td	align="right"  class="div3"><STRONG><?php echo number_format($cont4_4_sub, 2, ",", "."); ?></STRONG></td>
                                <td	align="right"  class="div2"><STRONG><?php echo number_format($cont5_5_sub, 2, ",", "."); ?></STRONG></td>
                                <td	align="center" class="div3">---</td>
                                <td	align="right"  class="div3"><STRONG><?php echo number_format($cont7_7_sub, 2, ",", "."); ?></STRONG></td>
                                <td	align="right"  class="div3"><STRONG><?php echo number_format($cont7_7a_sub, 2, ",", "."); ?></STRONG></td>
                                <td	align="right"  class="div3"><STRONG><?php echo number_format($cont8_8_sub, 2, ",", "."); ?></STRONG></td>
                            </tr>
                        </table>
			<br clear="all"/>
        <?php
                        }
                        $bPularSubTotal = false;
                        $cont1_1_sub = $cont2_2_sub = $cont3_3_sub = $cont4_4_sub =
				       $cont5_5_sub = $cont6_6_sub = $cont7_7_sub = $cont7_7a_sub =
				       $cont8_8_sub = 0;
        ?>
                        <br clear=all>
                        <br clear=all>
                        <table width="900" class="tabela" border="<?php echo (!isset($_GET["exportar"])) ? 0 : 1; ?>" bgcolor="<?php echo (!isset($_GET["exportar"])) ? "LightGrey" : ""; ?>">
                            <tr>
                                <td align="left" width="900" colspan="13" class="label" style="font-size: 12;"><STRONG>Nome da Evento</STRONG>:   <?php echo utf8_encode2($pRs["NomPeca"]); ?></td>
                            </tr>
                        </table>
        <?php
                        $var_NomePeca = $pRs["NomPeca"];
                    }

                    $var_forPagto = $pRs["forpagto"];
        ?>
                </table>
                <br clear="all"/>
                <table name="tab" id="tab" style="width: 900px; " border="<?php echo (!isset($_GET["exportar"])) ? 0 : 1; ?>" bgcolor="<?php echo (!isset($_GET["exportar"])) ? "LightGrey" : ""; ?>" class="tabela">
                    <tr>
                        <td align="left" width="900" colspan="13" class="label"><STRONG>Forma de Pagamento</STRONG>:   <?php echo utf8_encode2($var_forPagto); ?></td>
                    </tr>
        <?php $nLin = $nLin + 1; ?>
                    <tr>
                        <td	align="center" width="200" class="titulogrid">Tipo do Bilhete</td>
                        <td	align="center" width="60" class="titulogrid">Setor</td>
                        <td	align="center" width="60" class="titulogrid">Vlr. Apresentado</td>
                        <td	align="center" width="60" class="titulogrid">Qtd Bilh</td>
                        <td	align="center" width="50" class="titulogrid">Comissão</td>
                        <td	align="center" width="60" class="titulogrid">Tx. Conveniência</td>
                        <td	align="center" width="110" class="titulogrid">TSP Administração</td>
                        <td	align="center" width="60" class="titulogrid">Valor</td>
                        <td	align="center" width="60" class="titulogrid">Valor<br>do<br>Repasse</td>
                        <td	align="center" width="60" class="titulogrid">Tx.Adm.%</td>
                        <td	align="center" width="60" class="titulogrid">Valor</td>
                        <td	align="center" width="60" class="titulogrid">Spread</td>
                        <td	align="center" width="60" class="titulogrid">Resultado</td>
                    </tr>
        <?php
                    $nLin = $nLin + 2;
                    $cont1 = $cont2 = $cont3 = $cont4 = $cont5 = $cont6 =
			     $cont7 = $cont7a = $cont8 = 0;

                    // Repete valores faturados
                    while ($pRs) {
                        // Condição para criação de funções de cálculo
                        if ($var_forPagto == $pRs["forpagto"]) {
                            $formula1 = $pRs["totfat"] - $pRs["TotTxConveniencia"] - $pRs["TotSpread"];

                            if ($pRs["PcTxAdm"]) {
                                $formula3 = $pRs["PcTxAdm"] / 100;
                                $PcTxAdm = $pRs["PcTxAdm"];
                            } else {
                                $PcTxAdm = 0;
                                $formula3 = 0;
                            }
                            $formula4 = $pRs["totfat"] * $formula3;

                            if ($pRs["vlCms"]) {
                                $formula5 = $pRs["totfat"] - $formula4 - $formula1 + $pRs["vlCms"];
                            }
        ?>
                            <tr>
                                <td	align="left" width="210" class="texto"><?php echo utf8_encode2($pRs["tipbilhete"]); ?></td>
                                <td	align="left" width="" class="texto"><?php echo utf8_encode2($pRs["nomsetor"]); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round($pRs["totfat"], 2), 2, ",", "."); ?></td>
                                <td	align="right" width="" class="texto"><?php echo $pRs["qtdBilh"]; ?></td>
                                <td	align="right" width="" class="texto"><?php echo (($pRs["vlCms"]) ? number_format(round($pRs["vlCms"], 2), 2, ",", ".") : "0.00"); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round($pRs["TotTxConveniencia"], 2), 2, ",", "."); ?></td>
                                <td	align="left" width="130" class="texto"><?php echo (($pRs["destiplct"]) ? $pRs["destiplct"] : "-----------------"); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round($pRs["TotSpread"], 2), 2, ",", "."); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round($formula1, 2), 2, ",", "."); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round($PcTxAdm, 2), 2, ",", "."); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round($formula4, 2), 2, ",", "."); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round(($pRs["TotSpread"]), 2) - round($formula4, 2), 2, ",", "."); ?></td>
                                <td	align="right" width="" class="texto"><?php echo number_format(round($formula5, 2), 2, ",", "."); ?></td>
                            </tr>
        <?php
                            $nLin = $nLin + 1;

                            $cont1 += round($pRs["totfat"], 2);
                            $cont2 += $pRs["qtdBilh"];
                            $cont3 += round($pRs["TotTxConveniencia"], 2);
                            $cont4 += round($pRs["TotSpread"], 2);
                            $cont5 += round($formula1, 2);
                            if ($pRs["vlCms"]) {
                                $cont6 += round($pRs["vlCms"], 2);
                            }
                            $cont7 += round($formula4, 2);
                            $cont7a += round(($pRs["TotSpread"] - $formula4), 2);
                            $cont8 += round($formula5, 2);

                            // Se true reescreve titulo da coluna dos itens
                            if ($nLin > 35) {
                                echo '</table>';
                                // Mostra cabeçalho somento no modo HTML e não no Excel
                                if (!isset($_GET["exportar"]))
                                    Cabec(&$nPag, &$nLin, $descricao);

                                if (!isset($_GET["exportar"]))
                                    echo "<table width=\"900\" border=\"0\" bgcolor=\"LightGrey\" class=\"tabela\">";
                                else
                                    echo "<table width=\"900\" border=\"1\" class=\"tabela\">";

                                echo "<tr>";
                                echo "<td	align=\"center\" width=\"200\" class=\"titulogrid\">Tipo do Bilhete</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Setor</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Vlr. Apresentado</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Qtd Bilh</td>";
                                echo "<td	align=\"center\" width=\"50\" class=\"titulogrid\">Comissão</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Tx. Conveniência</td>";
                                echo "<td	align=\"center\" width=\"110\" class=\"titulogrid\">TSP Administração</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Valor</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Valor<br>do<br>Repasse</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Tx.Adm.%</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Valor</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Spread</td>";
                                echo "<td	align=\"center\" width=\"60\" class=\"titulogrid\">Resultado</td>";
                                echo "</tr>";
                                $nLin = $nLin + 3;
                            }
                        } else {
                            break;
                        }// Fecha else
			$pRs = fetchResult($stmt);
                    } //fecha while
        ?>
                    <tr>
                        <td	align="left" width="200"><STRONG>Subtotal:</STRONG></td>
                        <td	align="center" width="60">---</td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont1, 2, ",", "."); ?></STRONG></td>
                        <td	align="right" width="" class=""><STRONG><?php echo $cont2; ?></STRONG></td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont6, 2, ",", "."); ?></STRONG></td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont3, 2, ",", "."); ?></STRONG></td>
                        <td	align="center" width="">---</td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont4, 2, ",", "."); ?></STRONG></td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont5, 2, ",", "."); ?></STRONG></td>
                        <td	align="center" width="" class="">---</td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont7, 2, ",", "."); ?></STRONG></td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont7a, 2, ",", "."); ?></STRONG></td>
                        <td	align="right" width="" class=""><STRONG><?php echo number_format($cont8, 2, ",", "."); ?></STRONG></td>
                    </tr>
<?php
                    $nLin = $nLin + 2;

                    $cont1_1_sub += $cont1;
                    $cont2_2_sub += $cont2;
                    $cont3_3_sub += $cont3;
                    $cont4_4_sub += $cont4;
                    $cont5_5_sub += $cont5;
                    $cont6_6_sub += $cont6;
                    $cont7_7_sub += $cont7;
                    $cont7_7a_sub += $cont7a;
                    $cont8_8_sub += $cont8;

                    $cont1_1 += $cont1;
                    $cont2_2 += $cont2;
                    $cont3_3 += $cont3;
                    $cont4_4 += $cont4;
                    $cont5_5 += $cont5;
                    $cont6_6 += $cont6;
                    $cont7_7 += $cont7;
                    $cont7_7a += $cont7a;
                    $cont8_8 += $cont8;

                    $cont1 = $cont2 = $cont3 = $cont4 = $cont5 = $cont6 =
			     $cont7 = $cont7a = $cont8 = 0;
                }//Fecha while
?>
                <tr>
                    <td	align="left"  class="div1"><STRONG>Total por Evento:</STRONG></td>
                    <td	align="center" class="div4">---</td>
                    <td	align="right"  class="div3"><STRONG><?php echo number_format($cont1_1_sub, 2, ",", "."); ?></STRONG></td>
                    <td	align="right"  class="div3"><STRONG><?php echo $cont2_2_sub; ?></STRONG></td>
                    <td	align="right"  class="div3"><STRONG><?php echo number_format($cont6_6_sub, 2, ",", "."); ?></STRONG></td>
                    <td	align="right"  class="div2"><STRONG><?php echo number_format($cont3_3_sub, 2, ",", "."); ?></STRONG></td>
                    <td	align="center" class="div1">---</td>
                    <td	align="right"  class="div3"><STRONG><?php echo number_format($cont4_4_sub, 2, ",", "."); ?></STRONG></td>
                    <td	align="right"  class="div2"><STRONG><?php echo number_format($cont5_5_sub, 2, ",", "."); ?></STRONG></td>
                    <td	align="center" class="div3">---</td>
                    <td	align="right"  class="div3"><STRONG><?php echo number_format($cont7_7_sub, 2, ",", "."); ?></STRONG></td>
                    <td	align="right"  class="div3"><STRONG><?php echo number_format($cont7_7a_sub, 2, ",", "."); ?></STRONG></td>
                    <td	align="right"  class="div3"><STRONG><?php echo number_format($cont8_8_sub, 2, ",", "."); ?></STRONG></td>
                </tr>
                <tr><td><font size="1">&nbsp</font></td></tr>
                <tr>
                    <td	align="left" width="137"><STRONG>Total Geral:</STRONG></td>
                    <td	align="center" width="52">---</td>
                    <td	align="right" width="65" class=""><STRONG><?php echo number_format($cont1_1, 2, ",", "."); ?></STRONG></td>
                    <td	align="right" width="45" class=""><STRONG><?php echo number_format($cont2_2, 0, ",", "."); ?></STRONG></td>
                    <td	align="right" width="50" class=""><STRONG><?php echo number_format($cont6_6, 2, ",", "."); ?></STRONG></td>
                    <td	align="right" width="68" class=""><STRONG><?php echo number_format($cont3_3, 2, ",", "."); ?></STRONG></td>
                    <td	align="center" width="93">---</td>
                    <td	align="right" width="50" class=""><STRONG><?php echo number_format($cont4_4, 2, ",", "."); ?></STRONG></td>
                    <td	align="right" width="50" class=""><STRONG><?php echo number_format($cont5_5, 2, ",", "."); ?></STRONG></td>
                    <td	align="center" width="50">---</td>
                    <td	align="right" width="" class=""><STRONG><?php echo number_format($cont7_7, 2, ",", "."); ?></STRONG></td>
                    <td	align="right" width="" class=""><STRONG><?php echo number_format($cont7_7a, 2, ",", "."); ?></STRONG></td>
                    <td	align="right" width="" class=""><STRONG><?php echo number_format($cont8_8, 2, ",", "."); ?></STRONG></td>
                </tr>
            </table>
            <br clear="all"/>

            <table width="900" border="0">
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
            </form>
<?php
            }
        } else {
            print_r(sqlErrors());
        }// Fecha if / else sqlErrors
?>
</body>
</html>