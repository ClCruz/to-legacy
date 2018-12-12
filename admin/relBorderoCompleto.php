<?php
if(isset($_GET["exportar"]) && $_GET["exportar"] == "true"){
  header("Content-type: application/vnd.ms-excel");
  header("Content-type: application/force-download");
  header("Content-Disposition: attachment; filename=relatorio.xls");
  header("Pragma: no-cache");
}

require_once("../settings/functions.php");
require_once("../settings/Utils.php");

session_start();
$connMiddleway = mainConnection();
$conn = getConnectionTsp();

$rs = executeSQL($connMiddleway, 'SELECT ID_BASE FROM MW_BASE WHERE DS_NOME_BASE_SQL = ?', array($_SESSION["NomeBase"]), true);
$connGeral = getConnection($rs["ID_BASE"]);

// Variaveis passadas por parametro pela url
$codApresentacao = $_GET["CodApresentacao"];
$codPeca = (isset($_GET["CodPeca"]) && !empty($_GET["CodPeca"])) ? $_GET["CodPeca"] : "";
$codSala = (isset($_GET["Sala"]) && !empty($_GET["Sala"])) ? $_GET["Sala"] : "";
$dataIni = (isset($_GET["DataIni"]) && !empty($_GET["DataIni"])) ? $_GET["DataIni"] : "null";
$dataFim = (isset($_GET["DataFim"]) && !empty($_GET["DataFim"])) ? $_GET["DataFim"] : "null";
$horSessao = (isset($_GET["HorSessao"]) && !empty($_GET["HorSessao"])) ? $_GET["HorSessao"] : "null";
$horSessao = (($_GET['Small'] == '1') ? '--' : $horSessao);
$resumido = $_GET["Resumido"];
$var_url   = "relBorderoCompleto.php?CodPeca=".$_GET["CodPeca"]."&logo=imagem&Resumido=".$_GET["Resumido"]."&Small=".$_GET['Small']."&DataIni=".$_GET["DataIni"]."&DataFim=".$_GET["DataFim"]."&HorSessao=".$_GET["HorSessao"]."&Sala=".$_GET["Sala"];

$queryBase = "SELECT ds_local_evento DS_NOME_TEATRO FROM tabpeca tp INNER JOIN ci_middleway..mw_local_evento le ON le.id_local_evento = tp.id_local_evento WHERE tp.CodPeca = ?";
$nomeBase = executeSQL($connGeral, $queryBase, array($_GET["CodPeca"]), true);

if (empty($nomeBase)) {
  $queryBase = "SELECT DISTINCT DS_NOME_TEATRO FROM MW_BASE WHERE DS_NOME_BASE_SQL = ?";
  $nomeBase = executeSQL($connMiddleway, $queryBase, array($_SESSION["NomeBase"]), true);
}

// Monta e executa query principal do relatório
$strGeral = "SP_REL_BORDERO" . (($codSala == 'TODOS') ? '10' : '01') . " 'Emerson', " . $codPeca . "," . $codSala . "," . $dataIni . "," . $dataFim . ",'" . (($_GET['Small'] == '1') ? '--' : $horSessao) . "','" . $_SESSION["NomeBase"] . "'";

$pRSGeral = executeSQL($conn, $strGeral, array(), true);
if (sqlErrors ())
  $err = "Erro #002 <br/>" . var_dump($paramsGeral) . "<br/>" . $strGeral . "<br/>";


$array = explode(":", $pRSGeral["NomResPeca"]);
$PPArray = ($array[0] != "") ? $array[0] : "N&atilde;o Cadastrado";
$SPArray = ($array[1] != "") ? $array[1] : "N&atilde;o Cadastrado";
$TPArray = ($array[2] != "") ? $array[2] : "N&atilde;o Cadastrado";

if (isset($err) && $err != "") {
  echo $err . "<br/>";
  print_r(sqlErrors());
}

if ($_GET['Small'] == '1') {
  $strBordero = "SP_REL_BORDERO14 'Emerson', " . $codPeca . "," . $codSala . "," . $dataIni . "," . $dataFim . ",'--','" . $_SESSION["NomeBase"] . "'";
  $resultBordero = executeSQL($conn, $strBordero, array());

  if (hasRows($resultBordero)) {
    $numsArray = array();
    while ($rsBordero = fetchResult($resultBordero)) {
      $numsArray[] = $rsBordero['NumBordero'];
    }
    $pRSGeral['NumBordero'] = gerarNotacaoIntervalo($numsArray);
  }
}

