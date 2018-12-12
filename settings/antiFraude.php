<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
// Polecat's Multi-dimensional array_replace function 
// Will take all data in second array and apply to first array leaving any non-corresponding values untouched and intact 
function polecat_array_replace( array &$array1, array &$array2 ) { 
    // This sub function is the iterator that will loop back on itself ad infinitum till it runs out of array dimensions 
    if(!function_exists('tier_parse')){ 
        function tier_parse(array &$t_array1, array&$t_array2) { 
            foreach ($t_array2 as $k2 => $v2) { 
                if (is_array($t_array2[$k2])) { 
                    tier_parse($t_array1[$k2], $t_array2[$k2]); 
                } else { 
                    $t_array1[$k2] = $t_array2[$k2]; 
                } 
            } 
            return $t_array1; 
        } 
    } 
    
    foreach ($array2 as $key => $val) { 
        if (is_array($array2[$key])) { 
            tier_parse($array1[$key], $array2[$key]); 
        } else { 
            $array1[$key] = $array2[$key]; 
        } 
    } 
    return $array1; 
} 

//function definition to convert array to xml
function array_to_xml($array, &$xml) {
    foreach($array as $key => $value) {
        if(is_array($value)) {
            if(!is_numeric($key)){
                $subnode = $xml->addChild("$key");
                array_to_xml($value, $subnode);
            }else{
                // $subnode = $xml->addChild("item$key");
                array_to_xml($value, $xml);
            }
        }else {
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

function verificarAntiFraude($id_pedido, $array_dados_extra = array()) {

	$wsdl_url = ($_ENV['IS_TEST'] ? "http://homologacao.clearsale.com.br/integracaov2/service.asmx?WSDL" : "http://integracao.clearsale.com.br/service.asmx?WSDL");
	$entityCode = ($_ENV['IS_TEST'] ? "A2150D50-C67F-4F3B-A675-CC79D89FD206" : "872A4AC5-9987-453F-A964-764EEF2C160B");


	session_start();
	$mainConnection = mainConnection();

	$query = "SELECT
					(SELECT COUNT(1) FROM MW_ITEM_PEDIDO_VENDA I WHERE I.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA) AS QT_TOTAL_ITENS,
					REPLACE(CONVERT(VARCHAR, P.DT_PEDIDO_VENDA, 120), ' ', 'T') AS DT_PEDIDO_VENDA,
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
					CONVERT(VARCHAR(10),DT_NASCIMENTO, 120) AS DT_NASCIMENTO,
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
					ISNULL(X.ID_CLEARSALE_BANDEIRA, 4) AS ID_CLEARSALE_BANDEIRA,

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

					C.ID_DOC_ESTRANGEIRO
				FROM MW_PEDIDO_VENDA P
				INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
				INNER JOIN MW_ESTADO E ON E.ID_ESTADO = C.ID_ESTADO
				LEFT JOIN MW_ESTADO E2 ON E2.ID_ESTADO = P.ID_ESTADO
				LEFT JOIN MW_CLEARSALE_BANDEIRA_MEIO X ON X.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
				WHERE P.ID_PEDIDO_VENDA = ?";

	$rs = executeSQL($mainConnection, $query, array($id_pedido), true);

	if ($rs['ID_DOC_ESTRANGEIRO'] != NULL) {
		return false;
	}

	foreach ($rs as $key => $value) {
		$rs[$key] = utf8_encode2($value);
	}

	$query = "SELECT
					I.ID_APRESENTACAO,
					E.ID_EVENTO,
					E.DS_EVENTO,
					CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 120)+'T'+REPLACE(A.HR_APRESENTACAO, 'H', ':')+':00' AS DT_APRESENTACAO,
					C.DS_NOME + ' ' + C.DS_SOBRENOME AS DS_NOME,
					C.CD_CPF
				FROM MW_PEDIDO_VENDA P
				INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
				INNER JOIN MW_ITEM_PEDIDO_VENDA I ON I.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
				WHERE P.ID_PEDIDO_VENDA = ?";
	$result = executeSQL($mainConnection, $query, array($id_pedido));

	$tickets_array = array();
	while ($rs2 = fetchResult($result)) {
		foreach ($rs2 as $key => $value) {
			$rs2[$key] = utf8_encode2($value);
		}

		$evento_info = getEvento($rs2['ID_EVENTO']);

		$tickets_array[] = array(
			'Ticket' => array(
				'Event' => array(
					'ID' => $rs2['ID_APRESENTACAO'],
					'Name' => $rs2['DS_EVENTO'],
					'Local' => utf8_encode2($evento_info['nome_teatro']),
					'Date' => $rs2['DT_APRESENTACAO']
				),
				'People' => array(
					'Person' => array(
						'Name' => $rs2['DS_NOME'],
						'LegalDocument' => $rs2['CD_CPF']
					)
				)
			)
		);
	}

	$array_dados = array('Orders' => array(
		'Order' => array(
			'ID' => $id_pedido,
			'FingerPrint' => array('SessionID' => session_id()),
			'Date' => $rs['DT_PEDIDO_VENDA'],
			'Email' => $rs['CD_EMAIL_LOGIN'],
			'ShippingPrice' => $rs['VL_FRETE'],
			'TotalItems' => $rs['VL_TOTAL_PEDIDO_VENDA'],
			'TotalOrder' => $rs['VL_TOTAL_PEDIDO_VENDA'],
			'QtyItems' => $rs['QT_TOTAL_ITENS'],
			'IP' => $rs['ID_IP'],
			'ShippingType' => ($rs['IN_RETIRA_ENTREGA'] == 'R' ? 13 : 1),
			'Status' => 0,
			'Origin' => (isset($_SESSION['operador']) ? 'televendas' : 'site'),
			'Product' => 16,
			'BillingData' => array(
				'ID' => $rs['ID_CLIENTE'],
				'Type' => 1,
				'LegalDocument1' => $rs['CD_CPF'],
				'LegalDocument2' => $rs['CD_RG'],
				'Name' => $rs['DS_NOME'] . ' ' . $rs['DS_SOBRENOME'],
				'BirthDate' => $rs['DT_NASCIMENTO'].'T00:00:00',
				'Email' => $rs['CD_EMAIL_LOGIN'],
				'Gender' => $rs['IN_SEXO'],
				'Address' => array(
					'Street' => $rs['DS_ENDERECO'],
					'Number' => $rs['NR_ENDERECO'],
					'Comp' => $rs['DS_COMPL_ENDERECO'],
					'County' => $rs['DS_BAIRRO'],
					'City' => $rs['DS_CIDADE'],
					'State' => $rs['SG_ESTADO'],
					'Country' => 'Brasil',
					'ZipCode' => $rs['CD_CEP']
				),
				'Phones' => array()
			),
			'Payments' => array(
				'Payment' => array(
					'Date' => $rs['DT_PEDIDO_VENDA'],
					'Amount' => $rs['VL_TOTAL_PEDIDO_VENDA'],
					'PaymentTypeID' => 1,
					'QtyInstallments' => $rs['NR_PARCELAS_PGTO'],
					'Interest' => 0,
					'InterestValue' => 0,
					'CardNumber' => $rs['CD_BIN_CARTAO'],
					'CardBin' => substr($rs['CD_BIN_CARTAO'], 0, 6),
					'CardEndNumber' => substr($rs['CD_BIN_CARTAO'], -4),
					'CardType' => $rs['ID_CLEARSALE_BANDEIRA'],
					'CardExpirationDate' => '',
					'Name' => '',
					'Nsu' => ''
				)
			),
			'Tickets' => array($tickets_array)
		)
	));

	if ($rs['DS_DDD_TELEFONE'] and $rs['DS_TELEFONE']) {
		$array_dados['Orders']['Order']['BillingData']['Phones'][] = array(
			'Phone' => array(
				'Type' => 1,
				'DDD' => $rs['DS_DDD_TELEFONE'],
				'Number' => $rs['DS_TELEFONE']
			)
		);
	}

	if ($rs['DS_DDD_CELULAR'] and $rs['DS_CELULAR']) {
		$array_dados['Orders']['Order']['BillingData']['Phones'][] = array(
			'Phone' => array(
				'Type' => 6,
				'DDD' => $rs['DS_DDD_CELULAR'],
				'Number' => $rs['DS_CELULAR']
			)
		);
	}

	if ($rs['IN_RETIRA_ENTREGA'] == 'E') {
		$array_dados['Orders']['Order']['ShippingData'] = array(
			'ID' => $rs['ID_CLIENTE'],
			'Type' => 1,
			'LegalDocument1' => $rs['CD_CPF'],
			'LegalDocument2' => $rs['CD_RG'],
			'Name' => ($rs['DS_EMAIL_VOUCHER'] ? $rs['NM_CLIENTE_VOUCHER'] : $rs['DS_CUIDADOS_DE']),
			'Email' => ($rs['DS_EMAIL_VOUCHER'] ? $rs['DS_EMAIL_VOUCHER'] : $rs['CD_EMAIL_LOGIN']),
			'Address' => array(
				'Street' => $rs['DS_ENDERECO_ENTREGA'],
				'Number' => $rs['NR_ENDERECO_ENTREGA'],
				'Comp' => $rs['DS_COMPL_ENDERECO_ENTREGA'],
				'County' => $rs['DS_BAIRRO_ENTREGA'],
				'City' => $rs['DS_CIDADE_ENTREGA'],
				'State' => $rs['SG_ESTADO_ENTREGA'],
				'Country' => 'Brasil',
				'ZipCode' => $rs['CD_CEP_ENTREGA']
			),
			'Phones' => array(
				array(
					'Phone' => array(
						'Type' => 1,
						'DDD' => $rs['DS_DDD_TELEFONE'],
						'Number' => $rs['DS_TELEFONE']
					)
				),
				array(
					'Phone' => array(
						'Type' => 6,
						'DDD' => $rs['DS_DDD_CELULAR'],
						'Number' => $rs['DS_CELULAR']
					)
				)
			)
		);
	} else {
		$array_dados['Orders']['Order']['ShippingData'] = $array_dados['Orders']['Order']['BillingData'];
	}

	polecat_array_replace($array_dados, $array_dados_extra);

	$xmlObj = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\" ?><ClearSale></ClearSale>");

	array_to_xml($array_dados, $xmlObj);

	$xml = $xmlObj->asXML();

	$options = array(
	    'trace' => true,
	    'exceptions' => true,
	    'cache_wsdl' => WSDL_CACHE_NONE/*,
	    'proxy_host'     => ($_ENV['IS_TEST'] ? $proxy_homologacao['host'] : $proxy_producao['host']),
	    'proxy_port'     => ($_ENV['IS_TEST'] ? $proxy_homologacao['port'] : $proxy_producao['port'])*/
	);

	$tentativas = 3;

	do {
		try {
			$client = @new SoapClient($wsdl_url, $options);
			$result = $client->SendOrders(array('entityCode' => $entityCode, 'xml' => $xml));
			$xml_response = new SimpleXMLElement(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $result->SendOrdersResult));
		} catch (SoapFault $e) {
			$descricao_erro = $e->getMessage();
		} catch (Exception $e) {
			$descricao_erro = $e->getMessage();
		}

		executeSQL($mainConnection, 'INSERT INTO MW_PEDIDO_CLEARSALE VALUES (?, GETDATE(), ?)', array($id_pedido, (isset($descricao_erro) ? $descricao_erro : $xml_response->asXML())));

		if (isset($descricao_erro)) return false;

		// transacao concluida
		if ($xml_response->StatusCode == '00') {
			if (in_array($xml_response->Orders->Order->Status, array('APA', 'APM', 'APQ'))) {
				return true;
			}
		} elseif ($xml_response->StatusCode == '05') {

			try {
				$result = $client->GetOrderStatus(array('entityCode' => $entityCode, 'orderID' => $id_pedido));
				$xml_response = new SimpleXMLElement(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $result->GetOrderStatusResult));
			} catch (SoapFault $e) {
				$descricao_erro = $e->getMessage();
			} catch (Exception $e) {
				$descricao_erro = $e->getMessage();
			}

			executeSQL($mainConnection, 'INSERT INTO MW_PEDIDO_CLEARSALE VALUES (?, GETDATE(), ?)', array($id_pedido, (isset($descricao_erro) ? $descricao_erro : $xml_response->asXML())));

			if (isset($descricao_erro)) return false;

			if (in_array($xml_response->Orders->Order->Status, array('APA', 'APM', 'APQ'))) {
				return true;
			}
		}

		$tentativas--;

	} while (in_array($xml_response->StatusCode, array('02','03','04','06','8')) and $tentativas > 0);

	return false;
}

