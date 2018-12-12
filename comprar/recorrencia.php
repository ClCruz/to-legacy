<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

$query = "SELECT ID_CLIENTE, ID_ASSINATURA, ID_DADOS_CARTAO
			FROM MW_ASSINATURA_CLIENTE
			WHERE DT_PROXIMO_PAGAMENTO = CAST(GETDATE() AS DATE) AND IN_ATIVO = 1 AND ID_ASSINATURA_CLIENTE = ?";
$params = array($_GET['id']);
$rs = executeSQL($mainConnection, $query, $params, true);

if (empty($rs)) {
	die('false');
} else {

	require_once('../settings/Cypher.class.php');
	require_once('../settings/antiFraude.php');

	$id_dados_cartao = $rs['ID_DADOS_CARTAO'];
	$id_cliente = $rs['ID_CLIENTE'];
	$id_assinatura = $rs['ID_ASSINATURA'];

	$query = "SELECT DS_NOME_TITULAR, CD_NUMERO_CARTAO, CD_CODIGO_SEGURANCA, DT_VALIDADE, CD_MEIO_PAGAMENTO
				FROM MW_DADOS_CARTAO DC
				INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = DC.ID_MEIO_PAGAMENTO
				WHERE ID_CLIENTE = ? AND ID_DADOS_CARTAO = ?";
	$params = array($id_cliente, $id_dados_cartao);
	$rs = executeSQL($mainConnection, $query, $params, true);

	$cipher = new Cipher('1ngr3ss0s');

	$cartao_titular = $cipher->decrypt($rs['DS_NOME_TITULAR']);
	$cartao_numero = $cipher->decrypt($rs['CD_NUMERO_CARTAO']);
	$cartao_cod_seguranca = $cipher->decrypt($rs['CD_CODIGO_SEGURANCA']);
	$cartao_validade = $cipher->decrypt($rs['DT_VALIDADE']);
	$meio_pagamento = $rs['CD_MEIO_PAGAMENTO'];

	$query = 'SELECT
            C.ID_CLIENTE,C.DS_NOME,C.DS_SOBRENOME,C.DS_DDD_TELEFONE,C.DS_TELEFONE,C.DS_DDD_CELULAR,C.DS_CELULAR,C.CD_CPF,C.DS_ENDERECO,C.NR_ENDERECO,C.DS_COMPL_ENDERECO,C.DS_BAIRRO,C.DS_CIDADE,C.CD_CEP,C.CD_EMAIL_LOGIN,C.ID_ESTADO,E.SG_ESTADO
            FROM MW_CLIENTE C
            LEFT JOIN MW_ESTADO E ON E.ID_ESTADO = C.ID_ESTADO
            WHERE C.ID_CLIENTE = ?';
	$params = array($id_cliente);
	$rs = executeSQL($mainConnection, $query, $params, true);

	foreach($rs as $key => $val) {
	        $rs[$key] = utf8_encode2($val);
	}

	$valor_pagar = getProximoValorAssinatura($_GET['id']);

	if ($valor_pagar == 0) {
		$rsAux = executeSQL($mainConnection, "SELECT MAX(VL_ASSINATURA) FROM MW_ASSINATURA_VALOR WHERE ID_ASSINATURA = ?", array($id_assinatura), true);
		$valor_pagar = $rsAux[0];
		$cancelar_em_sucesso = true;
	} else {
		$cancelar_em_sucesso = false;
	}

	//RequestID
	$ri = md5(time());
	$ri = substr($ri, 0, 8) .'-'. substr($ri, 8, 4) .'-'. substr($ri, 12, 4) .'-'. substr($ri, 16, 4) .'-'. substr($ri, -12);

	//Parâmetros obrigatórios.
	$parametros = array();
	$PaymentDataCollection = array();
	$dadosExtrasEmail = array();

	$dadosExtrasEmail['cpf_cnpj_cliente'] = $rs['CD_CPF'];
	$dadosExtrasEmail['ddd_telefone1'] = $rs['DS_DDD_TELEFONE'];
	$dadosExtrasEmail['numero_telefone1'] = $rs['DS_TELEFONE'];
	$dadosExtrasEmail['ddd_telefone2'] = $rs['DS_DDD_CELULAR'];
	$dadosExtrasEmail['numero_telefone2'] = $rs['DS_CELULAR'];
	$dadosExtrasEmail['ddd_telefone3'] = '';
	$dadosExtrasEmail['numero_telefone3'] = '';

	$parametros['RequestId'] = $ri;
	$parametros['Version'] = '1.0';

	//--------------------
	$rs_gateway_pagamento = executeSQL($mainConnection, 'SELECT CD_GATEWAY_PAGAMENTO, DS_URL FROM MW_GATEWAY_PAGAMENTO WHERE IN_ATIVO = 1', null, true);
	$parametros['OrderData']['MerchantId'] = $rs_gateway_pagamento['CD_GATEWAY_PAGAMENTO'];
	//--------------------

	$result = executeSQL($mainConnection, "INSERT INTO MW_ASSINATURA_HISTORICO (DT_PAGAMENTO) VALUES (GETDATE()); SELECT 'A'+CONVERT(VARCHAR, SCOPE_IDENTITY());");
	$order_id = getLastID($result);

	$parametros['OrderData']['OrderId'] = $order_id;

	//Dados cliente
	$parametros['CustomerData']['CustomerIdentity'] = $rs['CD_CPF'];// CPF ou ID?
	$parametros['CustomerData']['CustomerName'] = $rs['DS_NOME'] . ' ' . $rs['DS_SOBRENOME'];
	$parametros['CustomerData']['CustomerEmail'] = $rs['CD_EMAIL_LOGIN'];

	//Dados do cartão
	$PaymentDataCollection['CardHolder'] = $cartao_titular;
	$PaymentDataCollection['PaymentMethod'] = $meio_pagamento;
	$PaymentDataCollection['CardNumber'] = $cartao_numero;
	$PaymentDataCollection['CardExpirationDate'] = $cartao_validade;
	$PaymentDataCollection['CardSecurityCode'] = $cartao_cod_seguranca;
	$PaymentDataCollection['Currency'] = 'BRL';
	$PaymentDataCollection['Country'] = 'BRA';
	$PaymentDataCollection['ServiceTaxAmount'] = 0; // somente para IATA (International Air Transport Association)
	$PaymentDataCollection['TransactionType'] = 2;
	$PaymentDataCollection['NumberOfPayments'] = 1;
	$PaymentDataCollection['PaymentPlan'] = $PaymentDataCollection['NumberOfPayments'] > 1 ? 1 : 0;

	// 1 Pré-Autorização
	// 2 Captura Automática
	$PaymentDataCollection['TransactionType'] = ($cancelar_em_sucesso ? 1 : 2);

	//Dados do endereço de cobrança.
	$parametros['CustomerData']['CustomerAddressData']['Street'] = $rs['DS_ENDERECO'];
	$parametros['CustomerData']['CustomerAddressData']['Number'] = $rs['NR_ENDERECO'];
	$parametros['CustomerData']['CustomerAddressData']['Complement'] = $rs['DS_COMPL_ENDERECO'];
	$parametros['CustomerData']['CustomerAddressData']['District'] = $rs['DS_BAIRRO'];
	$parametros['CustomerData']['CustomerAddressData']['ZipCode'] = $rs['CD_CEP'];
	$parametros['CustomerData']['CustomerAddressData']['City'] = $rs['DS_CIDADE'];
	$parametros['CustomerData']['CustomerAddressData']['State'] = $rs['SG_ESTADO'];
	$parametros['CustomerData']['CustomerAddressData']['Country'] = 'Brasil';

	$total = $valor_pagar;

	$PaymentDataCollection['Amount'] = $total * 100;

	$parametros['PaymentDataCollection'] = array(new SoapVar($PaymentDataCollection, SOAP_ENC_ARRAY, 'CreditCardDataRequest', 'https://www.pagador.com.br/webservice/pagador', 'PaymentDataRequest'));

	$options = array(
	    //'local_cert' => file_get_contents('../settings/cert.pem'),
	    //'passphrase' => file_get_contents('cert.key'),
	    //'authentication' => SOAP_AUTHENTICATION_BASIC || SOAP_AUTHENTICATION_DIGEST
	    
	    'trace' => true,
	    'exceptions' => true,
	    'cache_wsdl' => WSDL_CACHE_NONE/*,
	    'proxy_host'     => ($_ENV['IS_TEST'] ? $proxy_homologacao['host'] : $proxy_producao['host']),
	    'proxy_port'     => ($_ENV['IS_TEST'] ? $proxy_homologacao['port'] : $proxy_producao['port'])*/
	);

	$descricao_erro = '';

	$url_braspag = $rs_gateway_pagamento['DS_URL'];


	// ALTERACAO DOS DADOS DO CARTAO PARA GRAVACAO DO LOG
	$parametrosLOG = array_merge(array(), $parametros);
	$PaymentDataCollectionLOG = array_merge(array(), $PaymentDataCollection);
	$PaymentDataCollectionLOG['CardNumber'] = substr($cartao_numero, 0, 6) . '******' . substr($cartao_numero, -4);
	$PaymentDataCollectionLOG['CardSecurityCode'] = '***';
	$parametrosLOG['PaymentDataCollection'] = array(new SoapVar($PaymentDataCollectionLOG, SOAP_ENC_ARRAY, 'CreditCardDataRequest', 'https://www.pagador.com.br/webservice/pagador', 'PaymentDataRequest'));

	// echo "<br><br><br><pre>";
	// var_dump(array('requestOriginal' => $parametros),
	//     array('requestMascarado' => $parametrosLOG));
	// echo "</pre>";
	// die(''.time());


	try {
	    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
	        array($id_cliente, json_encode(array('descricao' => '1. inicialização do pedido ' . $parametros['OrderData']['OrderId'], 'url' => $url_braspag)))
	    );

	    $client = @new SoapClient($url_braspag, $options);

	    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
	        array($id_cliente, json_encode(array('descricao' => '2. envio do pedido=' . $parametros['OrderData']['OrderId'], 'post' => $parametrosLOG)))
	    );
	    
	    $result = $client->AuthorizeTransaction(array('request' => $parametros));

	    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
	        array($id_cliente, json_encode(array('descricao' => '3. retorno do pedido=' . $parametros['OrderData']['OrderId'], 'post' => $result)))
	    );
	    
	} catch (SoapFault $e) {
	    $descricao_erro = $e->getMessage();
	} catch (Exception $e) {
	    $descricao_erro = $e->getMessage();
	}


	if ($cancelar_em_sucesso) {
		if ($result->AuthorizeTransactionResult->CorrelationId == $ri and $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status == '1') {
			cancelarPedido($result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId);
			$result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status = '0';
		} else {
	        $descricao_erro = "Transação não autorizada.";
		}
	}

	// echo "<pre>";
	// var_dump($client);
	// var_dump($result);
	// var_dump($descricao_erro);
	// echo "</pre>";
	// die(''.time());

	if ($descricao_erro == '') {
	    if (($result->AuthorizeTransactionResult->CorrelationId == $ri and $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status == '0')
	        or ($PaymentDataCollection['Amount'] == 0)) {

	        $query = "UPDATE MW_ASSINATURA_CLIENTE SET DT_PROXIMO_PAGAMENTO = dbo.GetProximaDataPagamento(GETDATE()) WHERE ID_ASSINATURA_CLIENTE = ?";
	        $params = array($_GET['id']);
	        executeSQL($mainConnection, $query, $params);

	        $rs = executeSQL($mainConnection, 'SELECT QT_BILHETE FROM MW_ASSINATURA WHERE ID_ASSINATURA = ?', array($id_assinatura), true);

			$query = "UPDATE MW_ASSINATURA_HISTORICO SET
                        ID_ASSINATURA_CLIENTE = ?,
                        DT_PAGAMENTO = GETDATE(),
                        VL_PAGAMENTO = ?,
                        ID_TRANSACTION_BRASPAG = ?,
                        ID_PEDIDO_IPAGARE = ?,
                        CD_NUMERO_AUTORIZACAO = ?,
                        CD_NUMERO_TRANSACAO = ?,
                        QT_BILHETE = ?
                    WHERE ID_ASSINATURA_HISTORICO = ?";
			$params = array(
	            $_GET['id'],
	            ($cancelar_em_sucesso ? 0 : $valor_pagar),
	            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId,
	            $result->AuthorizeTransactionResult->OrderData->BraspagOrderId,
	            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AuthorizationCode,
	            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AcquirerTransactionId,
            	$rs['QT_BILHETE'],
	            substr($order_id, 1)
	        );
        	executeSQL($mainConnection, $query, $params);

	        executeSQL($mainConnection, "EXEC PRC_REPOR_CUPONS_ASSINATURA ?", array($_GET['id']));

	        executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
		        array($id_cliente, json_encode(array('descricao' => '4. tudo ok pedido=' . $parametros['OrderData']['OrderId'])))
		    );

	        die("true");
	    } else {
	        // $descricao_erro = "Transação não autorizada.";
	    }

	    if (count(get_object_vars($result->AuthorizeTransactionResult->ErrorReportDataCollection)) > 0) {
	        include('errorMail.php');
	    }
	}

	$query = "UPDATE MW_ASSINATURA_CLIENTE SET IN_ATIVO = 0 WHERE ID_ASSINATURA_CLIENTE = ?";
    $params = array($_GET['id']);
    executeSQL($mainConnection, $query, $params);

    // apaga cupons restantes se a assinatura estiver inativa
	executeSQL($mainConnection, "EXEC PRC_REPOR_CUPONS_ASSINATURA ?", array($_GET['id']));

    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array($id_cliente, json_encode(array('descricao' => '4. falha pedido=' . $parametros['OrderData']['OrderId'])))
    );

	die('false');
}