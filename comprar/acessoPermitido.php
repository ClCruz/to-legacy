<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');

session_start();

if(isset($_SESSION['usuario_pdv']) and $_SESSION['usuario_pdv'] == 1){
	
	$mainConnection = mainConnection();

    $query = "SELECT (
					SELECT COUNT(1) FROM MW_RESERVA R
					INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
					INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
					INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
					INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE AND AC.CODPECA = E.CODPECA
					WHERE AC.ID_USUARIO = ? AND R.ID_SESSION = ?
				) AS ITENS_COM_PERMISSAO,
				(
					SELECT COUNT(1) FROM MW_RESERVA R
					WHERE R.ID_SESSION = ?
				) AS ITENS_NO_TOTAL";
	$params = array($_SESSION['operador'], session_id(), session_id());
	$rs = executeSQL($mainConnection, $query, $params, true);

	if ($rs['ITENS_NO_TOTAL'] != $rs['ITENS_COM_PERMISSAO']) {
		echo "Usuário sem permissão para vender um ou mais dos itens selecionados.<br/><br/>Favor reiniciar o processo de compra.";
		die();
	}
}