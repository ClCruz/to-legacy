<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
if(isset($_GET["exportar"]) && $_GET["exportar"] == "true"){
  header("Content-type: application/vnd.ms-excel");
  header("Content-type: application/force-download");
  header("Content-Disposition: attachment; filename=relatorio.xls");
  header("Pragma: no-cache");
}

require_once("../settings/functions.php");
require_once("../settings/Utils.php");

$mainConnection = mainConnection();
$connGeral = getConnectionTsp();
session_start();

$rs = executeSQL($mainConnection, 'SELECT ID_BASE FROM MW_BASE WHERE DS_NOME_BASE_SQL = ?', array($_SESSION["NomeBase"]), true);
$connBase = getConnection($rs["ID_BASE"]);

// Variaveis passadas por parametro pela url
$CodApresentacao = $_GET["CodApresentacao"];
$CodPeca = (isset($_GET["CodPeca"]) && !empty($_GET["CodPeca"])) ? $_GET["CodPeca"] : "";
$CodSala = (isset($_GET["Sala"]) && !empty($_GET["Sala"])) ? $_GET["Sala"] : "";
$DataIni = (isset($_GET["DataIni"]) && !empty($_GET["DataIni"])) ? $_GET["DataIni"] : "null";
$DataFim = (isset($_GET["DataFim"]) && !empty($_GET["DataFim"])) ? $_GET["DataFim"] : "null";
$HorSessao = (isset($_GET["HorSessao"]) && !empty($_GET["HorSessao"])) ? $_GET["HorSessao"] : "null";
$Resumido = $_GET["Resumido"];
$var_url   = "relBorderoVendasLiquidas.php?CodPeca=".$_GET["CodPeca"]."&logo=imagem&Resumido=".$_GET["Resumido"]."&Small=".$_GET['Small']."&DataIni=".$_GET["DataIni"]."&DataFim=".$_GET["DataFim"]."&HorSessao=".$_GET["HorSessao"]."&Sala=".$_GET["Sala"];

$queryBase = "SELECT ds_local_evento DS_NOME_TEATRO FROM tabpeca tp INNER JOIN ci_middleway..mw_local_evento le ON le.id_local_evento = tp.id_local_evento WHERE tp.CodPeca = ?";
$nomeBase = executeSQL($connBase, $queryBase, array($_GET["CodPeca"]), true);

if (empty($nomeBase)) {
  $queryBase = "SELECT DISTINCT DS_NOME_TEATRO FROM MW_BASE WHERE DS_NOME_BASE_SQL = ?";
  $nomeBase = executeSQL($mainConnection, $queryBase, array($_SESSION["NomeBase"]), true);
}

if (isset($_GET["imagem"]) && $_GET["imagem"] == "logo") {
  $strSql = "SP_REL_BORDERO_VENDAS;2 ?, ?, ?";
  $pRSBordero = executeSQL($connGeral, $strSql, array($CodPeca, $CodApresentacao, "'" . $_SESSION["NomeBase"] . "'"));
  if (sqlErrors ())
    $err = "Erro #001 " . print_r(sqlErrors());
}

// Monta e executa query principal do relatório
$strGeral = "SP_REL_BORDERO_VENDAS;" . (($CodSala == 'TODOS') ? '10' : '1') . " 'Emerson', " . $CodPeca . "," . $CodSala . "," . $DataIni . "," . $DataFim . ",'" . (($_GET['Small'] == '1') ? '--' : $HorSessao) . "','" . $_SESSION["NomeBase"] . "'";

$pRSGeral = executeSQL($connGeral, $strGeral, array(), true);
if (sqlErrors ())
  $err = "Erro #002 <br>" . var_dump($paramsGeral) . "<br>" . $strGeral . "<br>";

$array = explode(":", $pRSGeral["NomResPeca"]);
$PPArray = ($array[0] != "") ? $array[0] : "Não Cadastrado";
$SPArray = ($array[1] != "") ? $array[1] : "Não Cadastrado";
$TPArray = ($array[2] != "") ? $array[2] : "Não Cadastrado";

