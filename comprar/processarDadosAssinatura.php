<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
require_once('../settings/Log.class.php');

require('acessoLogado.php');

require_once('../settings/antiFraude.php');
require_once('../settings/Cypher.class.php');

require_once('../settings/brandcaptchalib.php');

$resp = brandcaptcha_check_answer(
            $recaptcha['private_key'],
            $_SERVER["HTTP_X_FORWARDED_FOR"],
            $_POST["brand_cap_challenge"],
            $_POST["brand_cap_answer"]
        );

if (!$_ENV['IS_TEST'] and !isset($_SESSION['operador'])) {
    if (!$resp->is_valid) {
        // set the error code so that we can display it
        $error = $resp->error;
        echo "Entre com a informação solicitada no campo Autenticidade.";
        exit();
    }
}

// não passar código de cartão nulo
if ($_POST['codCartao'] == '') {
    echo "Nenhuma forma de pagamento selecionada.";
    die();
}

// condicao que para uma tentativa de usar o cartao de teste no ambiente de producao
if (!$_ENV['IS_TEST'] and $_POST['codCartao'] == 997) {
    echo "Nice try...";
    die();
}

$_POST['numCartao'] = preg_replace("/[^0-9]/", "", $_POST['numCartao']);

$mainConnection = mainConnection();

require('antiFraude.php');

$query = 'SELECT
            C.ID_CLIENTE,C.DS_NOME,C.DS_SOBRENOME,C.DS_DDD_TELEFONE,C.DS_TELEFONE,C.DS_DDD_CELULAR,C.DS_CELULAR,C.CD_CPF,C.DS_ENDERECO,C.NR_ENDERECO,C.DS_COMPL_ENDERECO,C.DS_BAIRRO,C.DS_CIDADE,C.CD_CEP,C.CD_EMAIL_LOGIN,C.ID_ESTADO,E.SG_ESTADO
            FROM MW_CLIENTE C
            LEFT JOIN MW_ESTADO E ON E.ID_ESTADO = C.ID_ESTADO
            WHERE C.ID_CLIENTE = ?';
$params = array($_SESSION['user']);
$rs = executeSQL($mainConnection, $query, $params, true);

foreach($rs as $key => $val) {
        $rs[$key] = utf8_encode2($val);
}

$valor_pagar = getPrimeiroValorAssinatura($_SESSION['user'], $_POST['id']);

if ($valor_pagar == 0) {
	$valor_pagar = 1.00;
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

if (!isset($_SESSION['order_id'])) {
	$result = executeSQL($mainConnection, "INSERT INTO MW_ASSINATURA_HISTORICO (DT_PAGAMENTO) VALUES (GETDATE()); SELECT 'A'+CONVERT(VARCHAR, SCOPE_IDENTITY());");
	$_SESSION['order_id'] = getLastID($result);
}

$parametros['OrderData']['OrderId'] = $_SESSION['order_id'];

if (isset($_SESSION['id_braspag'])) {
    $parametros['OrderData']['BraspagOrderId'] = $_SESSION['id_braspag'];
}

//Dados cliente
$parametros['CustomerData']['CustomerIdentity'] = $rs['CD_CPF'];// CPF ou ID?
$parametros['CustomerData']['CustomerName'] = $rs['DS_NOME'] . ' ' . $rs['DS_SOBRENOME'];
$parametros['CustomerData']['CustomerEmail'] = $rs['CD_EMAIL_LOGIN'];

//Dados do cartão
$PaymentDataCollection['CardHolder'] = $_POST['nomeCartao'];
$PaymentDataCollection['PaymentMethod'] = $_POST['codCartao'];
$PaymentDataCollection['CardNumber'] = $_POST['numCartao'];
$PaymentDataCollection['CardExpirationDate'] = $_POST['validadeMes'] . '/' . $_POST['validadeAno'];
$PaymentDataCollection['CardSecurityCode'] = $_POST['codSeguranca'];
$PaymentDataCollection['Currency'] = 'BRL';
$PaymentDataCollection['Country'] = 'BRA';
$PaymentDataCollection['ServiceTaxAmount'] = 0; // somente para IATA (International Air Transport Association)
$PaymentDataCollection['TransactionType'] = 2;
$PaymentDataCollection['NumberOfPayments'] = 1;
$PaymentDataCollection['PaymentPlan'] = $PaymentDataCollection['NumberOfPayments'] > 1 ? 1 : 0;

// 1 Pré-Autorização
// 2 Captura Automática
$PaymentDataCollection['TransactionType'] = 1;

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
$PaymentDataCollectionLOG['CardNumber'] = substr($_POST['numCartao'], 0, 6) . '******' . substr($_POST['numCartao'], -4);
$PaymentDataCollectionLOG['CardSecurityCode'] = '***';
$parametrosLOG['PaymentDataCollection'] = array(new SoapVar($PaymentDataCollectionLOG, SOAP_ENC_ARRAY, 'CreditCardDataRequest', 'https://www.pagador.com.br/webservice/pagador', 'PaymentDataRequest'));

// echo "<br><br><br><pre>";
// var_dump(array('requestOriginal' => $parametros),
//     array('requestMascarado' => $parametrosLOG));
// echo "</pre>";
// die(''.time());


try {
    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array($_SESSION['user'], json_encode(array('descricao' => '3. inicialização do pedido ' . $parametros['OrderData']['OrderId'], 'url' => $url_braspag)))
    );

    $client = @new SoapClient($url_braspag, $options);

    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array($_SESSION['user'], json_encode(array('descricao' => '4. envio do pedido=' . $parametros['OrderData']['OrderId'], 'post' => $parametrosLOG)))
    );
    
    $result = $client->AuthorizeTransaction(array('request' => $parametros));

    executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
        array($_SESSION['user'], json_encode(array('descricao' => '5. retorno do pedido=' . $parametros['OrderData']['OrderId'], 'post' => $result)))
    );
    
} catch (SoapFault $e) {
    $descricao_erro = $e->getMessage();
} catch (Exception $e) {
    $descricao_erro = $e->getMessage();
}


