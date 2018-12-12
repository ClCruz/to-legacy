<?php
require_once('../settings/functions.php');
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
require_once('../settings/settings.php');
require_once('../settings/Template.class.php');

// checa se o pedido é um "pedido pai" (assinatura)
$query = "SELECT TOP 1 1
            FROM MW_PEDIDO_VENDA PV
            INNER JOIN MW_ITEM_PEDIDO_VENDA I ON I.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
            INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
            INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
            INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A2.ID_APRESENTACAO
            WHERE PV.ID_PEDIDO_VENDA = ?";
$params = array($parametros['OrderData']['OrderId']);
$result = executeSQL($mainConnection, $query, $params);

$is_assinatura = hasRows($result);


$subject = 'Pedido ' . $parametros['OrderData']['OrderId'] . ' - Pago';

$namefrom = multiSite_getTitle();
$from = '';

$query = 'SELECT ds_meio_pagamento FROM mw_meio_pagamento WHERE cd_meio_pagamento = ?';
$rs = executeSQL($mainConnection, $query, array($PaymentDataCollection['PaymentMethod']), true);

$valores['codigo_pedido'] = $parametros['OrderData']['OrderId'];
$valores['nome_cliente'] = $parametros['CustomerData']['CustomerName'];
$valores['itens_pedido'] = '';
$valores['data_hora_status'] = isset($valores['date']) ? $valores['date'] : date('d/m/Y');
$valores['valor_total'] = number_format($PaymentDataCollection['Amount'] / 100, 2, ',', '');
$valores['nome_status'] = 'Pago';
$valores['data_hora_pagamento'] = isset($valores['date']) ? $valores['date'] : date('d/m/Y');
$valores['total_pagamento'] = $valores['valor_total'];
$valores['meio_pagamento'] = $rs['ds_meio_pagamento'];
$valores['codigo_cliente'] = $parametros['CustomerData']['CustomerIdentity'];
$valores['email_cliente'] = $parametros['CustomerData']['CustomerEmail'];
$valores['cpf_cnpj_cliente'] = $dadosExtrasEmail['cpf_cnpj_cliente'];
$valores['numero_parcelas'] = $PaymentDataCollection['NumberOfPayments'];

$valores['ddd_telefone1'] = $dadosExtrasEmail['ddd_telefone1'];
$valores['numero_telefone1'] = $dadosExtrasEmail['numero_telefone1'];
$valores['ddd_telefone2'] = $dadosExtrasEmail['ddd_telefone2'];
$valores['numero_telefone2'] = $dadosExtrasEmail['numero_telefone2'];
$valores['ddd_telefone3'] = $dadosExtrasEmail['ddd_telefone3'];
$valores['numero_telefone3'] = $dadosExtrasEmail['numero_telefone3'];

$valores['nome_presenteado'] = $dadosExtrasEmail['nome_presente'];
$valores['email_presenteado'] = $dadosExtrasEmail['email_presente'];

$valores['logradouro_endereco_cobranca'] = $parametros['CustomerData']['CustomerAddressData']['Street'];
$valores['numero_endereco_cobranca'] = '';
$valores['complemento_endereco_cobranca'] = $parametros['CustomerData']['CustomerAddressData']['Complement'];
$valores['bairro_endereco_cobranca'] = $parametros['CustomerData']['CustomerAddressData']['District'];
$valores['cidade_endereco_cobranca'] = $parametros['CustomerData']['CustomerAddressData']['City'];
$valores['uf_endereco_cobranca'] = $parametros['CustomerData']['CustomerAddressData']['State'];
$valores['pais_endereco_cobranca'] = $parametros['CustomerData']['CustomerAddressData']['State'] == 'EX' ? 'Exterior' : $parametros['CustomerData']['CustomerAddressData']['Country'];
$valores['cep_endereco_cobranca'] = $parametros['CustomerData']['CustomerAddressData']['ZipCode'];