function cancelarPedido($braspagTransactionId) {
	global $descricao_erro;
	
	$mainConnection = mainConnection();

	//RequestID
    $ri = md5(time());
    $ri = substr($ri, 0, 8) . '-' . substr($ri, 8, 4) . '-' . substr($ri, 12, 4) . '-' . substr($ri, 16, 4) . '-' . substr($ri, -12);

	$parametros = array();
	$parametros['RequestId'] = $ri;
    $parametros['Version'] = '1.0';
    $parametros['TransactionDataCollection']['TransactionDataRequest']['BraspagTransactionId'] = $braspagTransactionId;
    $parametros['TransactionDataCollection']['TransactionDataRequest']['Amount'] = 0;

    $options = array(
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE
    );

    $rs_gateway_pagamento = executeSQL($mainConnection, 'SELECT ID_GATEWAY_PAGAMENTO, DS_GATEWAY_PAGAMENTO, CD_GATEWAY_PAGAMENTO, DS_URL FROM MW_GATEWAY_PAGAMENTO WHERE IN_ATIVO = 1', array(), true);

    $url_braspag = $rs_gateway_pagamento['DS_URL'];
    $parametros['MerchantId'] = $rs_gateway_pagamento['CD_GATEWAY_PAGAMENTO'];

    try {
        $client = @new SoapClient($url_braspag, $options);

        $tentativas = 3;
        
        do {
	        $result = $client->VoidCreditCardTransaction(array('request' => $parametros));
	        $response = $result->VoidCreditCardTransactionResult;
	        $tentativas--;
	    } while ($response->TransactionDataCollection->TransactionDataResponse->Status != '0' and $tentativas > 0);

    } catch (SoapFault $e) {
		$descricao_erro = $e->getMessage();
	} catch (Exception $e) {
		$descricao_erro = $e->getMessage();
	}

	return ($response->TransactionDataCollection->TransactionDataResponse->Status == '0');
}

