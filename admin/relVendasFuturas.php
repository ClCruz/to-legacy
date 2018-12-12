<?php

list($usec, $sec) = explode(' ', microtime());
$script_start = (float) $sec + (float) $usec;

require_once("../settings/functions.php");
require_once('../settings/Template.class.php');
require_once('../settings/Utils.php');
require_once('../settings/Apresentacao.php');
session_start();

// Cria objeto do tipo Template para manipular estrutura HTML
$tpl = new Template("relVendasFuturas.html");
// Variáveis de referência a conexão do banco de dados
$conn_geral = getConnectionTsp();
$conn = getConnection($_SESSION["IdBase"]);
$conn_mw = mainConnection();
// Variaveis passadas por parametro pela url
$cod_apresentacao = $_GET["CodApresentacao"];
$codPeca = chk_value($_GET["CodPeca"]);
$codSala = chk_value($_GET["Sala"]);
$dataIni = chk_null($_GET["DataIni"]);
$dataFim = chk_null($_GET["DataFim"]);
$horSessao = chk_null($_GET["HorSessao"]);
$resumido = $_GET["Resumido"];

if (isset($_GET["imagem"]) && $_GET["imagem"] == "logo") {
  $strSql = "SP_REL_BORDERO_VENDAS;2 ?, ?, ?";
  $pRSBordero = executeSQL($conn_geral, $strSql, array($codPeca, $cod_apresentacao, "'" . $_SESSION["NomeBase"] . "'"));
  if (sqlErrors ())
    $err = "Erro #001 " . print_r(sqlErrors());
}

// Monta e executa query principal do relatÃ³rio
$params = array('Emerson', $codPeca, $codSala, tratarData($dataIni), tratarData($dataFim), '--', $_SESSION["NomeBase"]);
$strGeral = "SP_REL_BORDERO_VENDAS;";
if ($codSala == 'TODOS') {
  $strGeral .= "10 'Emerson'," . $codPeca . ",'" . $codSala . "','" . tratarData($dataIni) . "','" . tratarData($dataFim) . "','--','" . $_SESSION["NomeBase"] . "'";
} else {
  $strGeral .= "10 'Emerson'," . $codPeca . ",'" . $codSala . "','" . tratarData($dataIni) . "','" . tratarData($dataFim) . "','--','" . $_SESSION["NomeBase"] . "'";
}

$pRSGeral = executeSQL($conn_geral, $strGeral, array(), true);
if (sqlErrors ()) {
  $err = "Erro #002 <br>" . var_dump($params) . "<br>" . $strGeral . "<br>";
}

$array = explode(":", $pRSGeral["NomResPeca"]);
$PPArray = ($array[0] != "") ? $array[0] : "N&atilde;o Cadastrado";
$SPArray = ($array[1] != "") ? $array[1] : "N&atilde;o Cadastrado";
$TPArray = ($array[2] != "") ? $array[2] : "N&atilde;o Cadastrado";

if (isset($err) && $err != "") {
  echo $err . "<br>";
}

$strBordero = "SP_REL_BORDERO_VENDAS;14 'Emerson', " . $codPeca . "," . $codSala . ",'" . tratarData($dataIni) . "','" . tratarData($dataFim) . "','--','" . $_SESSION["NomeBase"] . "'";
$resultBordero = executeSQL($conn_geral, $strBordero, array());
if (hasRows($resultBordero)) {
  $numsArray = array();
  while ($rsBordero = fetchResult($resultBordero)) {
    $numsArray[] = $rsBordero['NumBordero'];
  }
  $pRSGeral['NumBordero'] = gerarNotacaoIntervalo($numsArray);
}

if (isset($err) && $err != "") {
  echo $err . "<br>";
  print_r(sqlErrors());
}

$tpl->evento = utf8_encode2($pRSGeral["NomPeca"]);
$tpl->numBordero = $pRSGeral["NumBordero"];
$tpl->responsavel = utf8_encode2($PPArray);
$tpl->cpfCnpj = $SPArray;
$tpl->dtInicio = $dataIni;
$tpl->dtFim = $dataFim;
$tpl->endereco = utf8_encode2($TPArray);

