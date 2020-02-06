<?php
require_once('../settings/functions.php');
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


$query = "DECLARE @id_base   INT
DECLARE @CodPeca   INT
DECLARE @id_evento INT
DECLARE @ds_nome_base_sql VARCHAR(32)

SELECT TOP 1 @id_evento = E.id_evento, @CodPeca = E.CodPeca, @id_base = E.id_base
            FROM MW_PEDIDO_VENDA PV
            INNER JOIN MW_ITEM_PEDIDO_VENDA I ON I.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
            INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
            INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO
			INNER JOIN CI_MIDDLEWAY..mw_evento E ON A.id_evento = E.id_evento
            WHERE PV.ID_PEDIDO_VENDA = ?

SELECT @ds_nome_base_sql = ds_nome_base_sql FROM CI_MIDDLEWAY..mw_base
where id_base = @id_base

exec('USE '+ @ds_nome_base_sql + ';SELECT description_voucher from tabPeca where CodPeca ='+@CodPeca)";

$params = array($parametros['OrderData']['OrderId']);
$result = executeSQL($mainConnection, $query, $params);

$voucher = hasRows($result);


$query = "EXEC pr_show_partner_info_bypedido ?, ?";
// die(json_encode(getwhitelabelobj_forced("ingressaria")));
$params = array(getwhitelabelobj_forced("ingressaria")["apikey"],$parametros['OrderData']['OrderId']);
//     die(json_encode($params));
$rs_show_partner_info = executeSQL($mainConnection, $query, $params, true);
// die(json_encode($rs_show_partner_info));

// checa se é um cliente estrangeiro
$query = "SELECT CD_RG, ID_DOC_ESTRANGEIRO FROM MW_CLIENTE WHERE ID_CLIENTE = ?";
$rsEstrangeiro = executeSQL($mainConnection, $query, array($parametros['CustomerData']['CustomerIdentity']), true);

$dadosExtrasEmail['cpf_cnpj_cliente'] = $rsExtrangeiro['ID_DOC_ESTRANGEIRO'] ? $rsExtrangeiro['CD_RG'] : $dadosExtrasEmail['cpf_cnpj_cliente'];
// ---------------------------------


$query = 'SELECT ds_meio_pagamento FROM mw_meio_pagamento WHERE cd_meio_pagamento = ?';
$rs = executeSQL($mainConnection, $query, array($PaymentDataCollection['PaymentMethod']), true);

$valores['aaa'] = $voucher;

$valores['codigo_pedido'] = $parametros['OrderData']['OrderId'];
$valores['nome_cliente'] = $parametros['CustomerData']['CustomerName'];
$valores['itens_pedido'] = '';
$valores['data_hora_status'] = isset($valores['date']) ? $valores['date'] : date('d/m/Y');
$valores['data_hora_pagamento'] = isset($valores['date']) ? $valores['date'] : date('d/m/Y');
$valores['data_hora_impressao'] = date('d/m/Y H:i');
$valores['valor_total'] = number_format($PaymentDataCollection['Amount'] / 100, 2, ',', '');
$valores['nome_status'] = 'Pago';
$valores['total_pagamento'] = $valores['valor_total'];
$valores['meio_pagamento'] = utf8_encode2($rs['ds_meio_pagamento']);
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

$valores['cartao'] = $dadosExtrasEmail['cartao'];

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

$barcodes = array();
$ingressosCount = 0;
$CodApresentacao = '';
$queryCodigos = "SELECT codbar
                FROM tabControleSeqVenda c
                INNER JOIN tabLugSala l ON l.CodApresentacao = c.CodApresentacao AND l.Indice = c.Indice
                WHERE l.CodApresentacao = ? AND l.CodVenda = ? and c.statusingresso = 'L'
                ORDER BY c.Indice";
