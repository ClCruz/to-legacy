<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
session_start();
$mainConnection = mainConnection();

if (!isset($_GET['pedido'])) die('...');

$id_pedido = $_GET['pedido'];

echo "venda dos filhos para o pedido $id_pedido <br/><br/>";

$query = "SELECT TOP 1
			E.ID_BASE, B.DS_NOME_BASE_SQL, PV.ID_CLIENTE, PV.ID_USUARIO_CALLCENTER, PV.DT_PEDIDO_VENDA, PV.VL_TOTAL_PEDIDO_VENDA,
			PV.IN_SITUACAO, PV.IN_RETIRA_ENTREGA, PV.VL_TOTAL_INGRESSOS, PV.VL_FRETE, PV.VL_TOTAL_TAXA_CONVENIENCIA,
			PV.DS_ENDERECO_ENTREGA, PV.DS_COMPL_ENDERECO_ENTREGA, PV.DS_BAIRRO_ENTREGA, PV.DS_CIDADE_ENTREGA,
			PV.DS_CUIDADOS_DE, PV.IN_SITUACAO_DESPACHO, PV.ID_USUARIO_ESTORNO, PV.DT_DESPACHO, PV.DT_HORA_CANCELAMENTO,
			PV.DS_MOTIVO_CANCELAMENTO, PV.ID_ESTADO, PV.CD_CEP_ENTREGA, PV.ID_PEDIDO_IPAGARE, PV.CD_NUMERO_AUTORIZACAO,
			PV.CD_NUMERO_TRANSACAO, PV.CD_BIN_CARTAO, PV.ID_USUARIO_ITAU, PV.DT_ENTREGA_INGRESSO, PV.ID_IP,
			PV.ID_TRANSACTION_BRASPAG, PV.ID_MEIO_PAGAMENTO, PV.NR_PARCELAS_PGTO, PV.NR_BENEFICIO, PV.IN_PACOTE,
			C.DS_DDD_TELEFONE, C.DS_TELEFONE, C.DS_NOME, C.DS_SOBRENOME, C.CD_CPF, C.CD_RG, MP.CD_MEIO_PAGAMENTO
			FROM MW_PEDIDO_VENDA PV
			INNER JOIN MW_ITEM_PEDIDO_VENDA I ON I.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
			INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
			INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A2.ID_APRESENTACAO
			INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
			INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
			INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
			INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO
			WHERE PV.ID_PEDIDO_VENDA = ? and PV.IN_SITUACAO = 'F'";
$params = array($id_pedido);
$dadosPedido = executeSQL($mainConnection, $query, $params, true);

