<?php
require_once('../settings/functions.php');
session_start();
// require_once('../settings/Cypher.class.php');
// $cipher = new Cipher('1ngr3ss0s');
// echo urlencode(base64_encode($cipher->encrypt('436720|gabriel.monteiro@cc.com.br')));
// echo $cipher->decrypt(base64_decode($_GET['pedido']));
// die();

if (checkIfDelivery($_GET['pedido']) == true) die();

if (!isset($_GET['pedido'])) die();

if (is_numeric($_GET['pedido'])) {
	require 'acessoLogado.php';

	$id_pedido = $_GET['pedido'];
	$id_usuario = $_SESSION['user'];
	$email_presenteado = null;
} else {
	require_once('../settings/Cypher.class.php');

	$cipher = new Cipher('1ngr3ss0s');
	$decryptedtext = $cipher->decrypt(base64_decode($_GET['pedido']));
	$decryptedtext = explode('|', $decryptedtext);

	$id_pedido = $decryptedtext[0];
	$id_usuario = null;
	$email_presenteado = $decryptedtext[1];
}

$mainConnection = mainConnection();

$query = "SELECT
				C.DS_NOME + ' ' + C.DS_SOBRENOME AS DS_NOME,
				CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) AS DT_PEDIDO_VENDA,
				VL_TOTAL_PEDIDO_VENDA,
				MP.CD_MEIO_PAGAMENTO,
				C.CD_CPF,
				C.DS_DDD_TELEFONE,
				C.DS_TELEFONE,
				C.DS_DDD_CELULAR,
				C.DS_CELULAR,
				C.DS_ENDERECO,
				C.DS_COMPL_ENDERECO,
				C.DS_BAIRRO,
				C.DS_CIDADE,
				E.SG_ESTADO,
				C.CD_CEP,
				PV.DS_ENDERECO_ENTREGA,
				PV.DS_COMPL_ENDERECO_ENTREGA,
				PV.DS_BAIRRO_ENTREGA,
				PV.DS_CIDADE_ENTREGA,
				PV.CD_CEP_ENTREGA,
				PV.IN_RETIRA_ENTREGA,
				C.CD_EMAIL_LOGIN,
				PV.NR_PARCELAS_PGTO,
				PV.NM_CLIENTE_VOUCHER,
				PV.DS_EMAIL_VOUCHER,
				PV.CD_BIN_CARTAO
			FROM MW_PEDIDO_VENDA PV
			INNER JOIN MW_CLIENTE C ON PV.ID_CLIENTE = C.ID_CLIENTE
			LEFT JOIN MW_MEIO_PAGAMENTO MP ON PV.ID_MEIO_PAGAMENTO = MP.ID_MEIO_PAGAMENTO
			LEFT JOIN MW_ESTADO E ON C.ID_ESTADO = E.ID_ESTADO
			WHERE PV.ID_PEDIDO_VENDA = ?
				AND ((C.ID_CLIENTE = ? AND ? IS NULL) OR (? IS NULL AND PV.DS_EMAIL_VOUCHER = ?))";
$params = array($id_pedido,
				$id_usuario, $email_presenteado,
				$id_usuario, $email_presenteado);
$rsDados = executeSQL($mainConnection, $query, $params, true);

