<?php

/**
 * Consulta de Setores para combos gerais
 *
 * @author Edicarlos Barbosa <edicarlos.barbosa@cc.com.br>
 * @version 1.0
 */
require("../settings/functions.php");
session_start();

$codPeca = ($_REQUEST["CodPeca"] == "") ? "null" : $_REQUEST["CodPeca"];
$query = "SELECT DISTINCT TS.CODSALA, TS.NOMSALA FROM TABAPRESENTACAO TA INNER JOIN TABSALA TS ON TS.CODSALA = TA.CODSALA WHERE TA.CODPECA = ?";
$conn = getConnection($_SESSION["IdBase"]);
$rsSetores = executeSQL($conn, $query, array($codPeca));

if (!sqlErrors()) {
  if (hasRows($rsSetores)) {
    $retorno .= "<option value='TODOS'>&lt; TODOS &gt;</option>";
    while ($setor = fetchResult($rsSetores)) {
      $retorno .= "<option value=\"" . $setor["CODSALA"] . "\">" . utf8_encode2($setor["NOMSALA"]) . "</option>\n";
    }
  } else {
    $retorno = "<option value=\"-1\">NENHUM SETOR DISPONÍVEL</option>";
  }
} else {
  print_r(sqlErrors());
  $retorno = "<option value=\"-1\">NENHUM SETOR DISPONÍVEL</option>";
}
echo $retorno;
?>