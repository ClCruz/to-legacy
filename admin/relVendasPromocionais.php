<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
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

// Monta e executa query principal do relatório
$strGeral = "WITH TEMP AS (
            SELECT
                DISTINCT
                I.CODVENDA,
                PA.NOMPATROCINADOR,
                EP.NOMPROMOCIONAL,
                PP.NOMPRODUTOPATROCINADOR,
                EP.DATINICIO,
                EP.DATTERMINO,
                P.NOMPECA,
                CLI.NOME,
                CLI.CPF,
                CLI.TELEFONE,
                A.DATAPRESENTACAO,
                A.HORSESSAO
            FROM
                TABEVENTOPATROCINADO EP
                INNER JOIN
                TABPATROCINADOR	PA
                ON PA.CODPATROCINADOR = EP.CODPATROCINADOR
                INNER JOIN
                TABPECA	P
                ON P.CODPECA = EP.CODPECA
                INNER JOIN
                TABINGRESSO	I
                ON I.CODEVENTOPATROCINADO = EP.CODEVENTOPATROCINADO
                INNER JOIN
                TABPRODUTOPATROCINADOR	PP
                ON PP.CODPATROCINADOR = PA.CODPATROCINADOR
                AND PP.CODPRODUTOPATROCINADOR = I.CODPRODUTOPATROCINADOR
                INNER JOIN
                TABCOMPROVANTE	C
                ON C.CODVENDA = I.CODVENDA
                INNER JOIN
                TABAPRESENTACAO	A
                ON A.CODAPRESENTACAO = C.CODAPRESENTACAO
                INNER JOIN
                TABCLIENTE CLI
                ON CLI.CODIGO = C.CODCLIENTE
            WHERE
                A.DATAPRESENTACAO BETWEEN ? AND ? )

            SELECT
                T.CODVENDA,
                T.NOMPATROCINADOR,
                T.NOMPROMOCIONAL,
                T.NOMPRODUTOPATROCINADOR,
                T.DATINICIO,
                T.DATTERMINO,
                T.NOMPECA,
                T.NOME,
                T.CPF,
                T.TELEFONE,
                T.DATAPRESENTACAO,
                T.HORSESSAO,
                I.TIPBILHETE,
                I.VALPAGTO,
                COUNT(1) AS QTD
        FROM
                TEMP	T
                LEFT JOIN
                TABINGRESSO	I
                ON I.CODVENDA = T.CODVENDA
        GROUP BY
                T.CODVENDA,
                T.NOMPATROCINADOR,
                T.NOMPROMOCIONAL,
                T.NOMPRODUTOPATROCINADOR,
                T.DATINICIO,
                T.DATTERMINO,
                T.NOMPECA,
                T.NOME,
                T.CPF,
                T.TELEFONE,
                T.DATAPRESENTACAO,
                T.HORSESSAO,
                I.TIPBILHETE,
                I.VALPAGTO";

$paramsGeral = array($DataIni, $DataFim);
$pRSGeral = executeSQL($connGeral, $strGeral, $paramsGeral);

if(sqlErrors())
	$err = "Erro #001 ". var_dump($paramsGeral) ."<br>". $strGeral."<br>";