$imagem = getSalaImg($codSala, $connGeral);

if (isset($err) && $err != "") {
  echo $err . "<br/>";
  print_r(sqlErrors());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml">
  <head>    
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="Content-Language" content="pt-Br" />
    <meta name="Copyright" content="Copyright &copy; 2013" />
  </head>
    <title>Borderô de Vendas</title>
    <link rel="stylesheet" type="text/css" href="../stylesheets/estilos_ra.css" />
    <link rel="stylesheet" type="text/css" href="../stylesheets/padraoRelat.CSS" />
    <link rel="stylesheet" type="text/css" href="../stylesheets/relatorio_bordero.css" />
  
  <body leftmargin="0" topmargin="0">
    <script language="VBScript">
      function ZeroData(data) {
        ZeroData = Right(("0" & day(data)),2) & "/" & Right(("0" & month(data)),2) & "/" & year(data);
      }
    </script>
    <table width=650 class="tabela" border="0">
      <tr>
          <?php if(isset($_GET["exportar"]) && $_GET["exportar"] == "true") { ?>
              <td width="80">
                  <img alt="" align="left" border="0" src="<?php echo multiSite_getLogoFullURI()?>" />
              </td>
          <?php }else{ ?>
              <td>
                  <div class="logoTeatro">
                      <?php if( !empty($imagem) ): ?>
                      <img src="data:img/jpeg;base64,<?php echo base64_encode($imagem); ?>" alt="" />
                      <?php endif; ?>
                  </div>
              </td>
          <?php } ?>  
        <td width="300" class="tabela" align="center" bgcolor="LightGrey"><b><font size=2 face="tahoma,verdana,arial">Borderô de Vendas</font><br/>Contabilização dos Ingressos</b></td>
      </tr>
      <tr>
        <td colspan="3">
          <table class="tabela" width="648">
            <tr>
              <td align="right" width="70"><font size=1 face="tahoma,verdana,arial"><b>Local:</b></font></td>
              <td align="left" width="370" style="font-size: 14px;"><?php echo utf8_encode2($nomeBase["DS_NOME_TEATRO"]); ?></td>
              <td align="right" width="120"><font size=1 face="tahoma,verdana,arial"><b>Borderô nº</b></font></td>
              <td align="left" width="220"><?php echo $pRSGeral["NumBordero"]; ?></td>
            </tr>
            <tr>
              <td align="right" width="70"><font size=1 face="tahoma,verdana,arial"><b>Evento:</b></font></td>
              <td align="left" width="370"><?php echo utf8_encode2($pRSGeral["NomPeca"]); ?></td>
              <td align="right" width="120"><font size=1 face="tahoma,verdana,arial"><b>Apresentação nº</b></font></td>
              <td align="left" width="220"><?php echo $pRSGeral["NumBordero"]; ?></td>
            </tr>
            <tr>
              <td align="right"><font size=1 face="tahoma,verdana,arial"><b>Responsável:</b></font></td>
              <td align="left"><?php echo utf8_encode2($PPArray); ?></td>
              <?php
              $DataIni2 = substr($dataIni, -2, 2) . '/' . substr($dataIni, -4, 2) . '/' . substr($dataIni, 0, 4);
              $DataFim2 = substr($dataFim, -2, 2) . '/' . substr($dataFim, -4, 2) . '/' . substr($dataFim, 0, 4);
              if ($_GET['Small'] != '1') {
              ?>
                <td align="right"><font size=1 face="tahoma,verdana,arial"><b>Data e Horário:</b></font></td>
                <td align="left"><?php echo $pRSGeral["DatApresentacao"]->format("d/m/Y") . " | " . $pRSGeral["HorSessao"]; ?></td>
              <?php } else {
              ?>
                <td align="right"><font size=1 face="tahoma,verdana,arial"><b>Datas:</b></font></td>
                <td align="left"><?php echo $DataIni2 . " à " . $DataFim2; ?></td>
              <?php } ?>
            </tr>
            <tr>
              <td align="right"><font size=1 face="tahoma,verdana,arial"><b>CNPJ/CPF:</b></font></td>
              <td align="left"><?php echo $SPArray; ?></td>
              <td align="right"><font size=1 face="tahoma,verdana,arial"><b>Dia:</b></font></td>
              <td align="left"><?php echo getDay($pRSGeral["DatApresentacao"]); ?></td>
            </tr>
            <tr>
              <td align="right" rowspan="3" valign="top"><font size=1 face="tahoma,verdana,arial"><b>Endereço:</b></font></td>
              <td align="left" rowspan="3" valign="top"><?php echo utf8_encode2($TPArray); ?></td>

            </tr>
            <tr>
              <td align="right"><font size=1 face="tahoma,verdana,arial"><b>Local:</b></font></td>
              <td align="left"><?php echo utf8_encode2($pRSGeral["NomSala"]); ?></td>
            </tr>
            <tr>
              <td align="right"><font size=1 face="tahoma,verdana,arial"><b>Lotação/Capacidade:</b></font></td>
              <td align="left"><?php echo $pRSGeral["Lugares"]; ?></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <br/>

    <?php
                $lotacao = $pRSGeral["Lugares"];
                $totNVendidos = 0;
                $totPagantes = 0;
                $totNPagantes = 0;
                $totPublico = 0;
                // Quantidade Total de Ingressos Excedidos na Apresentação
                $qtdIngressosExcedidos = 0;
                // Lista de Tipos de Bilhetes Vendidos
                $ingressosExcedentes = array();

                $query = executeSQL($conn, $strGeral, $paramsGeral);
                while ($pRSBordero = fetchResult($query)) {
                  $nPag = 1;
                  $nLin = 0;
                  $totTransacoes = 0;
                  $totNVendidos = $totNVendidos + ($pRSBordero["Lugares"] - $pRSBordero["PubTotal"]);
                  $totNPagantes = $totNPagantes + ($pRSBordero["PubTotal"] - $pRSBordero["Pagantes"]);
                  $totPagantes = $totPagantes + $pRSBordero["Pagantes"];
                  $totPublico = $totPublico + $pRSBordero["PubTotal"];
                  if ($resumido == 0) {
    ?>
                    <table width="656" class="tabela tblResumo" border="0">
                      <tr>
                        <td align=center width="162" class="tabela"><b>Ingressos Não Vendidos:</b><?php echo $totNVendidos; ?></td>
                        <td align=center width="162" class="tabela"><b>Público Convidado:</b><?php echo $totNPagantes; ?></td>
                        <td align=center width="162" class="tabela"><b>Público Pagante:</b><?php echo $totPagantes; ?></td>
                        <td align=center width="163" class="tabela"><b>Público Total:</b><?php echo $totPublico; ?></td>
                      </tr>
                    </table>
                    <br/>
                    <table width="656" class="tabela" border="0" bgcolor="LightGrey">
                      <tr>
                        <td align="center" colspan="7"><font size="2" face="tahoma,verdana,arial"><b>1 - VENDAS BORDERÔ</b></font></td>
                      </tr>
                      <tr>
                        <td	align="left" width="104" class="titulogrid">Setor</td>
                        <td	align="left" width="240" class="titulogrid">Tipo de Ingressos</td>
                        <td	align="right" width="104" class="titulogrid">Qtde Estornados</td>
                        <td	align="right" width="104" class="titulogrid">Qtde Vendidos</td>
                        <td	align="right" width="104" class="titulogrid">Acessados Urna</td>
                        <td	align="right" width="104" class="titulogrid">Preço</td>
                        <td	align="right" width="104" class="titulogrid">Sub Total</td>
                      </tr>
      <?php
                    $strSqlBilhete = ($codSala == 'TODOS') ? "SP_REL_BORDERO05 '" . $dataIni . "','" . $dataFim . "'," . $codPeca . ",'" . (($horSessao == "--") ? "null" : $horSessao) . "','" . $_SESSION["NomeBase"] . "'" : "SP_REL_BORDERO04 " . $pRSBordero["CodApresentacao"] . ",'" . $_SESSION["NomeBase"] . "'";
                    $queryBilhete = executeSQL($conn, $strSqlBilhete);
                    if (sqlErrors ()) {
                      echo "Erro #003: ";
                      print_r(sqlErrors());
                      echo "<br/>" . $strSqlBilhete;
                    }
                    while ($pRSBilhete = fetchResult($queryBilhete)) {
                      if ($resumido == "0") {
                        $strIngExc = "SP_CON_INGRESSO_EXCEDIDO ?, ?";
                        $paramIngExc = array($pRSBilhete["CodTipBilhete"], $_SESSION["NomeBase"]);
                        $strIngExc = logQuery($strIngExc, $paramIngExc);
                        $rsIngExc = executeSQL($conn, $strIngExc, array());
                        if (hasRows($rsIngExc)) {
                          $qtdIngressosExcedidos += $pRSBilhete["QtdeVendidos"];
                        }
      ?>
                        <tr>
                          <td	align=left class=texto><?php echo formatarConteudoVazio(utf8_encode2($pRSBilhete["NomSetor"])); ?></td>
                          <td	align=left  class=texto><?php echo formatarConteudoVazio(utf8_encode2($pRSBilhete["TipBilhete"])); ?></td>
                          <td	align=right  class=texto><?php echo formatarConteudoVazio($pRSBilhete["QtdeEstornados"]); ?></td>
                          <td	align=right  class=texto><?php echo formatarConteudoVazio($pRSBilhete["QtdeVendidos"]); ?></td>
                          <td	align=right class=texto><?php echo ($pRSBilhete["QtdeAcessos"] == 0 or $pRSBilhete["StaTipBilhMeia"] == 'S') ? '' : $pRSBilhete["QtdeAcessos"]; ?></td>
                          <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSBilhete["Preco"], 2, ",", "."); ?></td>
                          <td	align=right class=texto >R$&nbsp;<?php echo number_format($pRSBilhete["Total"], 2, ",", "."); ?></td>
                        </tr>
      <?php
                      }
                      
                      $nTotalEstornados += $pRSBilhete['QtdeEstornados'];
                      $nTotalVendidos += $pRSBilhete['QtdeVendidos'];
                      $nTotalAcessados += $pRSBilhete["StaTipBilhMeia"] == 'S' ? 0 : $pRSBilhete["QtdeAcessos"];

                      $nTotalVendas = $nTotalVendas + $pRSBilhete["Total"];
                      $totTransacoes += $pRSBilhete["QtdeVendidos"];
                      $ingressosExcedentes[] = $pRSBilhete["CodTipBilhete"];
                    }

                    if ($resumido == "0") {
      ?>
                      <tr>
                        <td colspan="2" bgcolor="#FFFFFF" rowspan="2" align="center" class="tabela"><font size=2 face="tahoma,verdana,arial"><b>Taxa de Ocupação:</b>&nbsp;&nbsp;  <?php echo number_format((($totPublico / $lotacao) * 100), 2, ",", "."); ?> %</font></td>
                        <td bgcolor="LightGrey" colspan="3" align="left" class="label"></td>
                        <td bgcolor="LightGrey" colspan="2" align="center" class="label"><b>TOTAL DE VENDAS</b></td>
                      </tr>
                      <tr>
                        <td bgcolor="LightGrey" align="right" class="label"><b style="float: left">TOTAIS</b><b><?php echo $nTotalEstornados; ?></b></td>
                        <td bgcolor="LightGrey" align="right" class="label"><b><?php echo $nTotalVendidos; ?></b></td>
                        <td bgcolor="LightGrey" align="right" class="label"><b><?php echo $nTotalAcessados; ?></b></td>

                        <td bgcolor="LightGrey" colspan="2" align="right" class="label"><b>R$&nbsp;&nbsp;<?php echo number_format($nTotalVendas, 2, ",", "."); ?></b></td>
                      </tr>
                    </table>
                    <br clear="all"/>

                    <table width=656 class="tabela" border="0" bgcolor="LightGrey">
                      <tr>
                        <td align="center" colspan="3"><font size=2 face="tahoma,verdana,arial"><b>2 - DESCONTOS BORDERÔ</b></font></td>
                      </tr>
                      <tr>
                        <td	align="left" class="titulogrid">Tipo de Débito</td>
                        <td	align="right" class="titulogrid">% ou R$ Fixo</td>
                        <td	align="right" class="titulogrid">Valor</td>
                      </tr>
      <?php
                    }
                    $nTotalDesp = 0;
                    $nLin = $nLin + 4;

                    if ($codSala == 'TODOS') {
                      $gSQL = "SELECT CodApresentacao
                               FROM " . $_SESSION["NomeBase"] . "..tabapresentacao
                               WHERE datapresentacao BETWEEN ? AND ?
                                     AND codpeca = ?" . (($_GET['Small'] != '1') ? '
                                     AND horsessao = ?' : '');
                      $paramApre = (($_GET['Small'] == '1') ? array($dataIni, $dataFim, $codPeca) : array($dataIni, $dataFim, $codPeca, $horSessao));
                      $rsApresentacao = executeSQL($conn, $gSQL, $paramApre);
                      $qtdeSalas = numRows($conn, $gSQL, $paramApre);
                      $rsApresentacoes = fetchResult($rsApresentacao);
                    }

                    $despesas = array();
                    
                    $listaIngExcedentes = "";
                    $ingressosExcedentes = array_unique($ingressosExcedentes);
                    foreach ($ingressosExcedentes as $key => $value) {
                        $listaIngExcedentes .= $value .",";
                    }
                    $listaIngExcedentes = substr(trim($listaIngExcedentes), 0, strlen(trim($listaIngExcedentes)) - 1);
                    
                    $strSqlDetTemp = "SP_REL_BORDERO_VENDAS;" . (($codSala == 'TODOS') ? '11' : '5') . " '" . $dataIni . "','" . $dataFim . "'," . $codPeca . "," . $codSala . ",'" . $horSessao . "','" . $_SESSION["NomeBase"] . "'";
                    $queryDetTemp = executeSQL($conn, $strSqlDetTemp);
                    while ($pRSDetalhamento = fetchResult($queryDetTemp)) {
                      $nBrutoTot += $pRSDetalhamento["totfat"];
                      $nTotLiqu += $pRSDetalhamento["liquido"];
                    }

                    $taxaDosCartoes = $nBrutoTot - $nTotLiqu;
                    $taxaDosCartoesPorSala = ($taxaDosCartoes > 0) ? $taxaDosCartoes / $qtdeSalas : $taxaDosCartoes;

                    //Percorre todas as apresentações entre o período informado
                    do {
                      //Obtem os débitos do borderô da tabela tabDebBordero
                      $strDebito = "SP_REL_BORDERO_COMPLETO ?, ?, ?, ?, ?, ?";
                      //Define os parâmetros para a consulta dos débitos
                      if ($codSala == 'TODOS') {
                        //Parâmetros p/ quando selecionado Todas as Apresentações
                        $paramDebito = array($codPeca,
                            $rsApresentacoes["CodApresentacao"],
                            $dataIni,
                            $qtdIngressosExcedidos,
                            $listaIngExcedentes,
                            $taxaDosCartoesPorSala);
                      } else {
                        //Parâmetros p/ quando selecionado uma única Apresentação
                        $paramDebito = array($pRSBordero["CodPeca"],
                            $pRSBordero["CodApresentacao"],
                            $pRSBordero["DatApresentacao"]->format("Ymd"),
                            0,
                            $listaIngExcedentes,
                            $taxaDosCartoesPorSala);
                      }

                      $strDebito = logQuery($strDebito, $paramDebito);
                      $queryDebito = executeSQL($connGeral, $strDebito, array());
                      
                      //Percorre os tipos de débitos associados ao espetáculo
                      while ($rs = fetchResult($queryDebito)) {
                        //Símbolo monetário do valor do débito, Percentual ou Real
                        $simbolo = ($rs["TipValor"] == "P") ? "%" : "R$";
                        if ($rs["CodTipBilhete"] != null) {
                          $valor = $rs["Valor"] / $qtdeSalas;
                          If ($rs["QtdeIngExcedidos"] > 0) {
                            $nomeDebBordero = $rs["DebBordero"] . " - QTDE. INGR.: " . $rs["QtdeIngExcedidos"];

                            // possivel solucao para a divisao de salas quando é ingresso excedido
                            $valor = $rs["Valor"];
                          } else {
                            continue;
                          }
                        } else {
                          $valor = $rs["Valor"];
                          $nomeDebBordero = $rs["DebBordero"];
                        }
                        $tipoValor = 0;
                        $tipoValor = $simbolo . " " . number_format($rs["PerDesconto"], 2, ",", ".");
                        $nTotalDesp += $valor;
                        $despesas[$rs["CodTipDebBordero"]]['nome'] = $nomeDebBordero;
                        $despesas[$rs["CodTipDebBordero"]]['tipoValor'] = $tipoValor;
                        $despesas[$rs["CodTipDebBordero"]]['valor'] += $valor;
                        $despesas[$rs["CodTipDebBordero"]]['valor_real'] += $rs["ValorReal"];
                        $despesas[$rs["CodTipDebBordero"]]['limite'] += $rs["VlMinimoDebBordero"];
                      }
                    } while ($rsApresentacoes = fetchResult($rsApresentacao));

                    if (!empty($forma_pagamento)) {
                      foreach ($forma_pagamento as $forma) {
                        $despesas[] = array(
                            'nome' => $forma['nome'],
                            'valor' => $forma['valor'],
                            'tipoValor' => ' - '
                        );

                        $nTotalDesp += $forma['valor'];
                      }
                    }                    
                    
                    // verificar se existe algum registro na tabForPagamento com StaTaxaCartoes = S
                    $qtdeRegistros = numRows($connBase, "select 1 from tabForPagamento where StaTaxaCartoes = 'S' and staforpagto = 'A'");

                    // caso positivo calcular e exibir a taxa dos cartoes
                    if ($qtdeRegistros > 0) {
                      $nTotalDesp += $taxaDosCartoes;
                    }


                    $nBrutoTot = 0;
                    $nTotLiqu = 0;

                    foreach ($despesas as $desp) {
                      if ($desp["limite"] > 0) {
                        $nTotalDesp -= $desp["valor"];

                        if ($desp["limite"] > $desp["valor_real"]) {
                          $nTotalDesp += $desp["limite"];
                          $desp["valor"] = $desp["limite"];
                        } else {
                          $nTotalDesp += $desp["valor_real"];
                          $desp["valor"] = $desp["valor_real"];
                        }
                      }
                      if ($resumido == "0") {
      ?>
                        <tr>
                          <td	align=left  class=texto><?php echo utf8_encode2($desp["nome"]); ?></td>
                          <td	align=right class=texto><?php echo ($desp["tipoValor"] == 0 && $desp["valor"] == 0) ? "" : $desp["tipoValor"]; ?></td>
                          <td	align=right class=texto><?php echo ($desp["valor"] == 0) ? "" : number_format($desp["valor"], 2, ",", "."); ?></td>
                        </tr>
      <?php
                      }
                    }

                    // caso positivo calcular e exibir a taxa dos cartoes
                    if ($qtdeRegistros > 0) {
      ?>
                    <tr>
                      <td	align=left  class=texto>TAXA DOS CARTÕES (DÉBITO E CRÉDITO)</td>
                      <td	align=right class=texto> - </td>
                      <td	align=right class=texto><?php echo number_format($taxaDosCartoes, 2, ",", "."); ?></td>
                    </tr>
      <?php
                    }
      ?>
                    <tr>
                      <td bgcolor="#FFFFFF" width="400" align="left" valign="top"  colspan="2"><font size="1" face="tahoma,verdana,arial">assinaturas dos responsáveis, <?php echo date("d/m/Y G:i:s"); ?></font></td>
                      <td bgcolor="LightGrey" width="256" style="font-size:9px; width: 256px;"  align="right" class="label"><b>TOTAL DESCONTOS</b></td>
                    </tr>
                    <tr>
                      <td bgcolor="#FFFFFF" width="400" colspan="2"><br/></td>
                      <td bgcolor="LightGrey" width="256" style="font-size:9px; width: 256px;" align="right" class="label"><b>R$&nbsp;<?php echo number_format($nTotalDesp, 2, ",", "."); ?></b></td>
                    </tr>
                    <tr>
                      <td bgcolor="#FFFFFF" width="400" colspan="2"><br/></td>
                      <td bgcolor="LightGrey" width="256" style="font-size:9px; width: 256px;" align="right" class="label"><br/></td>
                    </tr>
                    <tr>
                      <td bgcolor="#FFFFFF" width="400" colspan="2"></td>
                      <td bgcolor="LightGrey" width="256" style="font-size:9px; width: 256px;" align="right" class="label"><b>VENDAS - DESCONTOS</b></td>
                    </tr>
                    <tr>
                      <td bgcolor="#FFFFFF" width="400" colspan="2"></td>
                      <td bgcolor="LightGrey" width="256" style="font-size:9px; width: 256px;" align="right" class="label"><b>R$&nbsp;<?php echo number_format(($nTotalVendas - $nTotalDesp), 2, ",", "."); ?></b></td>
                    </tr>
                    <tr>
                      <td  bgcolor="#FFFFFF" width="400" colspan="2">
                        <table class="tabelaAuxiliar">
                          <tr>
                            <td class="linha_assinatura" >____________________</td>
                            <td class="linha_assinatura" >____________________</td>
                            <td class="linha_assinatura" >____________________</td>
                          </tr>
                          <tr>
                            <td align="center">BILHETERIA</td>
                            <td align="center">LOCAL</td>
                            <td align="center">PRODUÇÃO</td>
                          </tr>
                        </table>
                      </td>
                      <td bgcolor="LightGrey" align="right" class="label" valign="top"></td>
                    </tr>
                    <tr>
                      <td colspan="3" bgcolor="#FFFFFF" width="650">
                        <font size=1 face="tahoma,verdana,arial">
                          O Borderô de vendas assinados pelas partes envolvidas, dará a plena  quitação dos valores pagos em dinheiro no momento do fechamento,  portanto, confira atentamente os valores recebidos em dinheiro, vales/recibos de saques e comprovantes de depósito.
                          Os valores vendidos através dos cartões de crédito e débito serão  repassados aos favorecidos de acordo com os prazos firmados  através do contrato prestação de serviços assinado pelas partes.
                        </font>
                      </td>
                    </tr>
                  </table>
                  <br clear="all"/>

                  <table width="656" class="tabela" border="0" bgcolor="LightGrey">
                    <tr>
                      <td align="center" colspan="7"><font size=2 face="tahoma,verdana,arial"><b>3 - DETALHAMENTO POR FORMA DE PAGAMENTO<br/>(apenas para conferência de valores e quantidades)</b></font></td>
                    </tr>
                    <tr>
                      <td	align="left" width="190" class="titulogrid">Tipo de Forma de Pagamento</td>
                      <td	align="right" width="40" class="titulogrid">%</td>
                      <td	align="right" width="86" class="titulogrid">Qtde Transações</td>
                      <td	align="right" width="76" class="titulogrid">Valores Brutos</td>
                      <td	align="right" width="40" class="titulogrid">Taxa</td>
                      <td	align="right" width="78" class="titulogrid">Desconto Taxa</td>
                      <td	align="right" width="60" class="titulogrid">Repasses</td>
                      <td	align="right" width="86" class="titulogrid">Data do Repasse</td>
                    </tr>
      <?php
                    $strSqlDet = "SP_REL_BORDERO" . (($codSala == 'TODOS') ? '11' : '07') . " '" . $dataIni . "','" . $dataFim . "'," . $codPeca . "," . $codSala . ",'" . (($horSessao == "--") ? "null" : $horSessao) . "','" . $_SESSION["NomeBase"] . "'";
                    $queryDet = executeSQL($conn, $strSqlDet);
                    $paramsDet = array($dataIni, $dataFim, $codPeca, $codSala, (($horSessao == "--") ? "null" : $horSessao), "'" . $_SESSION["NomeBase"] . "'");
                    if (sqlErrors ()) {
                      echo $strSqlDet . "<br/>";
                      echo "Erro #004: <br/>";
                      die(print_r(sqlErrors()));
                    } else {
                      while ($pRSDetalhamento = fetchResult($queryDet)) {
      ?>
                        <tr>
                          <td	align=left  class=texto><?php echo utf8_encode2($pRSDetalhamento["forpagto"]); ?></td>
                          <td	align=right class=texto><?php echo number_format(($pRSDetalhamento["qtdBilh"] / $totTransacoes) * 100, 2, ",", "."); ?></td>
                          <td	align=right class=texto><?php echo $pRSDetalhamento["qtdBilh"]; ?></td>
                          <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSDetalhamento["totfat"], 2, ",", "."); ?></td>
                          <td	align=right class=texto><?php echo number_format($pRSDetalhamento["taxa"], 2, ",", "."); ?></td>
                          <td	align=right class=texto><?php echo number_format($pRSDetalhamento["descontos"], 2, ",", "."); ?></td>
                          <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSDetalhamento["liquido"], 2, ",", "."); ?></td>
        <?php
                        $dataRepasseTemp = explode("/", $DataFim2);
                        $dataRepasse = mktime(24 * $pRSDetalhamento["PrzRepasseDias"], 0, 0, $dataRepasseTemp["1"], $dataRepasseTemp["0"], $dataRepasseTemp["2"]) . "  " . $pRSDetalhamento["PrzRepasseDias"];
        ?>
                        <td	align=right class=texto><?php echo date("d/m/Y", $dataRepasse); ?></td>
                      </tr>
      <?php
                        $nQt += $pRSDetalhamento["qtdBilh"];
                        $nBrutoTot += $pRSDetalhamento["totfat"];
                        $nTotDesc += $pRSDetalhamento["descontos"];
                        $nTotLiqu += $pRSDetalhamento["liquido"];
                        $ntotPercentualTransacoes += ( $pRSDetalhamento["qtdBilh"] / $totTransacoes) * 100;
                      }
                      $totTransacoes = 0;
                    }
      ?>
                    <tr>
                      <td bgcolor="LightGrey" align="left" class="label"><b>TOTAL</b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b><?php echo number_format($ntotPercentualTransacoes, 0); ?>%</b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b><?php echo $nQt; ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b>R$&nbsp;&nbsp;<?php echo number_format($nBrutoTot, 2, ",", "."); ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b><?php echo number_format($nTotDesc, 2, ",", "."); ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b>R$&nbsp;<?php echo number_format($nTotLiqu, 2, ",", "."); ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b></b></td>
                    </tr>
                  </table>
                  <br clear="all"/>
    <?php
                    if ($_REQUEST['Small'] != '2') {

                      echo $table3;
    ?>
                      <table width=656 class="tabela" border="0" bgcolor="LightGrey">
                        <tr>
                          <td align="center" colspan="4"><font size=2 face="tahoma,verdana,arial"><b>4 - DETALHAMENTO POR CANAL</b></font></td>
                        </tr>
                        <tr>
                          <td	align="left" width="162" class="titulogrid">Canais</td>
                          <td	align="right" width="162" class="titulogrid">Qtde Transações</td>
                          <td	align="right" width="162" class="titulogrid">Total</td>
                          <td	align="right" width="163" class="titulogrid">% do Total de Transações</td>
                        </tr>
      <?php
                      $strSqlDet = "SP_REL_BORDERO" . (($codSala == 'TODOS') ? '12' : '09') . " '" . $dataIni . "','" . $dataFim . "'," . $codPeca . "," . $codSala . ",'" . (($horSessao == "--") ? "null" : $horSessao) . "','" . $_SESSION["NomeBase"] . "'";
                      $queryDet2 = executeSQL($conn, $strSqlDet);
                      $queryDet3 = executeSQL($conn, $strSqlDet);
                      $nQt = 0;
                      $nBrutoTot = 0;
                      $cont = 0;
                      if ($totPublico == 0) {
                        $totPublico = 1;
                      }

                      while ($pRSDet2 = fetchResult($queryDet3)) {
                        $totTransacoes += $pRSDet2["Quant"];
                      }

                      while ($pRSDet = fetchResult($queryDet2)) {
      ?>
                        <tr>
                          <td	align=left  class=texto><?php echo utf8_encode2($pRSDet["Venda"]); ?></td>
                          <td	align=right  class=texto><?php echo $pRSDet["Quant"]; ?></td>
                          <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSDet["Total"], 2, ",", "."); ?></td>
                          <td	align=right class=texto><?php echo number_format(($pRSDet["Quant"] / $totTransacoes) * 100, 2, ",", "."); ?>%</td>
                        </tr>
      <?php
                        $nQt = $nQt + $pRSDet["Quant"];
                        $nBrutoTot = $nBrutoTot + $pRSDet["Total"];
                        $cont = $cont + number_format(($pRSDet["Quant"] / $totTransacoes ) * 100, 2);
                      }
      ?>
                      <tr>
                        <td bgcolor="LightGrey" align="left" class="label"><b>TOTAL</b></td>
                        <td bgcolor="LightGrey" align="right" class="label"><b><?php echo $nQt; ?></b></td>
                        <td bgcolor="LightGrey" align="right" class="label"><b>R$&nbsp;&nbsp;<?php echo number_format($nBrutoTot, 2, ",", "."); ?></b></td>
                        <td bgcolor="LightGrey" align="right" class="label"><b><?php echo number_format($cont, 0); ?>%</b></td>
                      </tr>

                    </table>
                    <br clear="all"/>

    <?php } ?>
                    <table width="656" border=0>
                      <tr>
                        <td align="middle">
                          <br/>
                          <input class="botao" type="button" value="Imprimir Relatório" name="cmdImprimi" onClick="javascript:window.print();"/>
                          <input class="botao" type="button" value="Fechar Janela" name="cmdFecha" onClick="javascript:window.close()"/>
                          <input class="botao" type="button" value="Exportar Excel" name="cmdExportar" onClick="document.location.href = '<?php echo $var_url."&exportar=true"; ?>';"/>
                        </td>
                      </tr>
                    </table>
    <?php
                  }
                }
    ?>
  </body>
</html>