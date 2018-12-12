<?php
require_once('../settings/functions.php');

function getConfigCielo($id) {
	$mainConnection = mainConnection();

	$rs_cielo = executeSQL($mainConnection, "SELECT
												ID_GATEWAY_PAGAMENTO,
												DS_URL,
												CD_GATEWAY_PAGAMENTO,
												DS_URL_CONSULTA,
												CD_KEY_GATEWAY_PAGAMENTO,
												DS_URL_RETORNO
											FROM MW_GATEWAY_PAGAMENTO
											WHERE (ID_GATEWAY = 8 AND IN_ATIVO = 1 AND ? IS NULL)
											OR ID_GATEWAY_PAGAMENTO = ?", array($id, $id), true);

	return array(
		'id' => $rs_cielo['ID_GATEWAY_PAGAMENTO'],

		'transaction_url' => $rs_cielo['DS_URL'],
		'query_url' => $rs_cielo['DS_URL_CONSULTA'],

		'merchantId' => $rs_cielo['CD_GATEWAY_PAGAMENTO'],
		'merchantKey' => $rs_cielo['CD_KEY_GATEWAY_PAGAMENTO'],

		'returnUrl' => $rs_cielo['DS_URL_RETORNO']
	);
}

function autorizarPedidoCielo($id_pedido, $dados_extra) {
	$config = getConfigCielo();

	$id_gateway_pagamento = $config['id'];
	$transaction_url = $config['transaction_url'];
	$merchantId = $config['merchantId'];
	$merchantKey = $config['merchantKey'];
	$returnUrl = $config['returnUrl'];
	
	$mainConnection = mainConnection();

	// checar se ja foi pago
	// $query = 'SELECT TOP 1 CD_STATUS, OBJ_PAGSEGURO FROM  MW_PEDIDO_PAGSEGURO WHERE ID_PEDIDO_VENDA = ? ORDER BY DT_STATUS DESC';
	// $params = array($id_pedido);
	// $rs = executeSQL($mainConnection, $query, $params, true);

	// if ($rs['CD_STATUS'] == 2) {
	// 	return array('success' => true, 'transaction' => unserialize(base64_decode($rs['OBJ_PAGSEGURO'])));
	// }

	$query = "SELECT
				P.ID_PEDIDO_VENDA,
				C.CD_EMAIL_LOGIN,
				ISNULL(P.VL_FRETE, 0) AS VL_FRETE,
				P.VL_TOTAL_PEDIDO_VENDA,
				P.ID_IP,
				C.ID_CLIENTE,
				C.CD_CPF,
				C.CD_RG,
				C.DS_NOME,
				C.DS_SOBRENOME,
				CONVERT(VARCHAR(10),DT_NASCIMENTO, 102) AS DT_NASCIMENTO,
				ISNULL(C.IN_SEXO, 'M') AS IN_SEXO,
				C.DS_ENDERECO,
				C.NR_ENDERECO,
				C.DS_COMPL_ENDERECO,
				C.DS_BAIRRO,
				C.DS_CIDADE,
				E.SG_ESTADO,
				C.CD_CEP,
				C.DS_DDD_TELEFONE,
				C.DS_TELEFONE,
				C.DS_DDD_CELULAR,
				C.DS_CELULAR,

				P.NR_PARCELAS_PGTO,
				P.CD_BIN_CARTAO,
				MP.CD_MEIO_PAGAMENTO,

				P.IN_RETIRA_ENTREGA,
				P.DS_CUIDADOS_DE,
				P.NM_CLIENTE_VOUCHER,
				P.DS_EMAIL_VOUCHER,
				P.DS_ENDERECO_ENTREGA,
				P.NR_ENDERECO_ENTREGA,
				P.DS_COMPL_ENDERECO_ENTREGA,
				P.DS_BAIRRO_ENTREGA,
				P.DS_CIDADE_ENTREGA,
				E2.SG_ESTADO AS SG_ESTADO_ENTREGA,
				P.CD_CEP_ENTREGA,

				C.ID_DOC_ESTRANGEIRO,
				P.NM_TITULAR_CARTAO
			FROM MW_PEDIDO_VENDA P
			INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
			INNER JOIN MW_ESTADO E ON E.ID_ESTADO = C.ID_ESTADO
			LEFT JOIN MW_ESTADO E2 ON E2.ID_ESTADO = P.ID_ESTADO
			LEFT JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
			WHERE P.ID_PEDIDO_VENDA = ?";

	$rs = executeSQL($mainConnection, $query, array($id_pedido), true);

	foreach($rs as $key => $val) {
		$rs[$key] = utf8_encode2($val);
	}

	$transaction_data = array(
		"MerchantOrderId" => $id_pedido,
		"Customer" => array(
			"Name" => $rs['DS_NOME'].' '.$rs['DS_SOBRENOME'],
			"Identity" => $rs['CD_CPF'],
			"IdentityType" => "CPF",
			"Email" => $rs['CD_EMAIL_LOGIN'],
			"Birthdate" => preg_replace('.', '-', $rs['DT_NASCIMENTO']),
			"Address" => array(
				"Street" => $rs['DS_ENDERECO'],
				"Number" => $rs['NR_ENDERECO'],
				"Complement" => $rs['DS_COMPL_ENDERECO'],
				"ZipCode" => $rs['CD_CEP'],
				"City" => $rs['DS_CIDADE'],
				"State" => $rs['SG_ESTADO'],
				"Country" => "BRA"
			)
		),
		"Payment" => array(
			"Amount" => number_format($rs['VL_TOTAL_PEDIDO_VENDA'] * 100, 0, '', '')
		)
	);

	// credit card
	if ($rs['CD_MEIO_PAGAMENTO'] == 920) {
		$transaction_data['Payment'] = array_merge($transaction_data['Payment'], array(
			"Capture" => false,
			"Type" => "CreditCard",
			"Installments" => $rs['NR_PARCELAS_PGTO'],
			"CreditCard" => array(
				"CardNumber" => $dados_extra['numCartao'],
				"Holder" => $dados_extra['nomeCartao'],
				"ExpirationDate" => $dados_extra['validadeMes'].'/'.$dados_extra['validadeAno'],
				"SecurityCode" => $dados_extra['codSeguranca'],
				"Brand" => $dados_extra['cardBrand']
			)
		));
	}
	// debit card
	elseif ($rs['CD_MEIO_PAGAMENTO'] == 921) {
		$transaction_data['Payment'] = array_merge($transaction_data['Payment'], array(
			"Capture" => true,
			"Type" => "DebitCard",
			"ReturnUrl" => $returnUrl,
			"DebitCard" => array(
				"CardNumber" => $dados_extra['numCartao'],
				"Holder" => $dados_extra['nomeCartao'],
				"ExpirationDate" => $dados_extra['validadeMes'].'/'.$dados_extra['validadeAno'],
				"SecurityCode" => $dados_extra['codSeguranca'],
				"Brand" => $dados_extra['cardBrand']
			)
		));
	}

	if ($rs['IN_RETIRA_ENTREGA'] == 'E') {
		$transaction_data['Customer']['DeliveryAddress'] = array(
			"Street" => $rs['DS_ENDERECO_ENTREGA'],
			"Number" => $rs['NR_ENDERECO_ENTREGA'],
			"Complement" => $rs['DS_COMPL_ENDERECO_ENTREGA'],
			"ZipCode" => $rs['CD_CEP_ENTREGA'],
			"City" => $rs['DS_CIDADE_ENTREGA'],
			"State" => $rs['SG_ESTADO_ENTREGA'],
			"Country" => "BRA"
        );
	}

	$header = array(
		"Content-Type: application/json",
		"MerchantId: $merchantId",
		"MerchantKey: $merchantKey"
	);

	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Enviado Cielo (autorizarPedidoCielo)', json_encode($transaction_data)));


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_URL, $transaction_url."/1/sales/");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction_data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	curl_close($ch);

	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Recebido Cielo (autorizarPedidoCielo)', json_encode($response)));

	$transaction = json_decode($response, true);

	$query = "UPDATE MW_PEDIDO_VENDA SET ID_GATEWAY_PAGAMENTO = ? WHERE ID_PEDIDO_VENDA = ?";
	$params = array($id_gateway_pagamento, $id_pedido);
	executeSQL($mainConnection, $query, $params);

	if (empty($curl_error) AND $transaction['Payment']['Status'] == 1) {

		$response = array('success' => true, 'transaction' => $transaction);

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $transaction['Payment']['Status'], base64_encode(serialize($transaction)));
		executeSQL($mainConnection, $query, $params);

	} elseif ($transaction['Payment']['Status'] == 3) {
		// negado
		$response = array('success' => false);

	} elseif(!empty($transaction['Payment']['AuthenticationUrl'])) {
		// redirecionar para pagamento
		$response = array('success' => true, 'redirect' => $transaction['Payment']['AuthenticationUrl']);

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $transaction['Payment']['Status'], base64_encode(serialize($transaction)));
		executeSQL($mainConnection, $query, $params);

	} else {
		$e = (empty($curl_error) ? $transaction : $curl_error);
		$response = array('success' => false, 'error' => tratarErroCielo($e, $id_pedido));
	}

	return $response;
}