// Obtem o nome da sala
if ($codSala != "TODOS") {
  $querySala = "SELECT NOMSALA FROM TABSALA WHERE CODSALA = ?";
  $rsSala = executeSQL($conn, $querySala, array($codSala), true);
  $nome_sala = $rsSala["NOMSALA"];
} else {
  $nome_sala = "TODOS";
}

$tpl->local = utf8_encode2($nome_sala);
$tpl->lugares = $pRSGeral["Lugares"];

// Obtem os dados dos canais de vendas
$codSala = ($codSala == "TODOS") ? 'NULL' : $codSala;
$query = "SP_VEN_CON014 ";
$query .= $codSala . "," . $codPeca . ",'" . tratarData($dataIni) . " 00:01:00','" . tratarData($dataFim) . " 23:59:00'";
$result = executeSQL($conn, $query, array());

// Contrói a lista de canal de venda
$canais = array();
$lista_apresentacoes = array();
while ($apresentacao = fetchResult($result)) {
  $apr = new Apresentacao($apresentacao["CANAL_VENDA"], $apresentacao["QTDE"],
          $apresentacao["PAGTO"], $apresentacao["DATA_APRESENTACAO"], $apresentacao["HORSESSAO"]);
  $lista_apresentacoes[] = $apr;
  $canais[] = $apresentacao["CANAL_VENDA"];
}
// Filtra os canais de venda para ter apenas nomes únicos
$canais = array_unique($canais);
// Ordena os canais de vendas
sort($canais);

foreach ($canais as $key => $value) {
  $tpl->canal = utf8_encode2($value);
  $tpl->parseBlock("BLOCK_CANAL", true);
  $tpl->parseBlock("BLOCK_HEADER_CANAL", true);
}

$rs_apresentacao = executeSQL($conn, $query, array());

$total_apresentacao = 0;
$apre_data_hora = "";
while ($total = fetchResult($rs_apresentacao)) {
  if (strcmp($apre_data_hora, $total["DATA_APRESENTACAO"] . $total["HORSESSAO"]) == 0) {
    continue;
  }
  $apre_data_hora = $total["DATA_APRESENTACAO"] . $total["HORSESSAO"];
  $tpl->data = textToDate($total["DATA_APRESENTACAO"]);
  $tpl->hora = $total["HORSESSAO"];

  $total_qtde = 0;
  $total_valor = 0;
  foreach ($canais as $key => $value) {
    $valores = search_value_presentation($lista_apresentacoes, $apre_data_hora, $value);
    $result_qtde = $valores[0];
    $result_valor = $valores[1];
    $tpl->qtde = $result_qtde;
    $tpl->valor = formatarValorNumerico($result_valor);
    $total_qtde += $result_qtde;
    $total_valor += $result_valor;
    $tpl->parseBlock("BLOCK_ITENS", true);
  }

  $tpl->total_qtde = $total_qtde;
  $tpl->total_valor = formatarValorNumerico($total_valor);
  $tpl->parseBlock("BLOCK_TOTAL", true);
  $tpl->parseBlock("BLOCK_APRESENTACAO", true);
  $total_apresentacao++;
}

$query = "SP_VEN_CON015 ";
$query .= $codSala . "," . $codPeca . ",'" . tratarData($dataIni) . " 00:01:00','" . tratarData($dataFim) . " 23:59:00'";
$rs_total_geral = executeSQL($conn, $query, array());
$total_qtde_geral = 0;
$total_valor_geral = 0;

while ($total = fetchResult($rs_total_geral)) {
  $total_qtde_geral += $total["QTDE"];
  $total_valor_geral += $total["PAGTO"];
  $tpl->total_qtde_geral = $total["QTDE"];
  $tpl->total_valor_geral = formatarValorNumerico($total["PAGTO"]);
  $tpl->parseBlock("BLOCK_TOTAL_GERAL", true);
}
$tpl->total_qtde_geral_final = $total_qtde_geral;
$tpl->total_valor_geral_final = formatarValorNumerico($total_valor_geral);
$tpl->total_apresentacao = $total_apresentacao;

list($usec, $sec) = explode(' ', microtime());
$script_end = (float) $sec + (float) $usec;
$elapsed_time = round($script_end - $script_start, 5);

//echo 'Elapsed time: ', $elapsed_time, ' secs. Memory usage: ', round(((memory_get_peak_usage(true) / 1024) / 1024), 2), 'Mb';

$tpl->show();
?>