function confirmarPedido($braspagTransactionId) {
	global $descricao_erro;
	
	$mainConnection = mainConnection();

	//RequestID
    $ri = md5(time());
    $ri = substr($ri, 0, 8) . '-' . substr($ri, 8, 4) . '-' . substr($ri, 12, 4) . '-' . substr($ri, 16, 4) . '-' . substr($ri, -12);

	$parametros = array();
	$parametros['RequestId'] = $ri;
    $parametros['Version'] = '1.0';
    $parametros['TransactionDataCollection']['TransactionDataRequest']['BraspagTransactionId'] = $braspagTransactionId;
    $parametros['TransactionDataCollection']['TransactionDataRequest']['Amount'] = 0;
    $parametros['TransactionDataCollection']['TransactionDataRequest']['ServiceTaxAmount'] = 0;

    $options = array(
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE
    );

    $rs_gateway_pagamento = executeSQL($mainConnection, 'SELECT ID_GATEWAY_PAGAMENTO, DS_GATEWAY_PAGAMENTO, CD_GATEWAY_PAGAMENTO, DS_URL FROM MW_GATEWAY_PAGAMENTO WHERE IN_ATIVO = 1', array(), true);

    $url_braspag = $rs_gateway_pagamento['DS_URL'];
    $parametros['MerchantId'] = $rs_gateway_pagamento['CD_GATEWAY_PAGAMENTO'];

    try {
        $client = @new SoapClient($url_braspag, $options);

        $tentativas = 3;
        
        do {
	        $result = $client->CaptureCreditCardTransaction(array('request' => $parametros));
	        $response = $result->CaptureCreditCardTransactionResult;
	        $tentativas--;
	    } while ($response->TransactionDataCollection->TransactionDataResponse->Status != '0' and $tentativas > 0);

    } catch (SoapFault $e) {
		$descricao_erro = $e->getMessage();
	} catch (Exception $e) {
		$descricao_erro = $e->getMessage();
	}

	return ($response->TransactionDataCollection->TransactionDataResponse->Status == '0');
}