foreach ($itensPedido as $item) {
    
    if ($item['descricao_item'] == 'Serviço') {
        
        $valores['valor_servico'] = 'Serviço: R$ '.number_format($item['valor_item'], 2, ',', '').'<br>';
        $valores['valor_ingressos'] = number_format(($PaymentDataCollection['Amount'] / 100) - $item['valor_item'], 2, ',', '');

    } else {
        if ($CodApresentacao !== $item['CodApresentacao']) {

            for ($i = 0; $i < 3; $i++) {
                $codigo_sql_errors = array();

                $conn = getConnection($item['id_base']);
                $codigo_sql_errors[] = array(
                    'id_base' => $item['id_base'],
                    'conexao' => sqlErrors()
                );
                
                $codigos = executeSQL($conn, $queryCodigos, array($item['CodApresentacao'], $item['CodVenda']));
                $codigo_sql_errors[] = array(
                    'params' => array($item['CodApresentacao'], $item['CodVenda']),
                    'requisicao' => sqlErrors()
                );
                
                $rsCodigo = fetchResult($codigos);
                $codigo_sql_errors[] = array('fetch' => sqlErrors());

                if ($rsCodigo['codbar'] != NULL) break;
                else {
                    $data_parts = explode('/', $item['descricao_item']['data']);
                    
                    $data_hora = $data_parts[2].'-'.$data_parts[1].'-'.$data_parts[0].' '.preg_replace('/h/i', ':', $item['descricao_item']['hora']);

                    if (strtotime($data_hora) > time()) {
                        $codigo_error_data[] = array(
                            'tentativa' => $i + 1,
                            'item' => $item,
                            'codigo_sql_errors' => $codigo_sql_errors
                        );
                    }
                    sleep(1);
                }
            }
            $CodApresentacao = $item['CodApresentacao'];
        } else {
            // remover esse codigo depois do dia 31/07/2016 -------------- issue #129
            if ($rsCodigo = fetchResult($codigos)) {}
            else {
                $codigos = executeSQL($conn, $queryCodigos, array($item['CodApresentacao'], $item['CodVenda']));
                $rsCodigo = fetchResult($codigos);
            }
            // adicionar esse codigo depois do dia 31/07/2016 ------------ issue #129
            // $rsCodigo = fetchResult($codigos)
        }

        $code = $rsCodigo['codbar'];

        
        $qrcode = getQRCodeFromAPI($item['descricao_item']['id_base'], $item['descricao_item']['codvenda'], $item['descricao_item']['indice']);
        //die("impressaoVoucher");

        //die("qrCode: ".$qrcode);
        
        $ingressosCount++;

        //$code2_type = pathinfo($path2, PATHINFO_EXTENSION);
        //$code2_data = file_get_contents($path2);
        $code2_img_src = 'data:image/png;base64,'.$qrcode;

        $valores['itens_pedido'][] = array(
            'item_qrcode' => $code2_img_src,
            'item_evento' => $item['descricao_item']['evento'],
            'item_teatro' => $item['descricao_item']['teatro'],
            'item_tipo_bilhete' => $item['descricao_item']['bilhete'],
            'item_codvenda' => $item['descricao_item']['codvenda'],
            'item_valor' => number_format($item['valor_item'], 2, ',', ''),
            'item_setor' => $item['descricao_item']['setor'],
            'item_cadeira' => $item['descricao_item']['cadeira'],
            'item_data' => ($is_assinatura ? '' : $item['descricao_item']['data']),
            'item_hora' => ($is_assinatura ? '' : $item['descricao_item']['hora']),
            'item_extra_style' => ($ingressosCount % 4 == 0 ? '' : 'border-right:2px dashed #EEEEEE;')
        );
        $valores['workaround'] = $item['descricao_item']['workaround'];
    }

    // se nao tiver servico destacado ingressos é igual total
    $valores['valor_ingressos'] = isset($valores['valor_servico']) ? $valores['valor_ingressos'] : $valores['valor_total'];
    $valores['valor_servico'] = isset($valores['valor_servico']) ? $valores['valor_servico'] : '';
}

$valores['itens_destacaveis'] = $valores['itens_pedido'];

//$rs_show_partner_info["show_partner_info"] = 0;

$forcedbase = $rs_show_partner_info["ds_nome_base_sql"];
//$forcedbase = "simoesinvestimentos";
//die(json_encode($is_gift));
//die("oi");
$objwlforced = getwhitelabelobj_forced($rs_show_partner_info["show_partner_info"] == 1 ? $forcedbase: gethost());

$caminhoHtml = $is_gift == 1 ? $objwlforced["templates"]["print"]["gift"] : $objwlforced["templates"]["print"]["voucher"];
// die($caminhoHtml);
//die(json_encode($objwlforced));


$valores['partner_desc'] = "";
if ($rs_show_partner_info["show_partner_info"] == 1) {
    $valores['partner_desc'] = "Compra realizada em: ".getwhitelabelobj()["uri"];

}

$tpl_file = $caminhoHtml;

$tpl = new Template($tpl_file);

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

//define the body of the message.
ob_start(); //Turn on output buffering

$tpl->show();

//copy current buffer contents into $message variable and delete current output buffer
$message = ob_get_clean();

$successMail = $message;
?>