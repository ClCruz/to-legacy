<?php
require_once('../settings/functions.php');
require_once('../settings/split/split_config.php');

//$type: pagarme, tipagos
function getSplit($type, $pedido, $where, $payment_method, $amount) {
    global $gw_pagarme, $gw_tipagos;

    configureSplit($type);

	$mainConnection = mainConnection();

	$query = "select distinct e.CodPeca, e.id_base
			  from mw_pedido_venda pv
			  inner join mw_item_pedido_venda ipv on ipv.id_pedido_venda = pv.id_pedido_venda
			  inner join mw_apresentacao a on a.id_apresentacao = ipv.id_apresentacao
			  inner join mw_evento e on e.id_evento = a.id_evento
			  where pv.id_pedido_venda = ?";
	$param = array($pedido);
	$stmt = executeSQL($mainConnection, $query, $param, true);

	$query = "SELECT DISTINCT r.recipient_id
	,rs.nr_percentual_split
	,rs.liable
	,rs.charge_processing_fee
	,rs.percentage_credit_web
	,rs.percentage_debit_web
	,rs.percentage_boleto_web
	,rs.percentage_credit_box_office
	,rs.percentage_debit_box_office
	,(CASE r.cd_cpf_cnpj WHEN '11665394000113' THEN 1 ELSE 0 END) IsTicketPay
	FROM tabPeca tb
	INNER JOIN CI_MIDDLEWAY..mw_evento e ON tb.CodPeca=e.CodPeca
	INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
	INNER JOIN CI_MIDDLEWAY..mw_produtor p ON p.id_produtor = tb.id_produtor and p.in_ativo=1
	INNER JOIN CI_MIDDLEWAY..mw_regra_split rs ON rs.id_produtor = p.id_produtor and rs.id_evento=e.id_evento
	INNER JOIN CI_MIDDLEWAY..mw_recebedor r ON rs.id_recebedor = r.id_recebedor and r.in_ativo=1
	WHERE tb.CodPeca = ? and rs.in_ativo = 1
	AND b.ds_nome_base_sql=DB_NAME()
	ORDER BY (CASE r.cd_cpf_cnpj WHEN '11665394000113' THEN 1 ELSE 0 END)";

	$conn = getConnection($stmt["id_base"]);
	$param = array($stmt["CodPeca"]);
	$result = executeSQL($conn, $query, $param);

	$queryCount = "SELECT DISTINCT COUNT(*) Total
	FROM tabPeca tb
	INNER JOIN CI_MIDDLEWAY..mw_evento e ON tb.CodPeca=e.CodPeca
	INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
	INNER JOIN CI_MIDDLEWAY..mw_produtor p ON p.id_produtor = tb.id_produtor and p.in_ativo=1
	INNER JOIN CI_MIDDLEWAY..mw_regra_split rs ON rs.id_produtor = p.id_produtor and rs.id_evento=e.id_evento
	INNER JOIN CI_MIDDLEWAY..mw_recebedor r ON rs.id_recebedor = r.id_recebedor and r.in_ativo=1
	WHERE tb.CodPeca = ? and rs.in_ativo = 1 AND b.ds_nome_base_sql=DB_NAME()";

	$resultCount = executeSQL($conn, $queryCount, $param,true);

	if(!hasRows($result))
		return null;

	$count = $resultCount["Total"];

	$i = 0;
	$amountUsed = 0;
	$amount = $amount/100;

	$split = array();
	while($rs = fetchResult($result)) {
		$i = $i+1;
		$perToUse = 0;
		$amountToUse = 0;

		switch ($where) {
			case "web":
				switch ($payment_method) {
					case "credit":
					case "credit_card":
							$perToUse = $rs["percentage_credit_web"];
						break;
					case "boleto":
						$perToUse = $rs["percentage_boleto_web"];
						break;
					case "debit":
					case "debit_card":
						$perToUse = $rs["percentage_debit_web"];
						break;							
				}
				break;
			case "bilheteria":
				switch ($payment_method) {
					case "credit":
					case "credit_card":
							$perToUse = $rs["percentage_credit_box_office"];
						break;
					case "debit":
					case "debit_card":
						$perToUse = $rs["percentage_debit_box_office"];
						break;							
				}
				break;
		}

		if ($count==$i) {
			$amoutToUse = round($amount-$amountUsed, 2);
		}
		else {
			$amoutToUse = round($amount*($perToUse/100), 2);
		}

		$amountUsed = $amountUsed + $amoutToUse;

		//error_log("perToUse: " . $perToUse);
		//error_log("amoutToUse: " . $amoutToUse);
        
        switch ($type) {
            case "pagarme":
                $split[] = array(
                    "recipient_id" => $rs["recipient_id"],
                    // "percentage" => $perToUse,
                    "amount" => $amoutToUse*100,
                    "liable" => $rs["liable"],
                    "charge_processing_fee" => $rs["charge_processing_fee"]);
                break;
            case "tipagos":
                $split[] = array(
                    "codigoProduto" => $gw_tipagos["codProduto"],
                    "codigoCliente" => $rs["recipient_id"],
                    "valor" => $amoutToUse*100);

            break;
		}
		
		//error_log("split: " . print_r($split, true));

	}
	//error_log("Split: " . print_r($split, true));
	return $split;
}

function isPlayWithSplitFindByOrder($pedido) {

	$mainConnection = mainConnection();

	$query = "SELECT TOP 1
    ep.id_gateway
    FROM mw_item_pedido_venda ipv
    INNER JOIN mw_apresentacao ipva ON ipv.id_apresentacao=ipva.id_apresentacao
    INNER JOIN mw_evento e ON ipva.id_evento=e.id_evento
    LEFT JOIN mw_excecao_pagamento ep ON e.id_evento=ep.id_evento
    WHERE ipv.id_pedido_venda=?";
	$param = array($pedido);
	$stmt = executeSQL($mainConnection, $query, $param, true);

    $id_gateway = $stmt["id_gateway"];
    
    // id_gateway	ds_gateway
    // 1	iPagare
    // 2	Fastcash
    // 3	PagSeguro
    // 4	CompreIngressos
    // 5	Braspag
    // 6	Pagar.me
    // 7	TiPagos
    // 8	Cielo
    // 9	Global
    // 10	Paypal
    $ret = false;
    switch ($id_gateway) {
        case 6:
        case "6":
            $ret = true;
        break;
        case 7:
        case "7":
            $ret = true;
        break;
    }

	return $ret;
}

function isPlayWithSplitFindByEvent($play) {

	$mainConnection = mainConnection();

	$query = "SELECT TOP 1 eep.id_gateway
    FROM mw_evento e
    INNER JOIN mw_regra_split rs ON e.id_evento=rs.id_evento
    INNER JOIN mw_recebedor r ON rs.id_recebedor=r.id_recebedor
    LEFT JOIN mw_excecao_pagamento eep ON e.id_evento=eep.id_evento
    WHERE e.id_evento=?";
	$param = array($play);
	$stmt = executeSQL($mainConnection, $query, $param, true);

    $id_gateway = $stmt["id_gateway"];
    
    // id_gateway	ds_gateway
    // 1	iPagare
    // 2	Fastcash
    // 3	PagSeguro
    // 4	CompreIngressos
    // 5	Braspag
    // 6	Pagar.me
    // 7	TiPagos
    // 8	Cielo
    // 9	Global
    // 10	Paypal
    $ret = false;
    switch ($id_gateway) {
        case 6:
        case "6":
            $ret = true;
        break;
        case 7:
        case "7":
            $ret = true;
        break;
    }

	return $ret;
}

?>