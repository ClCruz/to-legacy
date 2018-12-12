<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
require_once('../settings/Cypher.class.php');

function auth($user, $password) {
	$mainConnection = mainConnection();

	$query = 'SELECT 1 FROM MW_USUARIO WHERE CD_LOGIN = ? AND CD_PWW = ? AND IN_ATIVO = 1';
    $params = array($user, md5($password));
    $rs = executeSQL($mainConnection, $query, $params, true);

    return $rs[0];
}

function getItems($order_id) {
	$mainConnection = mainConnection();

	$query = 'SELECT
							 E.ID_EVENTO,
							 I.ID_APRESENTACAO,
							 E.DS_EVENTO,
							 B.DS_NOME_TEATRO,
							 A.DT_APRESENTACAO,
							 A.HR_APRESENTACAO,
							 I.DS_LOCALIZACAO,
							 I.DS_SETOR,
							 I.VL_UNITARIO,
							 I.VL_TAXA_CONVENIENCIA,
							 AB.DS_TIPO_BILHETE,
							 I.INDICE,
							 A.CODAPRESENTACAO,
							 I.CODVENDA,
							 E.ID_BASE
							 FROM
							 MW_PEDIDO_VENDA P
							 INNER JOIN MW_ITEM_PEDIDO_VENDA I ON I.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA
							 INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
							 INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
							 INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
							 INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = I.ID_APRESENTACAO_BILHETE
							 WHERE P.ID_PEDIDO_VENDA = ?

				union all

				SELECT
							 I.ID_EVENTO,
							 I.ID_APRESENTACAO,
							 I.DS_NOME_EVENTO AS DS_EVENTO,
							 I.DS_NOME_LOCAL AS DS_NOME_TEATRO,
							 I.DT_APRESENTACAO,
							 I.HR_APRESENTACAO,
							 I.DS_LOCALIZACAO,
							 I.DS_SETOR,
							 I.VL_UNITARIO,
							 I.VL_TAXA_CONVENIENCIA,
							 I.DS_TIPO_BILHETE,
							 NULL,
							 NULL,
							 NULL,
							 NULL
							 FROM
							 MW_PEDIDO_VENDA P
							 INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST I ON I.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA
							 WHERE P.ID_PEDIDO_VENDA = ?


							 ORDER BY DS_EVENTO, ID_APRESENTACAO, DS_LOCALIZACAO';

	$result = executeSQL($mainConnection, $query, array($order_id, $order_id));

	$return_data = array();

	while ($rs = fetchResult($result)) {
		$conn = getConnection($rs['ID_BASE']);

		$queryCodigo = "SELECT codbar
		                FROM tabControleSeqVenda c
		                INNER JOIN tabLugSala l ON l.CodApresentacao = c.CodApresentacao AND l.Indice = c.Indice
		                WHERE l.CodApresentacao = ? AND l.CodVenda = ? AND c.Indice = ? AND c.statusingresso = 'L'";
		$params = array($rs['CODAPRESENTACAO'], $rs['CODVENDA'], $rs['INDICE']);

		$codigo = executeSQL($conn, $queryCodigo, $params, true);

		$evento_info = getEvento($rs['ID_EVENTO']);
		
		$return_data[] = array(
			'evento' => utf8_encode2($rs['DS_EVENTO']),
			'evento_id' => $rs['ID_EVENTO'],
			'endereco' => $evento_info['endereco'],
			'nome_teatro' => $evento_info['nome_teatro'],
			'data' => $rs['DT_APRESENTACAO']->format('d/m/Y'),
			'hora' => $rs['HR_APRESENTACAO'],
			'setor' => utf8_encode2($rs['DS_SETOR']),
			'localizacao' => $rs['DS_LOCALIZACAO'],
			'tipo' => utf8_encode2($rs['DS_TIPO_BILHETE']),
			'codigo' => $codigo[0],
			'valor_ingresso' => number_format($rs['VL_UNITARIO'], 2, ',', '')
		);
	}

	return empty($return_data) ? NULL : $return_data;
}

function getHistory($client_id) {
	$mainConnection = mainConnection();

	$query = "SELECT
					ID_PEDIDO_VENDA,
				    DT_PEDIDO_VENDA, 
				    VL_TOTAL_PEDIDO_VENDA,
				    ID_PEDIDO_PAI,
				    (SELECT TOP 1 1 FROM MW_PEDIDO_VENDA b WHERE b.ID_PEDIDO_PAI = a.ID_PEDIDO_VENDA) as ASSINATURA
				FROM MW_PEDIDO_VENDA a
				WHERE ID_CLIENTE = ? AND IN_SITUACAO <> 'P'
				ORDER BY 1 DESC";

	$result = executeSQL($mainConnection, $query, array($client_id));

	$orders = array();

	while ($rs = fetchResult($result)) {
		
		$orders[] = array(
			'number' => $rs['ID_PEDIDO_VENDA'],
			'assinatura' => $rs['ASSINATURA'] ? TRUE : FALSE,
			'pedido_pai' => $rs['ID_PEDIDO_PAI'],
			'date' => utf8_encode2(strftime("%a %#d %b", $rs['DT_PEDIDO_VENDA']->format('U'))),
			'total' => number_format($rs['VL_TOTAL_PEDIDO_VENDA'], 2, ',', ''),
			'items' => getItems($rs['ID_PEDIDO_VENDA'])
		);
	}

	return empty($orders) ? NULL : array('orders' => $orders);
}

if (auth($_POST['user'], $_POST['password'])) {

	switch ($_POST['action']) {

		case 'getItems':
			echo json_encode(getItems($_POST['order_id']));
		break;

		case 'getHistory': {

			$cipher = new Cipher('1ngr3ss0s');
			$decryptedtext = $cipher->decrypt($_POST['client_id']);

			echo json_encode(getHistory($decryptedtext));

		break; }

		default:
			echo "Invalid action specified.";

	}

} else {
	header('HTTP/1.1 401 Unauthorized');
	echo "Unauthorized Access!";
}