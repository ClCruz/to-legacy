<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
require_once('../settings/functions.php');

require '../settings/PagSeguroLibrary/PagSeguroLibrary.php';

function getPagSeguroSessionId() {
	try {

		$credentials = PagSeguroConfig::getAccountCredentials();
		$sessionId = PagSeguroSessionService::getSession($credentials);

	} catch (PagSeguroServiceException $e) {
		$sessionId = '';
	}

	return $sessionId;
}

function pagarPedidoPagSeguro($id_pedido, $dados_extra) {

	$mainConnection = mainConnection();

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
				CONVERT(VARCHAR(10),DT_NASCIMENTO, 103) AS DT_NASCIMENTO,
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

	$directPaymentRequest = new PagSeguroDirectPaymentRequest();
	$directPaymentRequest->setPaymentMode('DEFAULT');

	$directPaymentRequest->setCurrency("BRL");

	$directPaymentRequest->setReference("$id_pedido");

	if ($_ENV['IS_TEST']) {
		$directPaymentRequest->setNotificationURL('http://localhost:1002/comprar/pagseguro_receiver.php');
	} else {
		$directPaymentRequest->setNotificationURL(multiSite_getURICompra('comprar/pagseguro_receiver.php'));
	}

	$query = "SELECT
					E.DS_EVENTO + ' - ' + I.DS_LOCALIZACAO + ' - ' + AB.DS_TIPO_BILHETE AS DESCRICAO,
					I.VL_UNITARIO + I.VL_TAXA_CONVENIENCIA AS VALOR
				FROM MW_ITEM_PEDIDO_VENDA I
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
				INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = I.ID_APRESENTACAO_BILHETE
				WHERE ID_PEDIDO_VENDA = ?";
	$result = executeSQL($mainConnection, $query, array($id_pedido));

	$i = 1;
	while ($rs2 = fetchResult($result)) {
		$directPaymentRequest->addItem("$i", $rs2['DESCRICAO'], 1, $rs2['VALOR']);
		$i++;
	}

	$directPaymentRequest->setSender(
		$rs['DS_NOME'].' '.$rs['DS_SOBRENOME'],
		$_ENV['IS_TEST'] ? 'email@sandbox.pagseguro.com.br' : $rs['CD_EMAIL_LOGIN'],
		$rs['DS_DDD_TELEFONE'],
		$rs['DS_TELEFONE'],
		'CPF',
		$rs['CD_CPF']
	);

	$directPaymentRequest->setSenderHash($dados_extra['senderHash']);

	$directPaymentRequest->setShippingAddress(
		$rs['IN_RETIRA_ENTREGA'] == 'R' ? $rs['CD_CEP'] : $rs['CD_CEP_ENTREGA'],
		$rs['IN_RETIRA_ENTREGA'] == 'R' ? $rs['DS_ENDERECO'] : $rs['DS_ENDERECO_ENTREGA'],
		$rs['IN_RETIRA_ENTREGA'] == 'R' ? $rs['NR_ENDERECO'] : $rs['NR_ENDERECO_ENTREGA'],
		$rs['IN_RETIRA_ENTREGA'] == 'R' ? $rs['DS_COMPL_ENDERECO'] : $rs['DS_COMPL_ENDERECO_ENTREGA'],
		$rs['IN_RETIRA_ENTREGA'] == 'R' ? $rs['DS_BAIRRO'] : $rs['DS_BAIRRO_ENTREGA'],
		$rs['IN_RETIRA_ENTREGA'] == 'R' ? $rs['DS_CIDADE'] : $rs['DS_CIDADE_ENTREGA'],
		$rs['IN_RETIRA_ENTREGA'] == 'R' ? $rs['SG_ESTADO'] : $rs['SG_ESTADO_ENTREGA'],
		'BRA'
	);

	switch ($rs['CD_MEIO_PAGAMENTO']) {
		case 900:
			$directPaymentRequest->setPaymentMethod('BOLETO');
		break;
		case 901:
			$directPaymentRequest->setPaymentMethod('EFT');

			$directPaymentRequest->setOnlineDebit(
	            array(
	                "bankName" => $dados_extra['bankName']
	            )
	        );
		break;
		case 902:
			$directPaymentRequest->setPaymentMethod('CREDIT_CARD');

			$creditCardToken = $dados_extra['cardToken'];

			$installments = new PagSeguroDirectPaymentInstallment(
	            array(
	              "quantity" => $rs['NR_PARCELAS_PGTO'],
	              "value" => round($rs['VL_TOTAL_PEDIDO_VENDA'] / $rs['NR_PARCELAS_PGTO'], 2),
	              "noInterestInstallmentQuantity" => ($rs['NR_PARCELAS_PGTO'] > 1 ? $rs['NR_PARCELAS_PGTO'] : NULL)
	            )
	        );

			$billingAddress = new PagSeguroBilling(
				array(
					'postalCode' => $rs['CD_CEP'],
					'street' => $rs['DS_ENDERECO'],
					'number' => $rs['NR_ENDERECO'],
					'complement' => $rs['DS_COMPL_ENDERECO'],
					'district' => $rs['DS_BAIRRO'],
					'city' => $rs['DS_CIDADE'],
					'state' => $rs['SG_ESTADO'],
					'country' => 'BRA'
				)
			);

			$creditCardData = new PagSeguroCreditCardCheckout(
				array(
					'token' => $creditCardToken,
					'installment' => $installments,
					'billing' => $billingAddress,
					'holder' => new PagSeguroCreditCardHolder(
						array(
							'name' => $rs['NM_TITULAR_CARTAO'],
							'birthDate' => $rs['DT_NASCIMENTO'],
							'areaCode' => $rs['DS_DDD_TELEFONE'],
							'number' => $rs['DS_TELEFONE'],
							'documents' => array(
								'type' => 'CPF',
								'value' => $rs['CD_CPF']
							)
						)
					)
				)
			);

			$directPaymentRequest->setCreditCard($creditCardData);
		break;
	}

	try {

		$credentials = PagSeguroConfig::getAccountCredentials();
		$response = array('success' => true, 'transaction' => $directPaymentRequest->register($credentials));

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $response['transaction']->getStatus()->getValue(), base64_encode(serialize($response['transaction'])));
		executeSQL($mainConnection, $query, $params);

	} catch (PagSeguroServiceException $e) {
		$response = array('success' => false, 'error' => tratarErroPagseguro($e));
	}

	return $response;
}