function capturarPedidoCielo($id_pedido) {

	$mainConnection = mainConnection();

	$query = "SELECT PP.CD_STATUS, PP.OBJ_PAGSEGURO, PV.ID_GATEWAY_PAGAMENTO
				FROM MW_PEDIDO_PAGSEGURO PP
				INNER JOIN MW_PEDIDO_VENDA PV ON PV.ID_PEDIDO_VENDA = PP.ID_PEDIDO_VENDA
				WHERE PP.ID_PEDIDO_VENDA = ?
				ORDER BY PP.DT_STATUS DESC";

	$rs = executeSQL($mainConnection, $query, array($id_pedido), true);

	$obj = unserialize(base64_decode($rs['OBJ_PAGSEGURO']));
	
	$config = getConfigCielo($rs['ID_GATEWAY_PAGAMENTO']);

	$transaction_url = $config['transaction_url'];
	$merchantId = $config['merchantId'];
	$merchantKey = $config['merchantKey'];
	$returnUrl = $config['returnUrl'];

	$transaction_data = array(
		"PaymentId" => $obj['Payment']['PaymentId']
	);

	$header = array(
		"MerchantId: $merchantId",
		"MerchantKey: $merchantKey"
	);

	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Enviado Cielo (capturarPedidoCielo)', json_encode($transaction_data)));


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_URL, $transaction_url."/1/sales/".$obj['Payment']['PaymentId']."/capture");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($transaction_data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	curl_close($ch);

	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Recebido Cielo (capturarPedidoCielo)', json_encode($response)));

	$transaction = json_decode($response, true);

	if (empty($curl_error) AND $transaction['Status'] == 2) {

		$response = array('success' => true, 'transaction' => $transaction);

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $transaction['Status'], base64_encode(serialize($transaction)));
		executeSQL($mainConnection, $query, $params);

	} elseif ($transaction['Status'] == 3) {
		// negado
		$response = array('success' => false);

	} elseif(!empty($transaction['Payment']['AuthenticationUrl'])) {
		// redirecionar para pagamento
		$response = array('success' => true, 'redirect' => $transaction['Payment']['AuthenticationUrl']);

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $transaction['Payment']['Status'], base64_encode(serialize($transaction)));
		executeSQL($mainConnection, $query, $params);

	} else {
		$e = (empty($curl_error) ? $transaction : $curl_error);
		$response = array('success' => false, 'error' => tratarErroCielo($e, $id_pedido));
	}

	return $response;
}