// se algm item do pedido é de um pacote de assinatura
if (!empty($dadosPedido)) {

	echo "assinatura detectada <br/><br/>";

	$dadosPedido['VL_FRETE'] = 0;

	// limpar reserva (evita que os itens do pai que sobraram na reserva sejam incluidos no primeiro filho)
	executeSQL($mainConnection, 'DELETE MW_RESERVA WHERE ID_SESSION = ?', array(session_id()));

	// marcar como um pedido de pacote
	executeSQL($mainConnection, "UPDATE MW_PEDIDO_VENDA SET IN_PACOTE = 'S' WHERE ID_PEDIDO_VENDA = ?", array($id_pedido));

	$conn = getConnection($dadosPedido['ID_BASE']);

	$query = "SELECT
					PA.ID_APRESENTACAO,
					I.INDICE AS ID_CADEIRA,
					I.DS_LOCALIZACAO AS DS_CADEIRA,
					SE.NOMSETOR AS DS_SETOR,
					GETDATE()+1 AS DT_VALIDADE,
					AB2.ID_APRESENTACAO_BILHETE,
					PV.CD_BIN_CARTAO AS CD_BINITAU,
					PV.NR_BENEFICIO,
					A3.CODAPRESENTACAO
				FROM CI_MIDDLEWAY..MW_PEDIDO_VENDA PV
				INNER JOIN CI_MIDDLEWAY..MW_ITEM_PEDIDO_VENDA I ON I.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
				INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = I.ID_APRESENTACAO_BILHETE
				INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
				INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO AND A2.IN_ATIVO = 1
				INNER JOIN CI_MIDDLEWAY..MW_PACOTE P ON P.ID_APRESENTACAO = A2.ID_APRESENTACAO
				INNER JOIN CI_MIDDLEWAY..MW_PACOTE_APRESENTACAO PA ON PA.ID_PACOTE = P.ID_PACOTE
				INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A3 ON A3.ID_APRESENTACAO = PA.ID_APRESENTACAO AND A3.DS_PISO = A.DS_PISO AND A3.IN_ATIVO = 1
				INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB2 ON AB2.ID_APRESENTACAO = A3.ID_APRESENTACAO AND AB2.CODTIPBILHETE = AB.CODTIPBILHETE AND AB2.IN_ATIVO = 1
				INNER JOIN TABAPRESENTACAO TA ON TA.CODAPRESENTACAO = A3.CODAPRESENTACAO
				INNER JOIN TABSALDETALHE TSD ON TSD.CODSALA = TA.CODSALA AND TSD.INDICE = I.INDICE
				INNER JOIN TABSETOR SE ON SE.CODSALA = TSD.CODSALA AND SE.CODSETOR = TSD.CODSETOR
				WHERE PV.ID_PEDIDO_VENDA = ?
				AND A3.ID_APRESENTACAO NOT IN (
					SELECT I2.ID_APRESENTACAO
					FROM CI_MIDDLEWAY..MW_ITEM_PEDIDO_VENDA I2
					INNER JOIN CI_MIDDLEWAY..MW_PEDIDO_VENDA PV2 ON PV2.ID_PEDIDO_VENDA = I2.ID_PEDIDO_VENDA
					WHERE PV2.ID_PEDIDO_PAI = PV.ID_PEDIDO_VENDA
				)
				ORDER BY PA.ID_APRESENTACAO,SE.NOMSETOR, I.DS_LOCALIZACAO";
	$result = executeSQL($conn, $query, $params);

	$apresentacoes = array();

	while ($rs = fetchResult($result)) {
		$apresentacoes[$rs['CODAPRESENTACAO']][] = array(
			$rs['ID_APRESENTACAO'],
			$rs['ID_CADEIRA'],
			session_id(),
			$rs['DS_CADEIRA'],
			$rs['DS_SETOR'],
			$rs['DT_VALIDADE']->format('Ymd'),
			$rs['ID_APRESENTACAO_BILHETE'],
			$rs['CD_BINITAU'],
			$rs['NR_BENEFICIO']
		);
	}

	if (!empty($apresentacoes)) echo "itens encontrados <br/><br/>";

	foreach ($apresentacoes as $codApresentacao => $arrayParams) {
		echo "inserindo itens para o codapresentacao $codApresentacao <br/><br/>";


		// gera mw_reserva
		foreach ($arrayParams as $params) {
			executeSQL($mainConnection,
						'INSERT INTO MW_RESERVA (ID_APRESENTACAO,ID_CADEIRA,ID_SESSION,DS_CADEIRA,DS_SETOR,DT_VALIDADE,ID_APRESENTACAO_BILHETE,CD_BINITAU,NR_BENEFICIO) VALUES (?,?,?,?,?,?,?,?,?)',
						$params);

			executeSQL($conn,
						'INSERT INTO TABLUGSALA (CODAPRESENTACAO,INDICE,CODTIPBILHETE,CODCAIXA,CODVENDA,STAIMPRESSAO,STACADEIRA,CODUSUARIO,CODRESERVA,ID_SESSION) VALUES (?,?,?,?,?,?,?,?,?,?)',
						array($codApresentacao, $params[1], NULL, 255, NULL, 0, 'T', NULL, NULL, session_id()));
		}






		// gera mw_pedido_venda para cada conunto de codapresentacao copiando os dados do pedido pai
		$newMaxId = 0;
		while ($newMaxId === 0) {
			$newMaxId = executeSQL($mainConnection, 'SELECT ISNULL(MAX(ID_PEDIDO_VENDA), 0) + 1 FROM MW_PEDIDO_VENDA', array(), true);
			$newMaxId = $newMaxId[0];

			executeSQL($mainConnection, 'INSERT INTO MW_PEDIDO_VENDA
				(ID_PEDIDO_VENDA, ID_CLIENTE, ID_USUARIO_CALLCENTER, DT_PEDIDO_VENDA, VL_TOTAL_PEDIDO_VENDA,
				IN_SITUACAO, IN_RETIRA_ENTREGA, VL_TOTAL_INGRESSOS, VL_FRETE, VL_TOTAL_TAXA_CONVENIENCIA,
				DS_ENDERECO_ENTREGA, DS_COMPL_ENDERECO_ENTREGA, DS_BAIRRO_ENTREGA, DS_CIDADE_ENTREGA,
				DS_CUIDADOS_DE, IN_SITUACAO_DESPACHO, ID_USUARIO_ESTORNO, DT_DESPACHO, DT_HORA_CANCELAMENTO,
				DS_MOTIVO_CANCELAMENTO, ID_ESTADO, CD_CEP_ENTREGA, ID_PEDIDO_IPAGARE, CD_NUMERO_AUTORIZACAO,
				CD_NUMERO_TRANSACAO, CD_BIN_CARTAO, ID_USUARIO_ITAU, DT_ENTREGA_INGRESSO, ID_IP,
				ID_TRANSACTION_BRASPAG, ID_MEIO_PAGAMENTO, NR_PARCELAS_PGTO, NR_BENEFICIO, ID_PEDIDO_PAI)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
				array($newMaxId, $dadosPedido['ID_CLIENTE'], $dadosPedido['ID_USUARIO_CALLCENTER'], $dadosPedido['DT_PEDIDO_VENDA'], $dadosPedido['VL_TOTAL_PEDIDO_VENDA'],
				$dadosPedido['IN_SITUACAO'], $dadosPedido['IN_RETIRA_ENTREGA'], $dadosPedido['VL_TOTAL_INGRESSOS'], $dadosPedido['VL_FRETE'], $dadosPedido['VL_TOTAL_TAXA_CONVENIENCIA'],
				$dadosPedido['DS_ENDERECO_ENTREGA'], $dadosPedido['DS_COMPL_ENDERECO_ENTREGA'], $dadosPedido['DS_BAIRRO_ENTREGA'], $dadosPedido['DS_CIDADE_ENTREGA'],
				$dadosPedido['DS_CUIDADOS_DE'], $dadosPedido['IN_SITUACAO_DESPACHO'], $dadosPedido['ID_USUARIO_ESTORNO'], $dadosPedido['DT_DESPACHO'], $dadosPedido['DT_HORA_CANCELAMENTO'],
				$dadosPedido['DS_MOTIVO_CANCELAMENTO'], $dadosPedido['ID_ESTADO'], $dadosPedido['CD_CEP_ENTREGA'], $dadosPedido['ID_PEDIDO_IPAGARE'], $dadosPedido['CD_NUMERO_AUTORIZACAO'],
				$dadosPedido['CD_NUMERO_TRANSACAO'], $dadosPedido['CD_BIN_CARTAO'], $dadosPedido['ID_USUARIO_ITAU'], $dadosPedido['DT_ENTREGA_INGRESSO'], $dadosPedido['ID_IP'],
				$dadosPedido['ID_TRANSACTION_BRASPAG'], $dadosPedido['ID_MEIO_PAGAMENTO'], $dadosPedido['NR_PARCELAS_PGTO'], $dadosPedido['NR_BENEFICIO'], $id_pedido));

			$error = sqlErrors();

			$newMaxId = empty($error) ? $newMaxId : 0;
		}






		// gera mw_item_pedido_venda a partir da reserva atual
		$query = "SELECT R.ID_RESERVA, R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, R.ID_CADEIRA, R.DS_CADEIRA, R.DS_SETOR, E.ID_EVENTO, E.DS_EVENTO, ISNULL(LE.DS_LOCAL_EVENTO, B.DS_NOME_TEATRO) DS_NOME_TEATRO, CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) DT_APRESENTACAO, A.HR_APRESENTACAO,
		            AB.VL_LIQUIDO_INGRESSO, AB.DS_TIPO_BILHETE, R.NR_BENEFICIO
		            FROM MW_RESERVA R
		            INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO AND A.IN_ATIVO = '1'
		            INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = '1'
		            INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
		            INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE AND AB.IN_ATIVO = '1'
		            LEFT JOIN MW_LOCAL_EVENTO LE ON E.ID_LOCAL_EVENTO = LE.ID_LOCAL_EVENTO
		            WHERE R.ID_SESSION = ? AND R.DT_VALIDADE >= GETDATE()
		            ORDER BY E.DS_EVENTO, R.ID_APRESENTACAO, R.DS_CADEIRA";
		$params = array(session_id());
		$result = executeSQL($mainConnection, $query, $params);

		$itensPedido = 0;
		$nr_beneficio = null;
		$totalIngressos = 0;
		$totalConveniencia = 0;
		$valorConvenienciaAUX = 0;
		while ($itens = fetchResult($result)) {
		    $itensPedido++;

		    $nr_beneficio = $itens['NR_BENEFICIO'] ? $itens['NR_BENEFICIO'] : $nr_beneficio;

		    $totalIngressos += $itens['VL_LIQUIDO_INGRESSO'];
		    $totalConveniencia += $valorConveniencia + $valorConvenienciaAUX;

		    $params2[$itensPedido] = array($newMaxId, $itens['ID_RESERVA'], $itens['ID_APRESENTACAO'], $itens['ID_APRESENTACAO_BILHETE'], $itens['DS_CADEIRA'], $itens['DS_SETOR'], 1, $itens['VL_LIQUIDO_INGRESSO'], $valorConveniencia + $valorConvenienciaAUX, 'XXXXXXXXXX', $itens['ID_CADEIRA']);
		}

        foreach($params2 as $params) {
	        executeSQL($mainConnection, 'INSERT INTO MW_ITEM_PEDIDO_VENDA (
						                    ID_PEDIDO_VENDA,
						                    ID_RESERVA,
						                    ID_APRESENTACAO,
						                    ID_APRESENTACAO_BILHETE,
						                    DS_LOCALIZACAO,
						                    DS_SETOR,
						                    QT_INGRESSOS,
						                    VL_UNITARIO,
						                    VL_TAXA_CONVENIENCIA,
						                    CODVENDA,
                         					INDICE
						                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ISNULL(?, 0), ?, ?)', $params);
	    }







        // atualiza mw_pedido_venda com os valores dos itens acima
        $query = 'UPDATE MW_PEDIDO_VENDA SET
                        VL_TOTAL_PEDIDO_VENDA = ?
                        ,VL_TOTAL_INGRESSOS = ?
                        ,VL_TOTAL_TAXA_CONVENIENCIA = ?
                        ,NR_PARCELAS_PGTO = ?
			WHERE ID_PEDIDO_VENDA = ?';

		$params = array(($totalIngressos + $dadosPedido['VL_FRETE'] + $totalConveniencia), $totalIngressos, $totalConveniencia, $PaymentDataCollection['NumberOfPayments'], $newMaxId);
		executeSQL($mainConnection, $query, $params);







		// executar proc vb
		if ($dadosPedido["ID_USUARIO_CALLCENTER"]) {
			//receber ingresso
			if ($dadosPedido["IN_RETIRA_ENTREGA"] != 'R')
				$caixa = 252;
			//buscar ingresso
			else
				$caixa = 254;				
		} else {
			//receber ingresso
			if ($dadosPedido["IN_RETIRA_ENTREGA"] != 'R')
				$caixa = 253;
			//buscar ingresso
			else
				$caixa = 255;
		}

		$return_code = -1;

		$proc_assinatura = 'EXEC '.strtoupper($dadosPedido['DS_NOME_BASE_SQL']).'..SP_VEN_INS001_WEB ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?';
		$params_proc_assinatura = array(session_id(), $dadosPedido['ID_BASE'], $dadosPedido['CD_MEIO_PAGAMENTO'], $codApresentacao,
										 $dadosPedido['DS_DDD_TELEFONE'], $dadosPedido['DS_TELEFONE'], ($dadosPedido['DS_NOME'].' '.$dadosPedido['DS_SOBRENOME']),
										 $dadosPedido['CD_CPF'], $dadosPedido['CD_RG'], $newMaxId, $dadosPedido['ID_PEDIDO_IPAGARE'],
										 $dadosPedido['CD_NUMERO_AUTORIZACAO'], $dadosPedido['CD_NUMERO_TRANSACAO'], $dadosPedido['CD_BIN_CARTAO'],
										 $caixa, array(&$return_code, SQLSRV_PARAM_OUT));

		executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('ANTES SP_VEN_INS001_WEB FILHO', json_encode($params_proc_assinatura)));

		$retornoProcedure = executeSQL($mainConnection, $proc_assinatura, $params_proc_assinatura);

		executeSQL($mainConnection, 'INSERT INTO tab_log_gabriel (data, passo, parametros) VALUES (GETDATE(), ?, ?)', array('DEPOIS SP_VEN_INS001_WEB FILHO', json_encode($retornoProcedure)));





		echo "resultado da procedure do vb: <br/>";
		var_dump($retornoProcedure); echo "<br/>";
		// make sure all result sets are stepped through, since the output params may not be set until this happens
		while($rsDebug = sqlsrv_next_result($retornoProcedure)) {var_dump($rsDebug); echo "<br/>";}
		var_dump($return_code); echo "<br/>";
		var_dump(sqlErrors()); echo "<br/>";
		echo "<br/>";




		
		if ($return_code === 0) {
			$erro_processo_assinatura = "erro no processo de assinatura";

			include('errorMail.php');
		}





		// limpar reserva
		executeSQL($mainConnection, 'DELETE MW_RESERVA WHERE ID_SESSION = ?', array(session_id()));
	}


	
	$query = "SELECT P.ID_PACOTE, IPV.INDICE
				FROM MW_APRESENTACAO A
				INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
				INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = A.ID_APRESENTACAO
				INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_APRESENTACAO = A2.ID_APRESENTACAO
				WHERE IPV.ID_PEDIDO_VENDA = ?";
	$params = array($id_pedido);
	$result = executeSQL($mainConnection, $query, $params);

	while ($rs = fetchResult($result)) {

		executeSQL($mainConnection,
					"UPDATE MW_PACOTE_RESERVA SET
						IN_STATUS_RESERVA = 'R',
						DT_HR_TRANSACAO = GETDATE()
					WHERE ID_CLIENTE = ? AND ID_PACOTE = ? AND ID_CADEIRA = ? AND IN_STATUS_RESERVA IN ('A', 'S')",
					array($dadosPedido['ID_CLIENTE'], $rs['ID_PACOTE'], $rs['INDICE']));

    }

	executeSQL($mainConnection, "UPDATE MW_CLIENTE SET IN_ASSINANTE = 'S' WHERE ID_CLIENTE = ?", array($dadosPedido['ID_CLIENTE']));

	limparCookies();

	die("<h1>finalizado</h1>");

}