function getStatusPagSeguro($id) {
	$status = array(
		1 => array(
			'name' => 'aguardando pagamento',
			'description' => 'o comprador iniciou a transação, mas até o momento o PagSeguro não recebeu nenhuma informação sobre o pagamento'
		),
		2 => array(
			'name' => 'em análise',
			'description' => 'o comprador optou por pagar com um cartão de crédito e o PagSeguro está analisando o risco da transação'
		),
		3 => array(
			'name' => 'paga',
			'description' => 'a transação foi paga pelo comprador e o PagSeguro já recebeu uma confirmação da instituição financeira responsável pelo processamento'
		),
		4 => array(
			'name' => 'disponível',
			'description' => 'a transação foi paga e chegou ao final de seu prazo de liberação sem ter sido retornada e sem que haja nenhuma disputa aberta'
		),
		5 => array(
			'name' => 'em disputa',
			'description' => 'o comprador, dentro do prazo de liberação da transação, abriu uma disputa'
		),
		6 => array(
			'name' => 'devolvida',
			'description' => 'o valor da transação foi devolvido para o comprador'
		),
		7 => array(
			'name' => 'cancelada',
			'description' => 'a transação foi cancelada sem ter sido finalizada'
		),
		8 => array(
			'name' => 'debitado',
			'description' => 'o valor da transação foi devolvido para o comprador'
		),
		9 => array(
			'name' => 'retenção temporária',
			'description' => 'o comprador abriu uma solicitação de chargeback junto à operadora do cartão de crédito'
		)
	);

	return $status[$id];
}

function getNotificationPagSeguro($notificationCode) {
	try {
		$credentials = PagSeguroConfig::getAccountCredentials();
		$response = PagSeguroNotificationService::checkTransaction(
			$credentials,
			$notificationCode
		);

		$response = array('success' => true, 'transaction' => $response);

	} catch (PagSeguroServiceException $e) {
		$response = array('success' => false, 'error' => tratarErroPagseguro($e));
	}

	return $response;
}

function estonarPedidoPagseguro($transactionCode) {
	try {
        
        $credentials = PagSeguroConfig::getAccountCredentials();
        $response = PagSeguroRefundService::createRefundRequest(
            $credentials,
            $transactionCode
        );

		$response = array('success' => true, 'transaction' => $response);

    } catch (PagSeguroServiceException $e) {

        $response = array('success' => false, 'error' => tratarErroPagseguro($e));

    }

    return $response;
}