function obterDadosPedidoPago($orderId) {
	global $descricao_erro;
	
	$mainConnection = mainConnection();

	//RequestID
    $ri = md5(time());
    $ri = substr($ri, 0, 8) . '-' . substr($ri, 8, 4) . '-' . substr($ri, 12, 4) . '-' . substr($ri, 16, 4) . '-' . substr($ri, -12);

	$parametros = array();
	$parametros['RequestId'] = $ri;
    $parametros['Version'] = '1.0';

    $options = array(
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE
    );

    $rs_gateway_pagamento = executeSQL($mainConnection, 'SELECT ID_GATEWAY_PAGAMENTO, DS_GATEWAY_PAGAMENTO, CD_GATEWAY_PAGAMENTO, DS_URL FROM MW_GATEWAY_PAGAMENTO WHERE IN_ATIVO = 1', array(), true);

    $url_braspag = ($_ENV['IS_TEST'] ? 'https://homologacao.pagador.com.br/services/pagadorQuery.asmx?WSDL' : 'https://transaction.pagador.com.br/services/pagadorQuery.asmx?WSDL');
    $parametros['MerchantId'] = $rs_gateway_pagamento['CD_GATEWAY_PAGAMENTO'];
    $parametros['OrderId'] = "$orderId";

    try {
        $client = @new SoapClient($url_braspag, $options);

        $tentativas = 3;
        
        do {
	        $result = $client->GetOrderIdData(array('orderIdDataRequest' => $parametros));
	        $response = $result->GetOrderIdDataResult;
	        
	        if (count($response->OrderIdDataCollection->OrderIdTransactionResponse) > 1)
	        	$braspagOrderIds = $response->OrderIdDataCollection->OrderIdTransactionResponse;
	        else
	        	$braspagOrderIds = array($response->OrderIdDataCollection->OrderIdTransactionResponse);
	        
	        $tentativas--;
	    } while (empty($braspagOrderIds) and $tentativas > 0);

    } catch (SoapFault $e) {
		$descricao_erro = $e->getMessage();
	} catch (Exception $e) {
		$descricao_erro = $e->getMessage();
	}

	foreach($braspagOrderIds as $obj) {

		$braspagOrderId = $obj->BraspagOrderId;

		//RequestID
	    $ri = md5(time());
	    $ri = substr($ri, 0, 8) . '-' . substr($ri, 8, 4) . '-' . substr($ri, 12, 4) . '-' . substr($ri, 16, 4) . '-' . substr($ri, -12);

		$parametros = array();
		$parametros['RequestId'] = $ri;
	    $parametros['Version'] = '1.0';

	    $options = array(
	        'trace' => true,
	        'exceptions' => true,
	        'cache_wsdl' => WSDL_CACHE_NONE
	    );

	    $parametros['MerchantId'] = $rs_gateway_pagamento['CD_GATEWAY_PAGAMENTO'];
	    $parametros['BraspagOrderId'] = $braspagOrderId;

	    try {
	        $client = @new SoapClient($url_braspag, $options);

	        $tentativas = 3;
	        
	        do {
		        $result = $client->GetOrderData(array('orderDataRequest' => $parametros));
		        $response = $result->GetOrderDataResult;
		        $tentativas--;
		    } while (!isset($response->TransactionDataCollection->OrderTransactionDataResponse) and $tentativas > 0);

	    } catch (SoapFault $e) {
			$descricao_erro = $e->getMessage();
		} catch (Exception $e) {
			$descricao_erro = $e->getMessage();
		}

		if ($descricao_erro == '') {

			$rs = executeSQL($mainConnection, 'SELECT VL_TOTAL_PEDIDO_VENDA FROM MW_PEDIDO_VENDA WHERE ID_PEDIDO_VENDA = ?', array($orderId), true);
			$valor_pedido = $rs[0];

			$is_pedido_pago = false;
			$is_pedido_cancelado = false;

			if (count($response->TransactionDataCollection->OrderTransactionDataResponse) > 1) {
				foreach($response->TransactionDataCollection->OrderTransactionDataResponse as $transaction) {
					if ($transaction->Status == '1') {
						$is_pedido_pago = true;
						$return_transaction = $transaction;
						$return_transaction->BraspagOrderId = $braspagOrderId;

						break 2;

					} else if(in_array($transaction->Status, array('4', '5', '7'))) {
						$is_pedido_cancelado = true;
					}
				}
			} else {
				if ($response->TransactionDataCollection->OrderTransactionDataResponse->Status == '1') {
					$is_pedido_pago = true;
					$return_transaction = $response->TransactionDataCollection->OrderTransactionDataResponse;
					$return_transaction->BraspagOrderId = $braspagOrderId;
					break;

				} else if(in_array($response->TransactionDataCollection->OrderTransactionDataResponse->Status, array('4', '5', '7'))) {
					$is_pedido_cancelado = true;
				}
			}
		}
	}

	return (($is_pedido_pago and !$is_pedido_cancelado and $valor_pedido == ($return_transaction->Amount/100)) ? $return_transaction : false);
}