if(!isset($err) && $err == ""){

if(hasRows($pRSGeral)){
?>
<html>
<title>Relatório - Borderô de Vendas</title>
<head>
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
    <table width="770" class="tabela" border="0">
        <tr>
            <td colspan="1" rowspan="2"><img align="left" border="0" src="<?php echo multiSite_getLogo(); ?>" alt=""></td>
            <td colspan="1" height="15"></td>
        </tr>
        <tr>
            <td class="tabela" align="center" bgcolor="LightGrey"><font size=4 face="tahoma,verdana,arial"><b>Vendas Promocionais</b></font></td>
        </tr>
        <tr><td colspan="2"></td></tr>
    </table><br><br>

    <table width="760" class="tabela" border="0" bgcolor="LightGrey">
        <tr>
            <td align="center" colspan="6"><font size="2" face="tahoma,verdana,arial"><B>CONTABILIZAÇÃO DOS INGRESSOS</B></font></td>
        </tr>
        <tr>
            <td	align="left" width="240" class="titulogrid">Cliente</td>
            <td	align="center" width="104" class="titulogrid">CPF</td>
            <td	align="center" width="104" class="titulogrid">Telefone</td>
            <td	align="center" width="104" class="titulogrid">Local</td>
            <td	align="center" width="104" class="titulogrid">Evento</td>
            <td align="center" width="104" class="titulogrid">Apresentação</td>
        </tr>

        <?php
            $codVendaAnt = " ";
            $pular  = false;
            $binAux = " ";
            
            //Variaveis com valores totais
             $valorTotalCartaoItau = 0;
             $totalIngressosMeia = 0;
             $totalIngressosAlavancados = 0;
             $totalIngressosVendidos = 0;

            while($dados = fetchResult($pRSGeral)){
                $codVendaAtual = $dados["CODVENDA"];
                if($codVendaAtual != $codVendaAnt){
                    $pular = false;
                    if(isset($existeBilhete) && $existeBilhete == true){
                        echo "</table></td></tr>";
                        $existeBilhete = false;
                    }
                    echo "<tr><td colspan=\"6\"></td></tr>";
        ?>
        <tr>
            <td	align="left"  class="texto"><?php echo ($dados["NOME"] == "") ? "&nbsp;" : $dados["NOME"]; ?></td>
            <td	align="center"  class="texto"><?php echo $dados["CPF"];  ?></td>
            <td	align="center" class="texto"><?php echo ($dados["TELEFONE"] == "") ? "&nbsp;" : $dados["TELEFONE"]; ?></td>
            <td	align="center" class="texto"><?php echo $dados["NOMPRODUTOPATROCINADOR"]; ?></td>
            <td	align="center" class="texto"><?php echo $dados["NOMPECA"]; ?></td>
            <td align="center" class="texto"><?php echo $dados["NOMPROMOCIONAL"]; ?></td>
        </tr>
        <?php
                }else
                    $pular = true;
        ?>
        <tr>
            <td colspan="6">
                <?php
                    if($pular == false){
                        $existeBilhete = true;

                ?>
                <table width="760" border="0" bgcolor="LightGrey">
                    <tr>
                        <td align="left" colspan="3" width="552" class="titulogrid">Tipo</td>
                        <td align="center" width="104" class="titulogrid">Qtd</td>
                        <td align="center" width="100" class="titulogrid">Valor</td>
                        <!--
                        <td align="center" colspan="2" width="234" class="titulogrid">Data</td>
                        <td align="center" width="98" class="titulogrid">Autorização</td>
                        -->
                    </tr>

                    <?php
                    }
                        $codVendaAnt = $dados["CODVENDA"];
                    ?>
                    <tr>
                        <td align="left" colspan="3"  class="texto"><?php echo $dados["TIPBILHETE"]; ?></td>
                        <td align="center"  class="texto"><?php echo $dados["QTD"]; ?></td>
                        <td align="center"  class="texto"><?php echo str_replace(".",",",number_format($dados["VALPAGTO"], 2)); ?></td>
                        <!--
                        <td align="center"  colspan="2" class="texto"><?php //echo $dados["DATAPRESENTACAO"]->format("d/m/y"); ?></td>
                        <td align="center"  class="texto"></td>
                        -->
                    </tr>
                    <?php
                    $valorTotalCartaoItau += ($dados["VALPAGTO"] * $dados["QTD"]);
                    //echo " Valor: ".$dados["VALPAGTO"] ." Qtd: ". $dados["QTD"] ." Total: ". ($dados["VALPAGTO"] * $dados["QTD"]);

                    $totalIngressosVendidos += $dados["QTD"];

                    //Verificar ingressos Meia Entrada
                    if(strtoupper($dados["TIPBILHETE"]) == "MEIA-ITAU")
                        $totalIngressosMeia += $dados["QTD"];
                    
                    //Verificar ingressos alavancados
                    if(strtoupper($dados["TIPBILHETE"]) == "INTEIRA")
                        $totalIngressosAlavancados += $dados["QTD"];
                    
                    if($binAux == $dados["NOMPRODUTOPATROCINADOR"]){
                        if(strtoupper($dados["TIPBILHETE"]) == "MEIA-ITAU"){
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["MEIA-ITAU"]["QTD"] += $dados["QTD"];
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["MEIA-ITAU"]["VALOR"] += ($dados["VALPAGTO"] * $dados["QTD"]);
                        }else{
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["OUTROS"]["QTD"] += $dados["QTD"];
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["OUTROS"]["VALOR"] += ($dados["VALPAGTO"] * $dados["QTD"]);
                        }
                    }else{
                        if(strtoupper($dados["TIPBILHETE"]) == "MEIA-ITAU"){
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["MEIA-ITAU"]["QTD"] = $dados["QTD"];
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["MEIA-ITAU"]["VALOR"] = ($dados["VALPAGTO"] * $dados["QTD"]);
                        }else{
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["OUTROS"]["QTD"] = $dados["QTD"];
                            $arrayBIN[$dados["NOMPRODUTOPATROCINADOR"]]["OUTROS"]["VALOR"] = ($dados["VALPAGTO"] * $dados["QTD"]);
                        }
                    }
                    $binAux = $dados["NOMPRODUTOPATROCINADOR"];
                    
                    if($pular == true)
                        $pular = false;

            }// Fecha while
                    ?>
                </table>
            </td>
        </tr>
    </table><br><br>

    <!-- Apresentação de valores totais -->
    <table width="770" class="tabela" border="0" bgcolor="LightGrey">
        <tr>
            <td colspan="4" align="center"><font size="2" face="tahoma,verdana,arial"><b>CONTABILIZAÇÃO GERAL</b></font></td>
        </tr>
        <tr>
            <td class="titulogrid" width="300">Total pago com cartão Itaú</td>
            <td class="texto" width="90"><?php echo str_replace(".",",",number_format($valorTotalCartaoItau, 2)); ?></td>
            <td class="titulogrid" width="300">Total de ingressos Meia Entrada Itaú</td>
            <td class="texto" width="90"><?php echo $totalIngressosMeia; ?></td>
        </tr>
        <tr>
            <td class="titulogrid" width="300">Total de ingressos alavancados</td>
            <td class="texto" width="90"><?php echo $totalIngressosAlavancados; ?></td>
            <td class="titulogrid" width="300">Total de ingressos vendidos</td>
            <td class="texto" width="90"><?php echo $totalIngressosVendidos; ?></td>
        </tr>
    </table><br><br>

    <!-- Apresentação de valores por BIN -->
    <table width="770" class="tabela" border="0" bgcolor="LightGrey">
        <tr>
            <td colspan="5" align="center"><font size="2" face="tahoma,verdana,arial"><b>CONTABILIZAÇÃO GERAL POR BIN</b></font></td>
        </tr>
        <tr>
            <td class="titulogrid">Tipo de BIN</td>
            <td class="titulogrid">Meia Itaú</td>
            <td class="titulogrid">Outros</td>
            <td class="titulogrid">Qtd Total</td>
            <td class="titulogrid">Valor Transacionado</td>
        </tr>
        <?php
            foreach($arrayBIN as $key => $subArray){
                $totalMeia = 0;
                $totalOutros = 0;
                $valorTotal = 0;
                $subkeyAux = "";
                foreach($subArray as $subkey => $value){
                    $valorTotal += $arrayBIN[$key][$subkey]["VALOR"];
                    if($subkey == "MEIA-ITAU"){
                        $totalMeia += $arrayBIN[$key][$subkey]["QTD"];
                    }else if($subkey == "OUTROS"){
                        $totalOutros += $arrayBIN[$key][$subkey]["QTD"];
                    }
        ?>
        <tr>
            <td class="texto"><?php echo $key; ?></td>
            <td class="texto"><?php echo $totalMeia; ?></td>
            <td class="texto"><?php echo $totalOutros; ?></td>
            <td class="texto"><?php echo $totalMeia + $totalOutros; ?></td>
            <td class="texto">R$ <?php echo str_replace(".", ",", number_format($valorTotal, 2)); ?></td>
        </tr>
        <?php
                    $subkeyAux = $subkey;
                }
            }
        ?>
    </table>
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
            </td>
        </tr>
    </table>
<?php
}else{
    echo $err."<br>";
    print_r(sqlErrors());
}
?>
</body>
</html>