function cancelarPedidoCielo($id_pedido) {

	$mainConnection = mainConnection();

	$query = "SELECT PP.CD_STATUS, PP.OBJ_PAGSEGURO, PV.ID_GATEWAY_PAGAMENTO
				FROM MW_PEDIDO_PAGSEGURO PP
				INNER JOIN MW_PEDIDO_VENDA PV ON PV.ID_PEDIDO_VENDA = PP.ID_PEDIDO_VENDA
				WHERE PP.ID_PEDIDO_VENDA = ?
				ORDER BY PP.DT_STATUS DESC";

	$rs = executeSQL($mainConnection, $query, array($id_pedido), true);

	$obj = unserialize(base64_decode($rs['OBJ_PAGSEGURO']));
	
	$config = getConfigCielo($rs['ID_GATEWAY_PAGAMENTO']);

	$transaction_url = $config['transaction_url'];
	$merchantId = $config['merchantId'];
	$merchantKey = $config['merchantKey'];
	$returnUrl = $config['returnUrl'];

	$transaction_data = array(
		"PaymentId" => $obj['Payment']['PaymentId']
	);

	$header = array(
		"MerchantId: $merchantId",
		"MerchantKey: $merchantKey"
	);
	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Enviado Cielo (cancelarPedidoCielo)', json_encode($transaction_data)));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_URL, $transaction_url."/1/sales/".$obj['Payment']['PaymentId']."/void");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($transaction_data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	curl_close($ch);
	
	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Recebido Cielo (cancelarPedidoCielo)', json_encode($response)));

	$transaction = json_decode($response, true);

	if (empty($curl_error) AND in_array($transaction['Payment']['Status'], array(10, 11))) {

		$response = array('success' => true, 'transaction' => $transaction);

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $transaction['Payment']['Status'], base64_encode(serialize($transaction)));
		executeSQL($mainConnection, $query, $params);

	} else {
		$e = (empty($curl_error) ? $transaction : $curl_error);
		$response = array('success' => false, 'error' => tratarErroCielo($e, $id_pedido));
	}

	return $response;
}

