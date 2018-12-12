<?php
require_once('../settings/functions.php');
require_once('../log4php/log.php');
require_once('../settings/split/split_config.php');
require_once('../settings/split/split_functions.php');

// if ($_ENV['IS_TEST']) {
// 	$url_ws  = "https://www.ti-pagos.com/bridgeservices/";
// 	$idLoja = "7309"; 
// 	$keyLoja = "49994822278418282883";
// 	$codProduto = "47";

// } else {
// 	$url_ws  = "https://www.ti-pagos.com/bridgeservices/";
// 	$idLoja = "7922"; 
// 	$keyLoja = "88281288497982783035";
// 	$codProduto = "55";
// }

function GUIDv4($trim = true)
{
    // Windows
    if (function_exists('com_create_guid') === true) {
        if ($trim === true)
            return trim(com_create_guid(), '{}');
        else
            return com_create_guid();
    }

    // OSX/Linux
    if (function_exists('openssl_random_pseudo_bytes') === true) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Fallback (PHP 4.2+)
    mt_srand((double)microtime() * 10000);
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);                  // "-"
    $lbrace = $trim ? "" : chr(123);    // "{"
    $rbrace = $trim ? "" : chr(125);    // "}"
    $guidv4 = $lbrace.
              substr($charid,  0,  8).$hyphen.
              substr($charid,  8,  4).$hyphen.
              substr($charid, 12,  4).$hyphen.
              substr($charid, 16,  4).$hyphen.
              substr($charid, 20, 12).
              $rbrace;
    return $guidv4;
}

function pagarPedidoTiPagos($id_pedido, $dados_extra) {
	global $gw_tipagos;

	sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Gerando pedido no tipagos.','',0);
	$gw_tipagos = configureSplit("tipagos");

	$url_ws = $gw_tipagos["url_ws"];
	$idLoja = $gw_tipagos["idLoja"];
	$keyLoja = $gw_tipagos["keyLoja"];
	$codProduto = $gw_tipagos["codProduto"];

	sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Informações do TiPagos.',print_r($gw_tipagos,true),0);


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
			CONVERT(VARCHAR(10),DT_NASCIMENTO, 110) AS DT_NASCIMENTO,
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

			$nCartao = str_replace("-", "", $dados_extra['numCartao']);
			$dadosCartao = $dados_extra['nomeCartao'].";".$nCartao.";".$dados_extra['validadeAno'].$dados_extra['validadeMes'];

			$valorTotal = str_replace(',', '', $rs['VL_TOTAL_PEDIDO_VENDA']);
			$valorTotal = str_replace('.', '', $rs['VL_TOTAL_PEDIDO_VENDA']);

			if($dados_extra['parcelas'] > 1){
				$formaPagamento = "2";
			} else {
				$formaPagamento = "1";
			}

			$dados = array();

			sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Recuperando split.','',0);

			$split = getSplit("tipagos", $id_pedido, "web", "credit_card", $valorTotal);

			sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Informações do split.',print_r($split,true),0);

			sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Informações do dadosCartao.',print_r($dadosCartao,true),0);

			sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Informações do dados_extra.',print_r($dados_extra,true),0);

			$guid = GUIDv4();

			if ($split == null) {
				$dados = array("header" => array("idLoja"=>$idLoja, 
										"keyLoja"=>$keyLoja,
										"codProduto"=>$codProduto),
					"tipoCapturaCliente"=>"3",
					"dadosCliente"=>$dadosCartao,
					"codSeguranca"=>$dados_extra['codSeguranca'],
					"valor"=>$valorTotal,
					"formaPagamento"=>$formaPagamento,
					"qtdeParcelas"=>$dados_extra['parcelas'],
					"transacaoCapturada"=>true,
					"descricaoPedido"=>$id_pedido, 
					"nsuTransacao"=>preg_replace('/\{|\}|\-/', "", $guid)
				);
			}
			else {
				$dados = array("header" => array("idLoja"=>$idLoja, 
										"keyLoja"=>$keyLoja,
										"codProduto"=>$codProduto),
					"tipoCapturaCliente"=>"3",
					"dadosCliente"=>$dadosCartao,
					"codSeguranca"=>$dados_extra['codSeguranca'],
					"valor"=>$valorTotal,
					"formaPagamento"=>$formaPagamento,
					"qtdeParcelas"=>$dados_extra['parcelas'],
					"transacaoCapturada"=>true,
					"descricaoPedido"=>$id_pedido, 
					"nsuTransacao"=>preg_replace('/\{|\}|\-/', "", $guid),
					"dadosSplit" => $split
				);
			}

			sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Informações do dados.',print_r($dados,true),0);

			$post_data = json_encode($dados);

			sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Informações do post.',print_r($post_data,true),0);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json')); 
			curl_setopt($ch, CURLOPT_URL, $url_ws."comprar");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$server_output = curl_exec($ch);
			curl_close($ch);

			$resp = json_decode($server_output, true);

			sale_trace($_SESSION['user'],$id_pedido,NULL,NULL,NULL,NULL,session_id(),'tipagos_functions.php','Resposta TIPagos.',print_r($resp, true),0);

			if($resp['retorno']['rc'] == '0'){
				$response = array('success' => true, 'transaction' => $resp);

				$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
				$params = array($id_pedido, $response['transaction']['retorno']['rc'], base64_encode(serialize($response['transaction'])));
				executeSQL($mainConnection, $query, $params);
			} else {
				$response = array('success' => false, 'error' => tratarErroTiPagos($resp['retorno']['rc']));
			}

		return $response;
}

