<?php

/**
 * Consulta de Eventos para combos gerais
 *
 * @author Edicarlos Barbosa <edicarlos.barbosa@cc.com.br>
 * @version 1.0
 */
require("../settings/functions.php");

$mainConnection = mainConnection();
session_start();

$strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = " . $_POST["NomeBase"];
$stmt = executeSQL($mainConnection, $strQuery, array(), true);

$conn = getConnection($_POST["NomeBase"]);
$query = "EXEC " . $stmt["DS_NOME_BASE_SQL"] . ".." . $_POST['Proc'] . " " . $_SESSION['admin'] . ", " . $_POST["NomeBase"];
$result = executeSQL($conn, $query);

// Cria sessao com nome da base utilizada
$_SESSION["IdBase"] = $_POST["NomeBase"];
$_SESSION["NomeBase"] = $stmt["DS_NOME_BASE_SQL"];
$retorno = "<option value=''>Selecione...</option>";
if (hasRows($result)) {
  while ($rs = fetchResult($result)) {
    $retorno .= "<option value=\"" . $rs["CodPeca"] . "\">" . utf8_encode2($rs["nomPeca"]) . "</option>\n";
  }
} else {
  $retorno = "<option value=\"-1\">NENHUM EVENTO DISPONÍVEL</option>";
}
echo $retorno;
?>