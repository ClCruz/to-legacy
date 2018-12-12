<?php
/**
 * Consulta dos Períodos de datas disponíveis das apresentações.
 *
 * @author Edicarlos Barbosa <edicarlos.barbosa@cc.com.br>
 * @version 1.0
 */
require("../settings/functions.php");
session_start();
$codPeca = ($_REQUEST["CodPeca"] == "") ? "null" : $_REQUEST["CodPeca"];
$conn = getConnection($_SESSION["IdBase"]);
$query = 'SELECT CONVERT(VARCHAR(10), MIN(DATAPRESENTACAO), 103) INICIAL,
		CONVERT(VARCHAR(10), MAX(DATAPRESENTACAO), 103) FINAL
		FROM TABAPRESENTACAO WHERE CODPECA = ?';
$params = array($codPeca);
$rs = executeSQL($conn, $query, $params, true);

if (empty($rs)) {
  die(json_encode(array('inicial' => '01/01/2005', 'final' => '')));
}

die(json_encode(array('inicial' => $rs['INICIAL'], 'fim' => $rs['FINAL'])));
?>