if (isset($err) && $err != "") {
  echo $err . "<br>";
  print_r(sqlErrors());
}

if ($_GET['Small'] == '1') {
  $strBordero = "SP_REL_BORDERO_VENDAS;14 'Emerson', " . $CodPeca . "," . $CodSala . "," . $DataIni . "," . $DataFim . ",'--','" . $_SESSION["NomeBase"] . "'";
  $resultBordero = executeSQL($connGeral, $strBordero, array());

  if (hasRows($resultBordero)) {
    $numsArray = array();
    while ($rsBordero = fetchResult($resultBordero)) {
      $numsArray[] = $rsBordero['NumBordero'];
    }
    $pRSGeral['NumBordero'] = gerarNotacaoIntervalo($numsArray);
  }
}

$imagem = getSalaImg($CodSala, $connBase);

if (isset($err) && $err != "") {
  echo $err . "<br>";
  print_r(sqlErrors());
}
?>
<html>
  <title>Borderô - Fechamento em Dinheiro</title>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  </head>
    <link rel="stylesheet" type="text/css" href="../stylesheets/estilos_ra.css">
    <link rel="stylesheet" type="text/css" href="../stylesheets/padraoRelat.CSS">
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
                  <img alt="" align="left" border="0" src="<?php echo multiSite_getLogoFullURI(); ?>" />
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
        <td width="300" class="tabela" align="center" bgcolor="LightGrey"><b><font size=2 face="tahoma,verdana,arial">Borderô - Fechamento em Dinheiro</font><br/>Contabilização dos Ingressos</b></td>
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
              $DataIni2 = substr($DataIni, -2, 2) . '/' . substr($DataIni, -4, 2) . '/' . substr($DataIni, 0, 4);
              $DataFim2 = substr($DataFim, -2, 2) . '/' . substr($DataFim, -4, 2) . '/' . substr($DataFim, 0, 4);
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
    <br>

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

              $query = executeSQL($connGeral, $strGeral, $paramsGeral);
              while ($pRSBordero = fetchResult($query)) {
                $nPag = 1;
                $nLin = 0;
                $totNVendidos = $totNVendidos + ($pRSBordero["Lugares"] - $pRSBordero["PubTotal"]);
                $totNPagantes = $totNPagantes + ($pRSBordero["PubTotal"] - $pRSBordero["Pagantes"]);
                $totPagantes = $totPagantes + $pRSBordero["Pagantes"];
                $totPublico = $totPublico + $pRSBordero["PubTotal"];
                if ($Resumido == 0) {
    ?>
                  <table width="656" class="tabela" border="0">
                    <tr>
                      <td align=center width="162" class="tabela"><font size=1 face="tahoma,verdana,arial"><b>Ingressos Não Vendidos:</b>&nbsp;&nbsp;&nbsp;<?php echo $totNVendidos; ?></font></td>
                      <td align=center width="162" class="tabela"><font size=1 face="tahoma,verdana,arial"><b>Público Não Pagante:</b>&nbsp;&nbsp;&nbsp;<?php echo $totNPagantes; ?></font></td>
                      <td align=center width="162" class="tabela"><font size=1 face="tahoma,verdana,arial"><b>Público Pagante:</b>&nbsp;&nbsp;&nbsp;<?php echo $totPagantes; ?></font></td>
                      <td align=center width="163" class="tabela"><font size=1 face="tahoma,verdana,arial"><b>Público Total:</b>&nbsp;&nbsp;&nbsp;<?php echo $totPublico; ?></font></td>
                    </tr>
                  </table>
                  <br>
                  <table width="656" class="tabela" border="0" bgcolor="LightGrey">
                    <tr>
                      <td align="center" colspan="6"><font size="2" face="tahoma,verdana,arial"><B>1 - RECEITAS DO BORDERÔ</B></font></td>
                    </tr>
                    <tr>
                      <td	align="left" width="104" class="titulogrid">Setor</td>
                      <td	align="left" width="240" class="titulogrid">Tipo de Ingressos</td>
                      <td	align="right" width="104" class="titulogrid">Qtde Estornados</td>
                      <td	align="right" width="104" class="titulogrid">Qtde Vendidos</td>
                      <td	align="right" width="104" class="titulogrid">Preço</td>
                      <td	align="right" width="104" class="titulogrid">Sub Total</td>
                    </tr>
      <?php
                  $strSqlBilhete = ($CodSala == 'TODOS') ? "SP_REL_BORDERO_VENDAS_2;5 '" . $DataIni . "','" . $DataFim . "'," . $CodPeca . ",'" . $HorSessao . "','" . $_SESSION["NomeBase"] . "'" : "SP_REL_BORDERO_VENDAS_2;4 " . $pRSBordero["CodApresentacao"] . ",'" . $_SESSION["NomeBase"] . "'";
                  $queryBilhete = executeSQL($connGeral, $strSqlBilhete);
                  if (sqlErrors ()) {
                    echo "Erro #003: ";
                    print_r(sqlErrors());
                    print_r($strSqlBilhete);
                  }
                  while ($pRSBilhete = fetchResult($queryBilhete)) {
                    if ($Resumido == "0") {
                      $strIngExc = "SP_CON_INGRESSO_EXCEDIDO ?, ?";
                      $paramIngExc = array($pRSBilhete["CodTipBilhete"], $_SESSION["NomeBase"]);
                      $strIngExc = logQuery($strIngExc, $paramIngExc);
                      $rsIngExc = executeSQL($connGeral, $strIngExc, array());
                      if (hasRows($rsIngExc)) {
                        $qtdIngressosExcedidos += $pRSBilhete["QtdeVendidos"];
                      }
      ?>
                      <tr>
                        <td	align=left class=texto><?php echo formatarConteudoVazio(utf8_encode2($pRSBilhete["NomSetor"])); ?></td>
                        <td	align=left  class=texto><?php echo formatarConteudoVazio(utf8_encode2($pRSBilhete["TipBilhete"])); ?></td>
                        <td	align=right  class=texto><?php echo formatarConteudoVazio($pRSBilhete["QtdeEstornados"]); ?></td>
                        <td	align=right  class=texto><?php echo formatarConteudoVazio($pRSBilhete["QtdeVendidos"]); ?></td>
                        <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSBilhete["Preco"], 2, ",", "."); ?></td>
                        <td	align=right class=texto >R$&nbsp;<?php echo number_format($pRSBilhete["Total"], 2, ",", "."); ?></td>
                      </tr>
      <?php
                    }
                    $nTotalVendas = $nTotalVendas + $pRSBilhete["Total"];
                    $ingressosExcedentes[] = $pRSBilhete["CodTipBilhete"];
                  }
                  if ($Resumido == "0") {
      ?>
                    <tr>
                      <td colspan="3" bgcolor="#FFFFFF" rowspan="2" align="center" class="tabela"><font size=2 face="tahoma,verdana,arial"><b>Taxa de Ocupação:</b>&nbsp;&nbsp;  <?php echo number_format((($totPublico / $lotacao) * 100), 2, ",", "."); ?> %</font></td>
                      <td bgcolor="LightGrey" colspan="2" align="center" class="label"><b>TOTAL DE VENDAS BRUTO</b></td>
                    </tr>
                    <tr>
                      <td bgcolor="LightGrey" colspan="2" align="right" class="label"><b>R$&nbsp;&nbsp;<?php echo number_format($nTotalVendas, 2, ",", "."); ?></b></td>
                    </tr>
                  </table>
                  <br clear=all>
    <?php ob_start(); ?>
                    <table width="656" class="tabela" border="0" bgcolor="LightGrey">
                      <tr>
                        <td align="center" colspan="5"><font size=2 face="tahoma,verdana,arial"><B>3 - DETALHAMENTO POR FORMA DE PAGAMENTO<BR>(apenas para conferência de valores e quantidades)</B></font></td>
                      </tr>
                      <tr>
                        <td	align="left" width="140" class="titulogrid">Tipo de Forma de Pagamento</td>
                        <td	align="right" width="104" class="titulogrid">Qtde Transações</td>
                        <td	align="right" width="104" class="titulogrid">Valores Brutos</td>
                        <td	align="right" width="104" class="titulogrid">Repasses</td>
        <?php if ($_GET['Small'] == 1) {
        ?>
                      <td	align="right" width="100" class="titulogrid">Pagamento em</td>
        <?php } ?>
                  </tr>
      <?php
                    $strSqlDet = "SP_REL_BORDERO_VENDAS_2;" . (($CodSala == 'TODOS') ? '2' : '3') . " '" . $DataIni . "','" . $DataFim . "'," . $CodPeca . "," . $CodSala . ",'" . $HorSessao . "','" . $_SESSION["NomeBase"] . "'";
                    $queryDet = executeSQL($connGeral, $strSqlDet);

                    $paramsDet = array($DataIni, $DataFim, $CodPeca, $CodSala, $HorSessao, "'" . $_SESSION["NomeBase"] . "'");

                    if (sqlErrors ()) {
                      echo $strSqlDet . "<br>";
                      print_r($paramsDet);
                      echo "Erro #004: <br>";
                      die(print_r(sqlErrors()));
                    } else {
                      $forma_pagamento = array();

                      while ($pRSDetalhamento = fetchResult($queryDet)) {

                        $forma_pagamento[] = array('nome' => $pRSDetalhamento["forpagto"], 'valor' => $pRSDetalhamento["liquido"]);
      ?>
                        <tr>
                          <td	align=left  class=texto><?php echo utf8_encode2($pRSDetalhamento["forpagto"]); ?></td>
                          <td	align=right class=texto><?php echo $pRSDetalhamento["qtdBilh"]; ?></td>
                          <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSDetalhamento["totfat"], 2, ",", "."); ?></td>
                          <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSDetalhamento["liquido"], 2, ",", "."); ?></td>
        <?php
                        if ($_GET['Small'] == 1) {
                          $dataRepasseTemp = explode("/", $DataFim2);
                          $dataRepasse = mktime(24 * $pRSDetalhamento["PrzRepasseDias"], 0, 0, $dataRepasseTemp["1"], $dataRepasseTemp["0"], $dataRepasseTemp["2"]) . "  " . $pRSDetalhamento["PrzRepasseDias"];
        ?>
                          <td	align=right class=texto><?php echo date("d/m/Y", $dataRepasse); ?></td>
        <?php } ?>
                      </tr>
      <?php
                        $nQt += $pRSDetalhamento["qtdBilh"];
                        $nBrutoTot += $pRSDetalhamento["totfat"];
                        $nTotDesc += $pRSDetalhamento["Descontos"];
                        $nTotLiqu += $pRSDetalhamento["liquido"];
                      }
                    }
      ?>
                  </table>
                  <br clear=all>
    <?php $table3 = ob_get_clean(); ?>
                    <table width=656 class="tabela" border="0" bgcolor="LightGrey">
                      <tr>
                        <td align="center" colspan="5"><font size=2 face="tahoma,verdana,arial"><B>2 - DESPESAS DO BORDERÔ</B></font></td>
                      </tr>
                      <tr>
                        <td	align="left" width="219" class="titulogrid">Tipo de Débito</td>
                        <td	align="right" width="219" class="titulogrid">% ou R$ Fixo</td>
                        <td	align="right" width="219" class="titulogrid">Valor</td>
                      </tr>
      <?php
                  }
                  $nTotalDesp = 0;
                  $nLin = $nLin + 4;

                  if ($CodSala == 'TODOS') {
                    $gSQL = "select CodApresentacao from " . $_SESSION["NomeBase"] . "..tabapresentacao where
							    datapresentacao between ? and ?
							    and codpeca = ?" . (($_GET['Small'] != '1') ? ' and horsessao = ?' : '');
                    $paramsApresentacoes = (($_GET['Small'] == '1') ? array($DataIni, $DataFim, $CodPeca) : array($DataIni, $DataFim, $CodPeca, $HorSessao));

                    $resultApresentacoes = executeSQL($connGeral, $gSQL, $paramsApresentacoes);
                    $qtdeSalas = numRows($connGeral, $gSQL, $paramsApresentacoes);
                    $rsApresentacao = fetchResult($resultApresentacoes);
                  }

                  $despesas = array();

                  $listaIngExcedentes = "";
                  $ingressosExcedentes = array_unique($ingressosExcedentes);
                  foreach ($ingressosExcedentes as $key => $value) {
                    $listaIngExcedentes .= $value . ",";
                  }
                  $listaIngExcedentes = substr(trim($listaIngExcedentes), 0, strlen(trim($listaIngExcedentes)) - 1);


                  $taxaDosCartoes = $nBrutoTot - $nTotLiqu;
                  $taxaDosCartoesPorSala = ($taxaDosCartoes > 0) ? $taxaDosCartoes / $qtdeSalas : $taxaDosCartoes;

                  do {
                    //Obtem os débitos do borderô da tabela tabDebBordero
                    $strDebito = "SP_REL_BORDERO_COMPLETO ?, ?, ?, ?, ?, ?";
                    //Define os parâmetros para a consulta dos débitos
                    if ($CodSala == 'TODOS') {
                      //Parâmetros p/ quando selecionado Todas as Apresentações
                      $paramDebito = array($CodPeca,
                          $rsApresentacao["CodApresentacao"],
                          $DataIni,
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
                    $queryDebito = executeSQL($connBase, $strDebito, array());

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
                  } while ($rsApresentacao = fetchResult($resultApresentacoes));

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

                  //$taxaDosCartoes = $nBrutoTot - $nTotLiqu;

                    
                  // verificar se existe algum registro na tabForPagamento com StaTaxaCartoes = S
                  $qtdeRegistros = numRows($connBase, "select 1 from tabForPagamento where StaTaxaCartoes = 'S' and staforpagto = 'A'");

                  // caso positivo calcular e exibir a taxa dos cartoes
                  if ($qtdeRegistros > 0) {
                    $nTotalDesp += $taxaDosCartoes;
                  }

                  foreach ($despesas as $desp) {
                    /*
                      https://portal.cc.com.br:8084/projetos/ticket/191

                      colunas VlMinimoDebBordero e Valor as ValorReal adicionadas
                      as colunas de retorno devido a um problema com os calculos de
                      limite com multiplas apresentacoes representando uma mesma apresentacao, por exemplo:

                      0- O TOTAL DE VENDAS BRUTO foi de R$ 10.800,00.
                      1- Foi criado uma apresentação em duas salas (do mesmo dia e hora), ou
                      seja, foi gerado duas apresentações.
                      2- Criou o valor mínimo do débito do borderô de R$ 750,00 no módulo
                      Administrativo (só que na descrição do débito informada que o mínimo é de
                      R$ 1.500,00).
                      3- Em uma sala (apresentação) o valor da venda apurado foi de R$ 700,00,
                      sendo assim o sistema assumiu o valor mínimo cadastrado de R$ 750,00.
                      4- Na outra sala (apresentação) o valor apurado foi de R$ 1.460,00,
                      superando o mínimo de R$ 750,00, portanto assumindo o R$ 1.460,00.
                      5- Desta forma o sistema somou R$ 1.460,00 + R$ 750,00 = R$ 2.210,00.

                      O sistema deverá calcular o valor do mínimo a ser cobrado do teatro depois
                      que somar os valores das vendas de cada sala, ou seja, se o valor total
                      das salas superar o mínimo deverá ser calculado o percentual cadastrado no
                      sistema.
                      No exemplo acima, o valor que deveria ser cobrado seria de R$ 2.160,00
                      (1460,00+700,00) e não R$ 2.210,00 (1460,00+750,00).
                     */
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
                    if ($Resumido == "0") {
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
                    <td bgcolor="#FFFFFF" align="left" valign="top" rowspan="3" colspan="2"><font size=1 face="tahoma,verdana,arial">assinaturas dos responsáveis, <?php echo date("d/m/Y G:i:s"); ?></font></td>
                    <td bgcolor="LightGrey" colspan="2" align="center" class="label"><b>TOTAL DE DESPESAS</b></td>
                  </tr>
                  <tr>
                    <td align="right" bgcolor="LightGrey" class="label">R$&nbsp;&nbsp;&nbsp;<?php echo number_format($nTotalDesp, 2, ",", "."); ?><br>
                      <br>
                    </td>
                  </tr>
                  <tr>
                    <td bgcolor="LightGrey" align="center" class="label"><b>RECEITA LÍQUIDA</b></td>
                  </tr>
                  <tr>
                    <td width="440" bgcolor="#FFFFFF" colspan="2">
                      <table border="0">
                        <tr>
                          <td class="linha_assinatura" width="200">_______________________</td>
                          <td class="linha_assinatura" width="200">_______________________</td>
                          <td class="linha_assinatura" width="200">_______________________</td>
                        </tr>
                        <tr>
                          <td align="center">BILHETERIA</td>
                          <td align="center">LOCAL</td>
                          <td align="center">PRODUÇÃO</td>
                        </tr>
                      </table>
                    </td>
                    <td bgcolor="LightGrey" align="right" class="label" valign="top"><b>R$&nbsp;&nbsp;&nbsp;<?php echo number_format(($nTotalVendas - $nTotalDesp), 2, ",", "."); ?></b></td>
                  </tr>
                  <tr>
                    <td colspan="4" bgcolor="#FFFFFF" width="650"><font size=1 face="tahoma,verdana,arial">O Borderô de vendas assinados pelas partes envolvidas, dará a plena  quitação dos valores pagos em dinheiro no momento do fechamento,  portanto, confira atentamente os valores recebidos em dinheiro, vales/recibos de saques e comprovantes de depósito.<br>    			    			Os valores vendidos através dos cartões de crédito e débito serão  repassados aos favorecidos de acordo com os prazos firmados  através do contrato prestação de serviços assinado pelas partes.</font>
                    </td>
                  </tr>
                </table>
                <br clear=all>
    <?php
                  if ($_REQUEST['Small'] != '2') {
                    /*
                      echo $table3; ?>
                      <table width=656 class="tabela" border="0" bgcolor="LightGrey">
                      <tr>
                      <td align="center" colspan="4"><font size=2 face="tahoma,verdana,arial"><B>4 - ESTATÍSTICA POR CANAL DE VENDA</B></font></td>
                      </tr>
                      <tr>
                      <td	align="left" width="162" class="titulogrid">Canais de Venda</td>
                      <td	align="right" width="162" class="titulogrid">Qtde Ingressos</td>
                      <td	align="right" width="162" class="titulogrid">Total</td>
                      <td	align="right" width="163" class="titulogrid">% do Total de Ingressos</td>
                      </tr>
                      <?php
                      $strSqlDet = "SP_REL_BORDERO_VENDAS;" . (($CodSala == 'TODOS') ? '12' : '9') . " '" . $DataIni . "','" . $DataFim . "'," . $CodPeca . "," . $CodSala . ",'" . $HorSessao . "','" . $_SESSION["NomeBase"] . "'";
                      $queryDet2 = executeSQL($connGeral, $strSqlDet);
                      $nQt = 0;
                      $nBrutoTot = 0;
                      $cont = 0;
                      if ($totPublico == 0)
                      $totPublico = 1;

                      while ($pRSDet = fetchResult($queryDet2)) {
                      ?>
                      <tr>
                      <td	align=left  class=texto><?php echo utf8_encode2($pRSDet["Venda"]); ?></td>
                      <td	align=right  class=texto><?php echo $pRSDet["Quant"]; ?></td>
                      <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSDet["Total"], 2, ",", "."); ?></td>
                      <td	align=right class=texto><?php echo number_format(($pRSDet["Quant"] / $totPagantes) * 100, 2, ",", "."); ?>%</td>
                      </tr>
                      <?php
                      $nQt = $nQt + $pRSDet["Quant"];
                      $nBrutoTot = $nBrutoTot + $pRSDet["Total"];
                      $cont = $cont + number_format(($pRSDet["Quant"] / $totPagantes ) * 100, 2);
                      }
                      ?>
                      <tr>
                      <td bgcolor="LightGrey" align="left" class="label"><b>TOTAL DE VENDAS</b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b><?php echo $nQt; ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b>R$&nbsp;&nbsp;<?php echo number_format($nBrutoTot, 2, ",", "."); ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b><?php echo number_format($cont, 0); ?>%</b></td>
                      </tr>

                      </table>
                      <br clear=all>

                      <?php */
                    /* <table width=656 class="tabela" border="0" bgcolor="LightGrey">
                      <tr>
                      <td align="center" colspan="4"><font size=2 face="tahoma,verdana,arial"><B>5 - ESTATÍSTICA POR PONTO DE VENDA</B></font></td>
                      </tr>
                      <tr>
                      <td	align="left" width="162" class="titulogrid">Ponto de Venda</td>
                      <td	align="right" width="162" class="titulogrid">Qtde Ingressos</td>
                      <td	align="right" width="162" class="titulogrid">Total</td>
                      <td	align="right" width="163" class="titulogrid">% do Total de Ingressos</td>
                      </tr>
                      <?php
                      $strSqlDet = "SP_REL_BORDERO_VENDAS;8 '" . $DataIni . "','" . $DataFim . "'," . $CodPeca . "," . $CodSala . ",'" . $HorSessao . "','" . $_SESSION["NomeBase"] . "'";
                      $queryDet2 = executeSQL($connGeral, $strSqlDet);
                      $nQt = 0;
                      $nBrutoTot = 0;
                      $cont = 0;
                      if ($totPublico == 0)
                      $totPublico = 1;

                      while ($pRSDet = fetchResult($queryDet2)) {
                      ?>
                      <tr>
                      <td	align=left  class=texto><?php echo utf8_encode2($pRSDet["Venda"]); ?></td>
                      <td	align=right  class=texto><?php echo $pRSDet["Quant"]; ?></td>
                      <td	align=right class=texto>R$&nbsp;<?php echo number_format($pRSDet["Total"], 2, ",", "."); ?></td>
                      <td	align=right class=texto><?php echo number_format(($pRSDet["Quant"] / $totPagantes) * 100, 2, ",", "."); ?>%</td>
                      </tr>
                      <?php
                      $nQt = $nQt + $pRSDet["Quant"];
                      $nBrutoTot = $nBrutoTot + $pRSDet["Total"];
                      $cont = $cont + number_format(($pRSDet["Quant"] / $totPagantes ) * 100, 2);
                      }
                      ?>
                      <tr>
                      <td bgcolor="LightGrey" align="left" class="label"><b>TOTAL DE VENDAS</b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b><?php echo $nQt; ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b>R$&nbsp;&nbsp;<?php echo number_format($nBrutoTot, 2, ",", "."); ?></b></td>
                      <td bgcolor="LightGrey" align="right" class="label"><b><?php echo number_format($cont, 0); ?>%</b></td>
                      </tr>

                      </table>
                      <br clear=all>
                     */
                  }
    ?>
                  <table width="656" border=0>
                    <tr>
                      <td align="middle">
                        <br>
                        <input class="botao" type="button" value="Imprimir Relatório" name="cmdImprimi" onClick="javascript:window.print();">
                        <input class="botao" type="button" value="Fechar Janela" name="cmdFecha" onClick="javascript:window.close()">
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