function tratarErroPagseguro($error_obj) {
	$nova_msg = '';

	foreach ($error_obj->getErrors() as $e) {
		switch ($e->getCode()) {
			case 5003:
				// msg do pagseguro: Falha de comunicação com a instituição financeira {Nome do Banco}.
				$nova_msg .= $e->getMessage(); // msg mantida
			break;
			case 10000:
				// msg do pagseguro: invalid creditcard brand.
			case 10001:
				// msg do pagseguro: creditcard number with invalid length.
			case 10002:
				// msg do pagseguro: invalid date format.
			case 10003:
				// msg do pagseguro: invalid security field.
			case 10004:
				// msg do pagseguro: cvv is mandatory.
			case 10006:
				// msg do pagseguro: security field with invalid length.
				$nova_msg .= 'Dados do cartão de crédito inválidos.';
			break;
			case 53004:
				// msg do pagseguro: items invalid quantity.
			case 53005:
				// msg do pagseguro: currency is required.
			case 53006:
				// msg do pagseguro: currency invalid value: {0}
			case 53007:
				// msg do pagseguro: reference invalid length: {0}
			case 53008:
				// msg do pagseguro: notificationURL invalid length: {0}
			case 53009:
				// msg do pagseguro: notificationURL invalid value: {0}
			case 53010:
				// msg do pagseguro: sender email is required.
			case 53011:
				// msg do pagseguro: sender email invalid length: {0}
			case 53012:
				// msg do pagseguro: sender email invalid value: {0}
			case 53013:
				// msg do pagseguro: sender name is required.
			case 53014:
				// msg do pagseguro: sender name invalid length: {0}
			case 53015:
				// msg do pagseguro: sender name invalid value: {0}
			case 53017:
				// msg do pagseguro: sender cpf invalid value: {0}
			case 53018:
				// msg do pagseguro: sender area code is required.
			case 53019:
				// msg do pagseguro: sender area code invalid value: {0}
			case 53020:
				// msg do pagseguro: sender phone is required.
			case 53021:
				// msg do pagseguro: sender phone invalid value: {0}
			case 53022:
				// msg do pagseguro: shipping address postal code is required.
			case 53023:
				// msg do pagseguro: shipping address postal code invalid value: {0}
			case 53024:
				// msg do pagseguro: shipping address street is required.
			case 53025:
				// msg do pagseguro: shipping address street invalid length: {0}
			case 53026:
				// msg do pagseguro: shipping address number is required.
			case 53027:
				// msg do pagseguro: shipping address number invalid length: {0}
			case 53028:
				// msg do pagseguro: shipping address complement invalid length: {0}
			case 53029:
				// msg do pagseguro: shipping address district is required.
			case 53030:
				// msg do pagseguro: shipping address district invalid length: {0}
			case 53031:
				// msg do pagseguro: shipping address city is required.
			case 53032:
				// msg do pagseguro: shipping address city invalid length: {0}
			case 53033:
				// msg do pagseguro: shipping address state is required.
			case 53034:
				// msg do pagseguro: shipping address state invalid value: {0}
			case 53035:
				// msg do pagseguro: shipping address country is required.
			case 53036:
				// msg do pagseguro: shipping address country invalid length: {0}
			case 53037:
				// msg do pagseguro: credit card token is required.
			case 53038:
				// msg do pagseguro: installment quantity is required.
			case 53039:
				// msg do pagseguro: installment quantity invalid value: {0}
			case 53040:
				// msg do pagseguro: installment value is required.
			case 53041:
				// msg do pagseguro: installment value invalid value: {0}
			case 53042:
				// msg do pagseguro: credit card holder name is required.
			case 53043:
				// msg do pagseguro: credit card holder name invalid length: {0}
			case 53044:
				// msg do pagseguro: credit card holder name invalid value: {0}
			case 53045:
				// msg do pagseguro: credit card holder cpf is required.
			case 53046:
				// msg do pagseguro: credit card holder cpf invalid value: {0}
			case 53047:
				// msg do pagseguro: credit card holder birthdate is required.
			case 53048:
				// msg do pagseguro: credit card holder birthdate invalid value: {0}
			case 53049:
				// msg do pagseguro: credit card holder area code is required.
			case 53050:
				// msg do pagseguro: credit card holder area code invalid value: {0}
			case 53051:
				// msg do pagseguro: credit card holder phone is required.
			case 53052:
				// msg do pagseguro: credit card holder phone invalid value: {0}
			case 53053:
				// msg do pagseguro: billing address postal code is required.
			case 53054:
				// msg do pagseguro: billing address postal code invalid value: {0}
			case 53055:
				// msg do pagseguro: billing address street is required.
			case 53056:
				// msg do pagseguro: billing address street invalid length: {0}
			case 53057:
				// msg do pagseguro: billing address number is required.
			case 53058:
				// msg do pagseguro: billing address number invalid length: {0}
			case 53059:
				// msg do pagseguro: billing address complement invalid length: {0}
			case 53060:
				// msg do pagseguro: billing address district is required.
			case 53061:
				// msg do pagseguro: billing address district invalid length: {0}
			case 53062:
				// msg do pagseguro: billing address city is required.
			case 53063:
				// msg do pagseguro: billing address city invalid length: {0}
			case 53064:
				// msg do pagseguro: billing address state is required.
			case 53065:
				// msg do pagseguro: billing address state invalid value: {0}
			case 53066:
				// msg do pagseguro: billing address country is required.
			case 53067:
				// msg do pagseguro: billing address country invalid length: {0}
			case 53068:
				// msg do pagseguro: receiver email invalid length: {0}
			case 53069:
				// msg do pagseguro: receiver email invalid value: {0}
			case 53070:
				// msg do pagseguro: item id is required.
			case 53071:
				// msg do pagseguro: item id invalid length: {0}
			case 53072:
				// msg do pagseguro: item description is required.
			case 53073:
				// msg do pagseguro: item description invalid length: {0}
			case 53074:
				// msg do pagseguro: item quantity is required.
			case 53075:
				// msg do pagseguro: item quantity out of range: {0}
			case 53076:
				// msg do pagseguro: item quantity invalid value: {0}
			case 53077:
				// msg do pagseguro: item amount is required.
			case 53078:
				// msg do pagseguro: item amount invalid pattern: {0}. Must fit the patern: \\d+.\\d\{2\}
			case 53079:
				// msg do pagseguro: item amount out of range: {0}
			case 53081:
				// msg do pagseguro: sender is related to receiver.
			case 53084:
				// msg do pagseguro: invalid receiver: {0}, verify receiver's account status and if it is a seller's account.
			case 53085:
				// msg do pagseguro: payment method unavailable.
			case 53086:
				// msg do pagseguro: cart total amount out of range: {0}
			case 53087:
				// msg do pagseguro: invalid credit card data.
			case 53091:
				// msg do pagseguro: sender hash invalid.
			case 53092:
				// msg do pagseguro: credit card brand is not accepted.
			case 53095:
				// msg do pagseguro: shipping type invalid pattern: {0}
			case 53096:
				// msg do pagseguro: shipping cost invalid pattern: {0}
			case 53097:
				// msg do pagseguro: shipping cost out of range: {0}
			case 53098:
				// msg do pagseguro: cart total value is negative: {0}
			case 53099:
				// msg do pagseguro: extra amount invalid pattern: {0}. Must fit the patern: -?\\d+.\\d\{2\}
			case 53101:
				// msg do pagseguro: payment mode invalid value, valid values are default and gateway.
			case 53102:
				// msg do pagseguro: payment method invalid value, valid values are creditCard, boleto e eft.
			case 53104:
				// msg do pagseguro: shipping cost was provided, shipping address must be complete.
			case 53105:
				// msg do pagseguro: sender information was provided, email must be provided too.
			case 53106:
				// msg do pagseguro: credit card holder is incomplete.
			case 53109:
				// msg do pagseguro: shipping address information was provided, sender email must be provided too.
			case 53110:
				// msg do pagseguro: eft bank is required.
			case 53111:
				// msg do pagseguro: eft bank is not accepted.
			case 53122:
				// msg do pagseguro: sender email invalid domain: {0}. You must use an email @sandbox.pagseguro.com.br
			case 53140:
				// msg do pagseguro: installment quantity out of range: {0}. The value must be greater than zero.
			case 53141:
				// msg do pagseguro: sender is blocked.
			case 53142:
				// msg do pagseguro: credit card token invalid.
				$nova_msg .= 'Dados inválidos no pedido. Favor informar o suporte se o erro persistir.';
			break;
			case 53115:
				// msg do pagseguro: sender born date invalid value: {0}
				$nova_msg .= 'Data de nascimento inválida. Por favor, atualize seus dados cadastrais antes de continuar.';
			break;
			case 53117:
				// msg do pagseguro: sender cnpj invalid value: {0}
				$nova_msg .= 'CNPJ inválido.';
			break;
		}

		$nova_msg .= ' ('.$e->getCode().')<br>';
	}

	return $nova_msg;
}