$valores['logradouro_endereco_entrega'] = $parametros['CustomerData']['DeliveryAddressData']['Street'];
$valores['numero_endereco_entrega'] = '';
$valores['complemento_endereco_entrega'] = $parametros['CustomerData']['DeliveryAddressData']['Complement'];
$valores['bairro_endereco_entrega'] = $parametros['CustomerData']['DeliveryAddressData']['District'];
$valores['cidade_endereco_entrega'] = $parametros['CustomerData']['DeliveryAddressData']['City'];
$valores['uf_endereco_entrega'] = $parametros['CustomerData']['DeliveryAddressData']['State'];
$valores['pais_endereco_entrega'] = $parametros['CustomerData']['DeliveryAddressData']['Country'];
$valores['cep_endereco_entrega'] = $parametros['CustomerData']['DeliveryAddressData']['ZipCode'];

$queryCodigos = "SELECT codbar
                FROM tabControleSeqVenda c
                INNER JOIN tabLugSala l ON l.CodApresentacao = c.CodApresentacao AND l.Indice = c.Indice
                WHERE l.CodApresentacao = ? AND l.CodVenda = ? and c.statusingresso = 'L'
                ORDER BY c.Indice";

$ingressosCount = 0;
foreach ($itensPedido as $item) {
    
    if ($item['descricao_item'] == 'Serviço') {
        
        $valores['valor_servico'] = 'Serviço: R$ '.number_format($item['valor_item'], 2, ',', '').'<br>';
        $valores['valor_ingressos'] = number_format(($PaymentDataCollection['Amount'] / 100) - $item['valor_item'], 2, ',', '');

    } else {
        
        $ingressosCount++;

        if ($CodApresentacao !== $item['CodApresentacao']) {
            $conn = getConnection($item['id_base']);
            $codigos = executeSQL($conn, $queryCodigos, array($item['CodApresentacao'], $item['CodVenda']));
            $rsCodigo = fetchResult($codigos);
            $CodApresentacao = $item['CodApresentacao'];
        } else {
            $rsCodigo = fetchResult($codigos);
        }

        $code = $rsCodigo['codbar'];

        $evento_info = getEvento($item['descricao_item']['id_evento']);

        $data_parts = explode('/', $item['descricao_item']['data']);

        $dados_pass = array(
            'codigo_pedido' => $valores['codigo_pedido'],
            'data' => ($is_assinatura ? '' : strftime("%a %d %b", strtotime($data_parts[2].$data_parts[1].$data_parts[0]))),
            'total' => $valores['valor_total'],
            'evento' => $item['descricao_item']['evento'],
            'endereco' => $evento_info['endereco'] . ' - ' . $evento_info['cidade'] . ', ' .$evento_info['sigla_estado'],
            'nome_teatro' => $evento_info['nome_teatro'],
            'horario' => ($is_assinatura ? '' : $item['descricao_item']['hora']),
            'barcode' => $code,
            'local_bilhete' => $item['descricao_item']['setor'] . ' ' . $item['descricao_item']['cadeira'],
            'tipo_bilhete' => $item['descricao_item']['bilhete'],
            'preco_bilhete' => number_format($item['valor_item'], 2, ',', ''),
            'servico_bilhete' => "0,00",
            'total_bilhete' => number_format($item['valor_item'], 2, ',', '')
        );

        $pkpass_url = getPKPass($dados_pass);
        
        $valores['itens_pedido'][] = array(
            //'item_miniatura' => getMiniature($item['descricao_item']['id_evento']),
            'item_miniatura' => multiSite_getURI("URI_SSL") . "images/default_espetaculo.jpg",
            'item_evento_id' => $item['descricao_item']['id_evento'],
            'item_evento' => $item['descricao_item']['evento'],
            'item_teatro' => $item['descricao_item']['teatro'],
            'item_tipo_bilhete' => $item['descricao_item']['bilhete'],
            'item_valor' => number_format($item['valor_item'], 2, ',', ''),
            'item_setor' => $item['descricao_item']['setor'],
            'item_cadeira' => $item['descricao_item']['cadeira'],
            'item_data' => ($is_assinatura ? '' : $item['descricao_item']['data']),
            'item_hora' => ($is_assinatura ? '' : $item['descricao_item']['hora']),

            'item_nome_teatro' => $evento_info['nome_teatro'],
            'item_teatro_estado' => $evento_info['sigla_estado'],
            'item_teatro_cidade' => $evento_info['cidade'],

            'pkpass_url' => $pkpass_url
        );
    }

    // se nao tiver servico destacado ingressos é igual total
    $valores['valor_ingressos'] = isset($valores['valor_servico']) ? $valores['valor_ingressos'] : $valores['valor_total'];
    $valores['valor_servico'] = isset($valores['valor_servico']) ? $valores['valor_servico'] : '';
}


