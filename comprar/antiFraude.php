<?php
//se for operador ignorar as regras anti-fraude
if (!isset($_SESSION['operador']) AND !$_ENV['IS_TEST']) {

	$cartao = $_POST['numCartao'];

	// Ticket #213 (https://portal.cc.com.br:8084/projetos/ticket/213)
	$query = "SELECT TOP 1 ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA
				WHERE IN_SITUACAO = 'F'
				AND ID_USUARIO_CALLCENTER IS NULL
				AND DATEADD(HOUR, 1, DT_PEDIDO_VENDA) > GETDATE()
				AND (ID_CLIENTE = ? AND (CD_BIN_CARTAO <> ? AND CD_BIN_CARTAO <> ?) OR ID_CLIENTE <> ? AND ID_IP = ?)";
	$params = array($_SESSION['user'], substr($cartao, 0, 6) . '******' . substr($cartao, -4), $cartao, $_SESSION['user'], $_SERVER["HTTP_X_FORWARDED_FOR"]);
	$rows = numRows($mainConnection, $query, $params);

	//print_r(array('<pre>', $query, $params, '</pre>'));die();

	if ($rows > 0) {
		echo "Prezado Cliente, por favor entre em contato com a nossa central de atendimento, através do número 11 2122 4070
				de segunda a domingo das 09h00 às 21h00, informando a seguinte mensagem: (erro 539)";
		die();
	}

}