if ($result->AuthorizeTransactionResult->CorrelationId == $ri and $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status == '1') {
	if ($cancelar_em_sucesso) {
		cancelarPedido($result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId);
		$result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status = '0';
	} else {
	    if (confirmarPedido($result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId)) {
	        $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status = '0';
	    } else {
	        cancelarPedido($result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId);

	        echo "Transação não autorizada.";
	        die();
	    }
	}
}

// echo "<pre>";
// var_dump($client);
// var_dump($result);
// var_dump($descricao_erro);
// echo "</pre>";
// die(''.time());

if ($descricao_erro == '') {
    $_SESSION['id_braspag'] = $result->AuthorizeTransactionResult->OrderData->BraspagOrderId;

    
    if ($result->AuthorizeTransactionResult->ErrorReportDataCollection->ErrorReportDataResponse->ErrorCode == '135') {
        $dados = obterDadosPedidoPago($parametros['OrderData']['OrderId']);

        if ($dados !== false) {
            $result->AuthorizeTransactionResult->OrderData->BraspagOrderId = $dados->BraspagOrderId;
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId = $dados->BraspagTransactionId;
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AcquirerTransactionId = $dados->AcquirerTransactionId;
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AuthorizationCode = $dados->AuthorizationCode;
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->PaymentMethod = $dados->PaymentMethod;

            $result->AuthorizeTransactionResult->CorrelationId = $ri;
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status = '0';
        }

        // email temporario para checar novo tratamento de erro (nao é possivel forcar o erro em homologacao)
        ob_start();
        echo "[ErrorCode] => 135<br/>[ErrorMessage] => OrderId was already registered<br/><br/>";
        echo "Não é um erro grave. Apenas checar os dados abaixo:<br/><br/>";
        echo "<pre>"; var_dump($dados); echo "</pre>";
        $message = ob_get_clean();

        sendErrorMail('Erro no Sistema - assinatura', $message);
    }

    if (($result->AuthorizeTransactionResult->CorrelationId == $ri and $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->Status == '0')
        or ($PaymentDataCollection['Amount'] == 0)) {

        $query = 'SELECT ID_MEIO_PAGAMENTO
                     FROM MW_MEIO_PAGAMENTO MP
                     WHERE CD_MEIO_PAGAMENTO = ?';
        $params = array($_POST['codCartao']);
        $id_meio_pagamento = executeSQL($mainConnection, $query, $params, true);
        $id_meio_pagamento = $id_meio_pagamento['ID_MEIO_PAGAMENTO'];

        $cipher = new Cipher('1ngr3ss0s');

		$query = "INSERT INTO MW_DADOS_CARTAO (ID_CLIENTE, ID_MEIO_PAGAMENTO, DS_NOME_TITULAR, CD_NUMERO_CARTAO, CD_CODIGO_SEGURANCA, DT_VALIDADE) VALUES (?, ?, ?, ?, ?, ?); SELECT SCOPE_IDENTITY();";
		$params = array($_SESSION['user'], $id_meio_pagamento, $cipher->encrypt($_POST['nomeCartao']), $cipher->encrypt($_POST['numCartao']), $cipher->encrypt($_POST['codSeguranca']), $cipher->encrypt($_POST['validadeMes'].'/'.$_POST['validadeAno']));
		$result2 = executeSQL($mainConnection, $query, $params);
		$id_dados_cartao = getLastID($result2);

        $query = "INSERT INTO MW_ASSINATURA_CLIENTE (ID_ASSINATURA, ID_CLIENTE, ID_DADOS_CARTAO, DT_PROXIMO_PAGAMENTO, DT_COMPRA, IN_ATIVO, ID_USUARIO_CALLCENTER) VALUES (?, ?, ?, dbo.GetProximaDataPagamento(GETDATE()), GETDATE(), 1, ?); SELECT SCOPE_IDENTITY();";
        $params = array($_POST['id'], $_SESSION['user'], $id_dados_cartao, $_SESSION['operador']);
        $result2 = executeSQL($mainConnection, $query, $params);
		$id_assinatura_cliente = getLastID($result2);

        $rs = executeSQL($mainConnection, 'SELECT DS_ASSINATURA, QT_BILHETE FROM MW_ASSINATURA WHERE ID_ASSINATURA = ?', array($_POST['id']), true);

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
            $id_assinatura_cliente,
            ($cancelar_em_sucesso ? 0 : $valor_pagar),
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId,
            $result->AuthorizeTransactionResult->OrderData->BraspagOrderId,
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AuthorizationCode,
            $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AcquirerTransactionId,
            $rs['QT_BILHETE'],
            substr($_SESSION['order_id'], 1)
        );
        executeSQL($mainConnection, $query, $params);

        executeSQL($mainConnection, "EXEC PRC_REPOR_CUPONS_ASSINATURA ?", array($id_assinatura_cliente));

        // -=========== envio do email de sucesso ===========-

        $dados_pedido['assinatura'] = true;
        $dados_pedido['codigo_pedido'] = 'A'.$_SESSION['order_id'];
        $dados_pedido['data'] = '';
        $dados_pedido['total'] = '';

        $dados_pedido['evento'] = $rs['DS_ASSINATURA'];
        $dados_pedido['endereco'] = formatCPF($dadosExtrasEmail['cpf_cnpj_cliente']);
        $dados_pedido['nome_teatro'] = $parametros['CustomerData']['CustomerName'];
        $dados_pedido['horario'] = '';

        $dados_pedido['barcode'] = $dadosExtrasEmail['cpf_cnpj_cliente'];
        $dados_pedido['local_bilhete'] = '';
        $dados_pedido['tipo_bilhete'] = '';
        $dados_pedido['preco_bilhete'] = '';
        $dados_pedido['servico_bilhete'] = '';
        $dados_pedido['total_bilhete'] = '';

        $pkpass_url = getPKPass($dados_pedido);

        require_once('../settings/Template.class.php');
        $tpl = new Template('./templates/emailAssinatura.html');

        $tpl->nome_cliente = $parametros['CustomerData']['CustomerName'];
        $tpl->quantidade_ingressos = $rs['QT_BILHETE'];
        $tpl->pkpass_url = $pkpass_url;
        $tpl->nome_assinatura = $rs['DS_ASSINATURA'];

        ob_start();
        $tpl->show();
        $message = ob_get_clean();

        $subject = 'Assinatura - Pedido '.$_SESSION['order_id'];
        $namefrom = strtoupper($rs['DS_ASSINATURA']);
        $from = 'assinantea@siscompre.com';

        authSendEmail($from, $namefrom, $parametros['CustomerData']['CustomerEmail'], $parametros['CustomerData']['CustomerName'], $subject, $message, array(), array(), 'utf-8');

        die("redirect.php?redirect=assinatura_ok.php?pedido=".$_SESSION['order_id']);
    } else {
        $descricao_erro = "Transação não autorizada.";
    }

    if (count(get_object_vars($result->AuthorizeTransactionResult->ErrorReportDataCollection)) > 0) {
        include('errorMail.php');
    }
}

echo $descricao_erro;
die();