$valores['link_voucher'] = multiSite_getURIReeimprimir($parametros['OrderData']['OrderId']);

$caminhoHtml = getwhitelabeltemplate("email:buyer");

$tpl = new Template($caminhoHtml);

foreach ($valores as $key => $value) {
    if (is_array($value)) {
        foreach ($value as $detalhes) {
            foreach ($detalhes as $key2 => $value2) {
            try { $tpl->$key2 = $value2; } catch (Exception $e) { die($e); /* variaveis nao encontradas */ }
        }
        $tpl->parseBlock(strtoupper($key), true);
    }
} else {
try { $tpl->$key = $value; } catch (Exception $e) {  /* variaveis nao encontradas */ }
}
}
//define the body of the message.

ob_start(); //Turn on output buffering

$tpl->show();

//copy current buffer contents into $message variable and delete current output buffer
$message = ob_get_clean();

$bcc = ($_ENV['IS_TEST']
        ? array()
        : array('Pedidos=>'.multiSite_getEmail("pedido")));

$successMail = authSendEmail($from, $namefrom, $parametros['CustomerData']['CustomerEmail'], $parametros['CustomerData']['CustomerName'], $subject, $message, array(), $bcc, 'utf-8', $barcodes);

if (filter_var($valores['email_presenteado'], FILTER_VALIDATE_EMAIL)) {

    require_once('../settings/Cypher.class.php');
    $cipher = new Cipher('1ngr3ss0s');
    $cipher_var = urlencode(base64_encode($cipher->encrypt($parametros['OrderData']['OrderId'].'|'.$valores['email_presenteado'])));

    $valores['link_voucher'] = multiSite_getURIReeimprimir($cipher_var);

    $caminhoHtml = getwhitelabeltemplate("email:gift");
                                
    $tpl = new Template($caminhoHtml);

    foreach ($valores as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $detalhes) {
                foreach ($detalhes as $key2 => $value2) {
                    try { $tpl->$key2 = $value2; } catch (Exception $e) { /* variaveis nao encontradas */ }
                }
                $tpl->parseBlock(strtoupper($key), true);
            }
        } else {
            try { $tpl->$key = $value; } catch (Exception $e) { /* variaveis nao encontradas */ }
        }
    }

    $subject = 'Você recebeu um presente!';

    ob_start();
    $tpl->show();
    $message = ob_get_clean();

    $successMail = authSendEmail($from, $namefrom, $valores['email_presenteado'], $valores['nome_presenteado'], $subject, $message, array(), array(), 'utf-8', $barcodes);

}

if (!empty($codigo_error_data)) {
    ob_start();
    echo "<pre>";
    var_dump(array(
        $_SERVER['LOCAL_ADDR'],
        $parametros['OrderData'],
        $codigo_error_data
    ));
    echo "</pre>";
    $message = ob_get_clean();
    sendErrorMail('Erro no Sistema - codigo do ingresso', $message);
}
?>