if (!empty($rsDados)) {

	$parametros['OrderData']['OrderId'] = $id_pedido;
	$parametros['CustomerData']['CustomerName'] = $rsDados['DS_NOME'];
	$valores['date'] = $rsDados['DT_PEDIDO_VENDA'];
	$PaymentDataCollection['Amount'] = $rsDados['VL_TOTAL_PEDIDO_VENDA'] * 100;
	$PaymentDataCollection['PaymentMethod'] = $rsDados['CD_MEIO_PAGAMENTO'];
	$PaymentDataCollection['NumberOfPayments'] = $rsDados['NR_PARCELAS_PGTO'];
	$parametros['CustomerData']['CustomerIdentity'] = $rsDados['CD_CPF'];
	$parametros['CustomerData']['CustomerEmail'] = $rsDados['CD_EMAIL_LOGIN'];
	$dadosExtrasEmail['cpf_cnpj_cliente'] = $parametros['CustomerData']['CustomerIdentity'];

	$dadosExtrasEmail['ddd_telefone1'] = $rsDados['DS_DDD_TELEFONE'];
	$dadosExtrasEmail['numero_telefone1'] = $rsDados['DS_TELEFONE'];
	$dadosExtrasEmail['ddd_telefone2'] = $rsDados['DS_DDD_CELULAR'];
	$dadosExtrasEmail['numero_telefone2'] = $rsDados['DS_CELULAR'];

	$dadosExtrasEmail['nome_presente'] = $rsDados['NM_CLIENTE_VOUCHER'];
	$dadosExtrasEmail['email_presente'] = $rsDados['DS_EMAIL_VOUCHER'];

	$dadosExtrasEmail['cartao'] = $rsDados['CD_BIN_CARTAO'];

	$parametros['CustomerData']['CustomerAddressData']['Street'] = $rsDados['DS_ENDERECO'];
	$parametros['CustomerData']['CustomerAddressData']['Complement'] = $rsDados['DS_COMPL_ENDERECO'];
	$parametros['CustomerData']['CustomerAddressData']['District'] = $rsDados['DS_BAIRRO'];
	$parametros['CustomerData']['CustomerAddressData']['City'] = $rsDados['DS_CIDADE'];
	$parametros['CustomerData']['CustomerAddressData']['State'] = $rsDados['SG_ESTADO'];
	$parametros['CustomerData']['CustomerAddressData']['Country'] = 'Brasil';
	$parametros['CustomerData']['CustomerAddressData']['ZipCode'] = $rsDados['CD_CEP'];

	if ($rsDados['IN_RETIRA_ENTREGA'] == 'E') {
		$parametros['CustomerData']['DeliveryAddressData']['Street'] = $rsDados['DS_ENDERECO_ENTREGA'];
		$parametros['CustomerData']['DeliveryAddressData']['Complement'] = $rsDados['DS_COMPL_ENDERECO_ENTREGA'];
		$parametros['CustomerData']['DeliveryAddressData']['District'] = $rsDados['DS_BAIRRO_ENTREGA'];
		$parametros['CustomerData']['DeliveryAddressData']['City'] = $rsDados['DS_CIDADE_ENTREGA'];
		$parametros['CustomerData']['DeliveryAddressData']['State'] = $rsDados['SG_ESTADO'];
		$parametros['CustomerData']['DeliveryAddressData']['Country'] = 'Brasil';
		$parametros['CustomerData']['DeliveryAddressData']['ZipCode'] = $rsDados['CD_CEP_ENTREGA'];
	}

	$query = "SELECT R.ID_RESERVA, R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, R.DS_LOCALIZACAO AS DS_CADEIRA,
					R.DS_SETOR, E.ID_EVENTO, E.DS_EVENTO, ISNULL(LE.DS_LOCAL_EVENTO, B.DS_NOME_TEATRO) DS_NOME_TEATRO,
					CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) DT_APRESENTACAO, A.HR_APRESENTACAO,
					AB.VL_LIQUIDO_INGRESSO, AB.DS_TIPO_BILHETE, E.ID_BASE, A.CodApresentacao, R.CodVenda, R.Indice
				FROM MW_ITEM_PEDIDO_VENDA R
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = '1'
				INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
				INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
				LEFT JOIN MW_LOCAL_EVENTO LE ON E.ID_LOCAL_EVENTO = LE.ID_LOCAL_EVENTO
				WHERE R.ID_PEDIDO_VENDA = ?
				ORDER BY E.DS_EVENTO, R.ID_APRESENTACAO, R.DS_LOCALIZACAO";

	$params = array($id_pedido);
	$result = executeSQL($mainConnection, $query, $params);

	$queryServicos = "SELECT DISTINCT isnull(T.IN_TAXA_POR_PEDIDO, 'N') IN_TAXA_POR_PEDIDO FROM MW_ITEM_PEDIDO_VENDA I
						INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
						LEFT JOIN MW_TAXA_CONVENIENCIA T ON T.ID_EVENTO = A.ID_EVENTO AND T.DT_INICIO_VIGENCIA <= GETDATE() AND T.IN_TAXA_POR_PEDIDO = 'S'
						WHERE I.ID_PEDIDO_VENDA = ?";
	$rsServicos = executeSQL($mainConnection, $queryServicos, array($id_pedido), true);

	$itensPedido = array();
	$i = -1;
	while ($itens = fetchResult($result)) {
	    $i++;

	    if ($i == 0) {
	        if ($rsServicos['IN_TAXA_POR_PEDIDO'] == 'S') {
	            $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], true, $id_pedido);

	            $itensPedido[$i]['descricao_item'] = 'Serviço';
	            $itensPedido[$i]['valor_item'] = $valorConveniencia;

	            $valorConveniencia = 0;
	            $i++;
	        } else {
	            $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], false, $id_pedido);
	        }
	    } else {
	        $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], false, $id_pedido);
	    }

	    $evento_info = getEvento($itens['ID_EVENTO']);

	    $itensPedido[$i]['descricao_item']['evento'] = utf8_encode2($itens['DS_EVENTO']);
	    $itensPedido[$i]['descricao_item']['data'] = $itens['DT_APRESENTACAO'];
	    $itensPedido[$i]['descricao_item']['hora'] = $itens['HR_APRESENTACAO'];
	    $itensPedido[$i]['descricao_item']['teatro'] = utf8_encode2($evento_info['nome_teatro']);
	    $itensPedido[$i]['descricao_item']['setor'] = utf8_encode2($itens['DS_SETOR']);
	    $itensPedido[$i]['descricao_item']['cadeira'] = utf8_encode2($itens['DS_CADEIRA']);
	    $itensPedido[$i]['descricao_item']['bilhete'] = utf8_encode2($itens['DS_TIPO_BILHETE']);
		$itensPedido[$i]['descricao_item']['codvenda'] = utf8_encode2($itens['CodVenda']);
		$itensPedido[$i]['descricao_item']['indice'] = $itens['Indice'];
		$itensPedido[$i]['descricao_item']['id_base'] = $itens['ID_BASE'];
		

	    $itensPedido[$i]['valor_item'] = ($itens['VL_LIQUIDO_INGRESSO'] + $valorConveniencia);
	    $itensPedido[$i]['id_base'] = $itens['ID_BASE'];
	    $itensPedido[$i]['CodApresentacao'] = $itens['CodApresentacao'];
	    $itensPedido[$i]['CodVenda'] = $itens['CodVenda'];
	}

	$is_gift = ($email_presenteado != null);

	require "../comprar/impressaoVoucher.php";

	if ($_GET['pdf']) {
		/*require "../settings/mpdf60/mpdf.php";

		$mpdf=new mPDF();

		$mpdf->WriteHTML($successMail);
		$mpdf->Output();*/

	} elseif ($_GET['allinmail']) {
		/*$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://transacional01.postmatic.com.br/api/?method=get_token&output=json&username=Tcompreingressos&password=30042015XXYR");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$server_output = curl_exec($ch);
		curl_close($ch);

		$resp = json_decode($server_output, true);

		$token = $resp['token'];

		$dados = array('dados' => json_encode(array('nm_html'		=> 'teste envio pedido',
													'html'			=> base64_encode($successMail),
													'temporario'	=> 1)));

		// $ch = curl_init();
		// curl_setopt($ch, CURLOPT_URL, "http://transacional01.postmatic.com.br/api/?method=cadastrar_html&output=json&token=$token");
		// curl_setopt($ch, CURLOPT_POST, 1);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		// curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		// $server_output = curl_exec($ch);
		// curl_close($ch);

		// $resp = json_decode($server_output, true);

		// $html_id = $resp['html_id'];
		$html_id = 682879;

		$timestamp = time();

		$dados = array('dados' => json_encode(array('nm_email'			=> $parametros['CustomerData']['CustomerEmail'],
													'html_id'			=> $html_id,
													'nm_subject'		=> $subject,
													'nm_remetente'		=> $namefrom,
													'email_remetente'	=> $from,
													'nm_reply'			=> $from,
													'dt_envio'			=> date("Y-m-d", $timestamp),
													'hr_envio'			=> date("H:i", $timestamp))));


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://transacional01.postmatic.com.br/api/?method=enviar_email&output=json&token=$token");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$server_output = curl_exec($ch);
		curl_close($ch);

		$resp = json_decode($server_output, true);

		var_dump($dados);

		var_dump($resp);*/

	} else {
		echo $successMail;
	}

}