function consultarPedidoCielo($id) {
	$config = getConfigCielo();

	$query_url = $config['query_url'];
	$merchantId = $config['merchantId'];
	$merchantKey = $config['merchantKey'];

	$header = array(
		"Content-Type: application/json",
		"MerchantId: $merchantId",
		"MerchantKey: $merchantKey"
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_URL, $query_url."/1/sales/$id");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	curl_close($ch);

	$transaction = json_decode($response, true);

	if ($transaction AND empty($curl_error)) {
		return array('success' => true, 'transaction' => $transaction);
	} else {
		
		$result = executeSQL($mainConnection, "SELECT
													DS_URL,
													CD_GATEWAY_PAGAMENTO,
													CD_KEY_GATEWAY_PAGAMENTO
												FROM MW_GATEWAY_PAGAMENTO
												WHERE ID_GATEWAY = 8 AND IN_ATIVO = 0");

		while ($rs = fetchResult($result)) {
			$query_url = $rs['DS_URL'];
			$merchantId = $rs['CD_GATEWAY_PAGAMENTO'];
			$merchantKey = $rs['CD_KEY_GATEWAY_PAGAMENTO'];

			$header = array(
				"Content-Type: application/json",
				"MerchantId: $merchantId",
				"MerchantKey: $merchantKey"
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
			curl_setopt($ch, CURLOPT_URL, $query_url."/1/sales/$id");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$response = curl_exec($ch);
			$curl_error = curl_error($ch);
			curl_close($ch);

			$transaction = json_decode($response, true);

			if ($transaction AND empty($curl_error)) {
				return array('success' => true, 'transaction' => $transaction);
			}
		}
	}

	$e = (empty($curl_error) ? $transaction : $curl_error);
	return array('success' => false, 'error' => tratarErroCielo($e, $id));
}

function getStatusCielo($id) {
	$status = array(
		'0' => array(
			'name' => 'NotFinished',
			'description' => 'Falha ao processar o pagamento'
		),
		'1' => array(
			'name' => 'Authorized',
			'description' => 'Meio de pagamento apto a ser capturado ou pago(Boleto'
		),
		'2' => array(
			'name' => 'PaymentConfirmed',
			'description' => 'Pagamento confirmado e finalizado'
		),
		'3' => array(
			'name' => 'Denied',
			'description' => 'Transferência eletrônica'
		),
		'10' => array(
			'name' => 'Voided',
			'description' => 'Pagamento cancelado'
		),
		'11' => array(
			'name' => 'Refunded',
			'description' => 'Pagamento Cancelado/Estornado'
		),
		'12' => array(
			'name' => 'Pending',
			'description' => 'Esperando retorno da instituição financeira'
		),
		'13' => array(
			'name' => 'Aborted',
			'description' => 'Pagamento cancelado por falha no processamento'
		),
		'20' => array(
			'name' => 'Scheduled',
			'description' => 'Recorrência agendada'
		)
	);

	return $status[$id];
}

function estonarPedidoCielo($payment_id, $id_pedido) {

	$mainConnection = mainConnection();

	$query = "SELECT PV.ID_GATEWAY_PAGAMENTO
				FROM MW_PEDIDO_VENDA PV
				WHERE PV.ID_PEDIDO_VENDA = ?";

	$rs = executeSQL($mainConnection, $query, array($id_pedido), true);

	$config = getConfigCielo($rs['ID_GATEWAY_PAGAMENTO']);

	$transaction_url = $config['transaction_url'];
	$merchantId = $config['merchantId'];
	$merchantKey = $config['merchantKey'];

	$header = array(
		"Content-Type: application/json",
		"MerchantId: $merchantId",
		"MerchantKey: $merchantKey"
	);
	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Enviado Cielo (estonarPedidoCielo)', json_encode(array($payment_id, $id_pedido))));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
	curl_setopt($ch, CURLOPT_URL, $transaction_url."/1/sales/$payment_id/void");
	curl_setopt($ch, CURLOPT_PUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	$curl_error = curl_error($ch);
	curl_close($ch);

	executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('Json Recebido Cielo (estonarPedidoCielo)', json_encode($response)));

	$transaction = json_decode($response, true);

	if (empty($curl_error) AND in_array($transaction['Status'], array(10, 11))) {

		$response = array('success' => true, 'transaction' => $transaction);

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $transaction['Status'], base64_encode(serialize($transaction)));
		executeSQL($mainConnection, $query, $params);

    } else {

    	$e = (empty($curl_error) ? $transaction : $curl_error);
        $response = array('success' => false, 'error' => tratarErroCielo($e, $id_pedido));

    }

    return $response;
}

function tratarErroCielo($error_obj, $id_pedido) {

	$mainConnection = mainConnection();

	executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array(NULL, json_encode(array('descricao' => 'erro cielo pedido ' . $id_pedido, 'error' => $error_obj)))
    );

	// curl error
	if (!is_array($error_obj)) {
		$nova_msg = 'Erro de conexão. Favor informar o suporte.';
	}
	// cielo error
	else {
		$codes = array();
		foreach ($error_obj as $e) {
			$codes[] = $e['Code'];
		}

		$codes = implode(array_unique($codes), ',');
		$nova_msg = "Erro no processamento do pedido. Favor informar o suporte. ($codes)";

		/*switch ($error_obj) {
			// Internal error
			case '0':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// RequestId is required
			case '100':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// MerchantId is required
			case '101':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Payment Type is required
			case '102':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Payment Type can only contain letters
			case '103':
				$nova_msg = 'Caracteres especiais não permitidos';
			break;
			// Customer Identity is required
			case '104':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Customer Name is required
			case '105':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Transaction ID is required
			case '106':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// OrderId is invalid or does not exists
			case '107':
				$nova_msg = 'Campo enviado excede o tamanho ou contem caracteres especiais';
			break;
			// Amount must be greater or equal to zero
			case '108':
				$nova_msg = 'Valor da transação deve ser maior que “0”';
			break;
			// Payment Currency is required
			case '109':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Invalid Payment Currency
			case '110':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Payment Country is required
			case '111':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Invalid Payment Country
			case '112':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Invalid Payment Code
			case '113':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// The provided MerchantId is not in correct format
			case '114':
				$nova_msg = 'O MerchantId enviado não é um GUID';
			break;
			// The provided MerchantId was not found
			case '115':
				$nova_msg = 'O MerchantID não existe ou pertence a outro ambiente (EX: Sandbox)';
			break;
			// The provided MerchantId is blocked
			case '116':
				$nova_msg = 'Loja bloqueada, entre em contato com o suporte Cielo';
			break;
			// Credit Card Holder is required
			case '117':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Credit Card Number is required
			case '118':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// At least one Payment is required
			case '119':
				$nova_msg = 'Nó “Payment” não enviado';
			break;
			// Request IP not allowed. Check your IP White List
			case '120':
				$nova_msg = 'IP bloqueado por questões de segurança';
			break;
			// Customer is required
			case '121':
				$nova_msg = 'Nó “Customer” não enviado';
			break;
			// MerchantOrderId is required
			case '122':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Installments must be greater or equal to one
			case '123':
				$nova_msg = 'Numero de parcelas deve ser superior a 1';
			break;
			// Credit Card is Required
			case '124':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Credit Card Expiration Date is required
			case '125':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Credit Card Expiration Date is invalid
			case '126':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// You must provide CreditCard Number
			case '127':
				$nova_msg = 'Numero do cartão de crédito é obrigatório';
			break;
			// Card Number length exceeded
			case '128':
				$nova_msg = 'Numero do cartão superiro a 16 digitos';
			break;
			// Affiliation not found
			case '129':
				$nova_msg = 'Meio de pagamento não vinculado a loja ou Provider invalido';
			break;
			// Could not get Credit Card
			case '130':
				$nova_msg = '—';
			break;
			// MerchantKey is required
			case '131':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// MerchantKey is invalid
			case '132':
				$nova_msg = 'O Merchantkey enviado não é um válido';
			break;
			// Provider is not supported for this Payment Type
			case '133':
				$nova_msg = 'Provider enviado não existe';
			break;
			// FingerPrint length exceeded
			case '134':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// MerchantDefinedFieldValue length exceeded
			case '135':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// ItemDataName length exceeded
			case '136':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// ItemDataSKU length exceeded
			case '137':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// PassengerDataName length exceeded
			case '138':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// PassengerDataStatus length exceeded
			case '139':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// PassengerDataEmail length exceeded
			case '140':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// PassengerDataPhone length exceeded
			case '141':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// TravelDataRoute length exceeded
			case '142':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// TravelDataJourneyType length exceeded
			case '143':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// TravelLegDataDestination length exceeded
			case '144':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// TravelLegDataOrigin length exceeded
			case '145':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// SecurityCode length exceeded
			case '146':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address Street length exceeded
			case '147':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address Number length exceeded
			case '148':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address Complement length exceeded
			case '149':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address ZipCode length exceeded
			case '150':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address City length exceeded
			case '151':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address State length exceeded
			case '152':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address Country length exceeded
			case '153':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Address District length exceeded
			case '154':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Customer Name length exceeded
			case '155':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Customer Identity length exceeded
			case '156':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Customer IdentityType length exceeded
			case '157':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Customer Email length exceeded
			case '158':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// ExtraData Name length exceeded
			case '159':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// ExtraData Value length exceeded
			case '160':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Boleto Instructions length exceeded
			case '161':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Boleto Demostrative length exceeded
			case '162':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Return Url is required
			case '163':
				$nova_msg = 'URL de retorno não é valida - Não é aceito paginação ou extenções (EX .PHP) na URL de retorno';
			break;
			// AuthorizeNow is required
			case '166':
				$nova_msg = '—';
			break;
			// Antifraud not configured
			case '167':
				$nova_msg = 'Antifraude não vinculado ao cadastro do lojista';
			break;
			// Recurrent Payment not found
			case '168':
				$nova_msg = 'Recorrencia não encontrada';
			break;
			// Recurrent Payment is not active
			case '169':
				$nova_msg = 'Recorrencia não está ativa. Execução paralizada';
			break;
			// Cartão Protegido not configured
			case '170':
				$nova_msg = 'Cartão protegido não vinculado ao cadastro do lojista';
			break;
			// Affiliation data not sent
			case '171':
				$nova_msg = 'Falha no processamento do pedido - Entre em contato com o suporte Cielo';
			break;
			// Credential Code is required
			case '172':
				$nova_msg = 'Falha na validação das credenciadas enviadas';
			break;
			// Payment method is not enabled
			case '173':
				$nova_msg = 'Meio de pagamento não vinculado ao cadastro do lojista';
			break;
			// Card Number is required
			case '174':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// EAN is required
			case '175':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Payment Currency is not supported
			case '176':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// Card Number is invalid
			case '177':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// EAN is invalid
			case '178':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// The max number of installments allowed for recurring payment is 1
			case '179':
				$nova_msg = 'Campo enviado está vazio ou invalido';
			break;
			// The provided Card PaymentToken was not found
			case '180':
				$nova_msg = 'Token do Cartão protegido não encontrado';
			break;
			// The MerchantIdJustClick is not configured
			case '181':
				$nova_msg = 'Token do Cartão protegido bloqueado';
			break;
			// Brand is required
			case '182':
				$nova_msg = 'Bandeira do cartão não enviado';
			break;
			// Invalid customer bithdate
			case '183':
				$nova_msg = 'Data de nascimento invalida ou futura';
			break;
			// Request could not be empty
			case '184':
				$nova_msg = 'Falha no formado ta requisição. Verifique o código enviado';
			break;
			// Brand is not supported by selected provider
			case '185':
				$nova_msg = 'Bandeira não suportada pela API Cielo';
			break;
			// The selected provider does not support the options provided (Capture, Authenticate, Recurrent or Installments)
			case '186':
				$nova_msg = 'Meio de pagamento não suporta o comando enviado';
			break;
			// ExtraData Collection contains one or more duplicated names
			case '187':
				$nova_msg = '—';
			break;
			// Avs with CPF invalid
			case '188':
				$nova_msg = '—';
			break;
			// Avs with length of street exceeded
			case '189':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Avs with length of number exceeded
			case '190':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Avs with length of complement exceeded
			case '190':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Avs with length of district exceeded
			case '191':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Avs with zip code invalid
			case '192':
				$nova_msg = 'CEP enviado é invalido';
			break;
			// Split Amount must be greater than zero
			case '193':
				$nova_msg = 'Valor para realização do SPLIT deve ser superior a 0';
			break;
			// Split Establishment is Required
			case '194':
				$nova_msg = 'SPLIT não habilitado para o cadastro da loja';
			break;
			// The PlataformId is required
			case '195':
				$nova_msg = 'Validados de plataformas não enviado';
			break;
			// DeliveryAddress is required
			case '196':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// Street is required
			case '197':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// Number is required
			case '198':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// ZipCode is required
			case '199':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// City is required
			case '200':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// State is required
			case '201':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// District is required
			case '202':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// Cart item Name is required
			case '203':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// Cart item Quantity is required
			case '204':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// Cart item type is required
			case '205':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// Cart item name length exceeded
			case '206':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Cart item description length exceeded
			case '207':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Cart item sku length exceeded
			case '208':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Shipping addressee sku length exceeded
			case '209':
				$nova_msg = 'Dado enviado excede o tamanho do campo';
			break;
			// Shipping data cannot be null
			case '210':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// WalletKey is invalid
			case '211':
				$nova_msg = 'Dados da Visa Checkout invalidos';
			break;
			// Merchant Wallet Configuration not found
			case '212':
				$nova_msg = 'Dado de Wallet enviado não é valido';
			break;
			// Credit Card Number is invalid
			case '213':
				$nova_msg = 'Cartão de crédito enviado é invalido';
			break;
			// Credit Card Holder Must Have Only Letters
			case '214':
				$nova_msg = 'Portador do cartão não deve conter caracteres especiais';
			break;
			// Agency is required in Boleto Credential
			case '215':
				$nova_msg = 'Campo obrigatório não enviado';
			break;
			// Customer IP address is invalid
			case '216':
				$nova_msg = 'IP bloqueado por questões de segurança';
			break;
			// MerchantId was not found
			case '300':
				$nova_msg = '—';
			break;
			// Request IP is not allowed
			case '301':
				$nova_msg = '—';
			break;
			// Sent MerchantOrderId is duplicated
			case '302':
				$nova_msg = '—';
			break;
			// Sent OrderId does not exist
			case '303':
				$nova_msg = '—';
			break;
			// Customer Identity is required
			case '304':
				$nova_msg = '—';
			break;
			// Merchant is blocked
			case '306':
				$nova_msg = '—';
			break;
			// Transaction not found
			case '307':
				$nova_msg = 'Transação não encontrada ou não existente no ambiente.';
			break;
			// Transaction not available to capture
			case '308':
				$nova_msg = 'Transação não pode ser capturada - Entre em contato com o suporte Cielo';
			break;
			// Transaction not available to void
			case '309':
				$nova_msg = 'Transação não pode ser Cancelada - Entre em contato com o suporte Cielo';
			break;
			// Payment method doest not support this operation
			case '310':
				$nova_msg = 'Comando enviado não suportado pelo meio de pagamento';
			break;
			// Refund is not enabled for this merchant
			case '311':
				$nova_msg = 'Cancelamento após 24 horas não liberado para o lojista';
			break;
			// Transaction not available to refund
			case '312':
				$nova_msg = 'Transação não permite cancelamento após 24 horas';
			break;
			// Recurrent Payment not found
			case '313':
				$nova_msg = 'Transação recorrente não encontrada ou não disponivel no ambiente';
			break;
			// Invalid Integration
			case '314':
				$nova_msg = '—';
			break;
			// Cannot change NextRecurrency with pending payment
			case '315':
				$nova_msg = '—';
			break;
			// Cannot set NextRecurrency to past date
			case '316':
				$nova_msg = 'Não é permitido alterada dada da recorrencia para uma data passada';
			break;
			// Invalid Recurrency Day
			case '317':
				$nova_msg = '—';
			break;
			// No transaction found
			case '318':
				$nova_msg = '—';
			break;
			// Smart recurrency is not enabled
			case '319':
				$nova_msg = 'Recorrencia não vinculada ao cadastro do lojista';
			break;
			// Can not Update Affiliation Because this Recurrency not Affiliation saved
			case '320':
				$nova_msg = '—';
			break;
			// Can not set EndDate to before next recurrency.
			case '321':
				$nova_msg = '—';
			break;
			// Zero Dollar Auth is not enabled
			case '322':
				$nova_msg = 'Zero Dollar não vinculado ao cadastro do lojista';
			break;
			// Bin Query is not enabled
			case '323':
				$nova_msg = 'Consulta de Bins não vinculada ao cadastro do lojista';
			break;
		}*/
	}

	return $nova_msg;
}