function tratarErroTiPagos($id) {
	switch ($id) {
		case '1': $msg_nova = 'Erro na Aplicação.'; break;
		case '2': $msg_nova = 'Id de conexão inválido.'; break;
		case '7': $msg_nova = 'Tipo de captura do cliente não suportado.'; break;
		case '8': $msg_nova = 'Transação não autorizada.'; break;
		case '9': $msg_nova = 'Erro na validação da Loja / Terminal.'; break;
		case '10': $msg_nova = 'Loja / Canal não encontrado.'; break;
		case '11': $msg_nova = 'A Loja não está ativa.'; break;
		case '12': $msg_nova = 'Key da loja inválida.'; break;
		case '13': $msg_nova = 'Terminal não encontrado'; break;
		case '14': $msg_nova = 'Terminal não está ativo.'; break;
		case '16': $msg_nova = 'A loja não está habilitada para a bandeira / tipo de compra recebidos.'; break;
		case '17': $msg_nova = 'Não foram encontrados adquirentes cadastrados para a loja.'; break;
		case '18': $msg_nova = 'O adquirente cadastrado para a transação não é suportado por esta versão.'; break;
		case '19': $msg_nova = 'Transação não localizada para o NSU informado.'; break;
		case '20': $msg_nova = 'Erro ao enviar transação para o adquirente.'; break;
		case '23': $msg_nova = 'Tipo de parcelamento inválido.'; break;
		case '30': $msg_nova = 'Valor de parcelamento inferior ao mínimo permitido.'; break;
		case '34': $msg_nova = 'Dados do cliente informados inválidos.'; break;
		case '42': $msg_nova = 'Transação negada.'; break;
		case '55': $msg_nova = 'Bandeira ou cartão recebido não suportado.'; break;
		case '56': $msg_nova = 'Não foi possível buscar o plano para o tipo de compra enviado.'; break;
		case '57': $msg_nova = 'Tipo de compra enviado não parametrizado para a loja.'; break;
		case '58': $msg_nova = 'Repasse já efetuado para a transação enviada. Não será possível concluir a operação.'; break;
		case '59': $msg_nova = 'Dados do cliente insuficientes para o tipo de adquirente associado a loja.'; break;
		case '62': $msg_nova = 'Bandeira não disponível para o plano / produto.'; break;
		case '65': $msg_nova = 'Tipo de captura do cliente não suportado.'; break;
		case '66': $msg_nova = 'Data de expiração do cartão não informada.'; break;
		case '67': $msg_nova = 'Data de expiração do cartão com formato inválido.'; break;
		case '68': $msg_nova = 'CVV2/CVC2 não informado.'; break;
		case '69': $msg_nova = 'CVV2/CVC2 com formato inválido.'; break;
		case '73': $msg_nova = 'Canal sem plano definido.'; break;
		default: $msg_nova = "Erro de processamento. ($id)"; break;
	}

	return $msg_nova;
}

function estonarPedidoTiPagos($id_pedido, $bank_data = array()) {
	global $gw_tipagos;

	$gw_tipagos = configureSplit("tipagos");

	$url_ws = $gw_tipagos["url_ws"];
	$idLoja = $gw_tipagos["idLoja"];
	$keyLoja = $gw_tipagos["keyLoja"];
	$codProduto = $gw_tipagos["codProduto"];

	$mainConnection = mainConnection();

	$query = "SELECT OBJ_PAGSEGURO FROM MW_PEDIDO_PAGSEGURO WHERE ID_PEDIDO_VENDA = ? ORDER BY DT_STATUS DESC";
    $params = array($id_pedido);
    $rs = executeSQL($mainConnection, $query, $params, true);

    $transaction = unserialize(base64_decode($rs['OBJ_PAGSEGURO']));


    $dados = array("header" => array("idLoja"=>$idLoja, 
									 "keyLoja"=>$keyLoja,
									 "codProduto"=>$codProduto),
				   "nsuTipagos"=>$transaction['nsuTipagos']);

	$post_data = json_encode($dados);
	
	error_log("realizando estorno no tipagos para o pedido " . $id_pedido);
	error_log("realizando estorno no tipagos: " . print_r($dados, true));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json')); 
	curl_setopt($ch, CURLOPT_URL, $url_ws."cancelarcompra");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$server_output = curl_exec($ch);
	curl_close($ch);

	$resp = json_decode($server_output, true);

	error_log("resposta para o estorno no tipagos: " . print_r($resp, true));

	// print_r($resp);

	if($resp['retorno']['rc'] == '0') {
		$response = array('success' => true, 'transaction' => $transaction);

		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($id_pedido, $transaction->status, base64_encode(serialize($transaction)));
		executeSQL($mainConnection, $query, $params);

    } else {
        $response = array('success' => false, 'error' => tratarErroTiPagos($resp['retorno']['rc']));

    }

    return $response;
}