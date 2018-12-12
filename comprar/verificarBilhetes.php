<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

$query = "SELECT COUNT(1) INGRESSOS_NULOS FROM MW_RESERVA WHERE ID_SESSION = ? AND ID_APRESENTACAO_BILHETE IS NULL";
$rs = executeSQL($mainConnection, $query, array(session_id()), true);

if ($rs['INGRESSOS_NULOS'] > 0) {
	$msgBilheteInvalido = 'Não é possível concluir o pedido.<br><br>Favor alterar o pedido para continuar.';
}

// tem bilhete bin sem codigo?
// tem bilhete promocional sem codigo?
$query = "SELECT E.ID_BASE, AB.CODTIPBILHETE FROM MW_RESERVA R
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
			INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
			INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO = A.ID_APRESENTACAO AND AB.IN_ATIVO = 1 AND AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
			WHERE R.ID_SESSION = ? AND (R.NR_BENEFICIO IS NULL AND R.CD_BINITAU IS NULL)
			GROUP BY E.ID_BASE, AB.CODTIPBILHETE";
$result = executeSQL($mainConnection, $query, array(session_id()));

while ($rs = fetchResult($result)) {
	$conn = getConnection($rs['ID_BASE']);
	$query = "SELECT 1 FROM TABTIPBILHETE WHERE CODTIPBILHETE = ? AND ID_PROMOCAO_CONTROLE IS NOT NULL";
	$rs = executeSQL($conn, $query, array($rs['CODTIPBILHETE']), true);

	if ($rs[0]) {
		$msgBilheteInvalido = 'Não é possível concluir o pedido.<br><br>Favor validar todos os ingressos promocionais.';
		break;
	}	
}

// tem bilhete promocional por cpf diferente do cpf do usuario?
$query = "SELECT CD_CPF_PROMOCIONAL FROM MW_PROMOCAO WHERE ID_SESSION = ? AND CD_CPF_PROMOCIONAL IS NOT NULL";
$result = executeSQL($mainConnection, $query, array(session_id()));

$query = "SELECT CD_CPF FROM MW_CLIENTE WHERE ID_CLIENTE = ?";
$cpf = executeSQL($mainConnection, $query, array($_SESSION['user']), true);
$cpf = preg_replace('/[\.\-]/', '', $cpf[0]);

while ($rs = fetchResult($result)) {
	if (preg_replace('/[\.\-]/', '', $rs[0]) != $cpf) {
		$msgBilheteInvalido = "O código promocional informado é pessoal e intransferível, portanto só poderá ser \
								utilizado para o CPF do cliente que consta em nossos cadastros.<br/><br/>\
								Selecione outro tipo de ingresso ou utilize o cadastro do beneficiário da promoção.";
		break;
	}	
}


if ($msgBilheteInvalido) {
	if (basename($_SERVER['SCRIPT_FILENAME']) == 'etapa5.php') {
		header("Location: etapa4.php");
	} else {
		$scriptBilheteInvalido = '<script type="text/javascript">
										$(function(){
											$.dialog({title:"Aviso...", text:"'.$msgBilheteInvalido.'", uiOptions:{width:500}});
										});
									</script>';
	}
}