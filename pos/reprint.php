<?php
// print_order(620201, true);die();
$mainConnection = mainConnection();
echo_header();

if (!isset($_GET['tipo'])) {

	echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione o tipo:</WRITE_AT>");

	$tipo_options = array('Comprovante', 'Ingresso', 'Últimos 10 Pedidos');

	echo_select('tipo', $tipo_options, 3);

	echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";
	echo "<GET TYPE=SERIALNO NAME=pos_serial>";

	echo "<POST>";

	die();
}

switch ($_GET['tipo']) {

	// reimpressao de comprovantes
	case 0:
	if ($_GET['RESPAG'] == 'APROVADO') {
		echo "<GET TYPE=HIDDEN NAME=reset VALUE=1>";
	} else {
		$idterm_tef = getIDPOS($_GET['pos_serial']);
		echo "<PAGAMENTO IPTEF=$ip_tef PORTATEF=$porta_tef CODLOJA=$codloja_tef IDTERM=$idterm_tef TIPO=GERENCIAL VALOR=$valor PAGRET=RESPAG BIN=BINCARTAO NINST=NOMEINST NSU=NSUAUT AUT=CAUT NPAR=PARC MODPAG=TIPOTRANS>";
	}
	break;

	// reimpressao de ingressos
	case 1:
	case 2:
	if (isset($_GET["pedido"])) {
		$query = "SELECT DS_LOCALIZACAO FROM MW_ITEM_PEDIDO_VENDA WHERE ID_PEDIDO_VENDA = ?";
		$result = executeSQL($mainConnection, $query, array($_GET['pedido']));

		$lugares = array();
		$linha = '';
		while ($rs = fetchResult($result)) {
			if (strlen($linha.$rs['DS_LOCALIZACAO'].', ') <= 28) {
				$linha .= $rs['DS_LOCALIZACAO'].', ';
			} else {
				$lugares[] = $linha;
				$linha = $rs['DS_LOCALIZACAO'].', ';
			}
		}
		$lugares[] = $linha;

		end($lugares);
		$key = key($lugares);
		$lugares[$key] = preg_replace('/\,?\s?$/', '', $lugares[$key]);
		reset($lugares);

		$query ="SELECT
					E.DS_EVENTO,
					A.DT_APRESENTACAO,
					A.HR_APRESENTACAO,
					A.DS_PISO,
					R.ID_APRESENTACAO_BILHETE,
					COUNT(R.ID_RESERVA) AS QTD_INGRESSOS,
					AB.DS_TIPO_BILHETE
				FROM MW_ITEM_PEDIDO_VENDA R
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
				INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
				WHERE ID_PEDIDO_VENDA = ?
				GROUP BY
					E.DS_EVENTO,
					A.DT_APRESENTACAO,
					A.HR_APRESENTACAO,
					A.DS_PISO,
					R.ID_APRESENTACAO_BILHETE,
					AB.DS_TIPO_BILHETE";

		$result = executeSQL($mainConnection, $query, array($_GET['pedido']));

		$total_ingressos = 0;
		$total_servico = 0;

		$confirmacao_options = array();

		while ($rs = fetchResult($result)) {

			if ($last_title != $rs['DS_EVENTO'].$rs['DT_APRESENTACAO']->format('d/m/Y').$rs['HR_APRESENTACAO'].$rs['DS_PISO']) {

				if (count($confirmacao_options) > 2) $confirmacao_options[] = ' ';

				$confirmacao_options[] = utf8_encode2($rs['DS_EVENTO']);
				$confirmacao_options[] = $rs['DT_APRESENTACAO']->format('d/m/Y').' '.$rs['HR_APRESENTACAO'];
				$confirmacao_options[] = utf8_encode2($rs['DS_PISO']);
				
				$last_title = $rs['DS_EVENTO'].$rs['DT_APRESENTACAO']->format('d/m/Y').$rs['HR_APRESENTACAO'].$rs['DS_PISO'];

				$confirmacao_options[] = ' ';
			}

			$confirmacao_options[] = utf8_encode2(str_pad(substr(remove_accents($rs['DS_TIPO_BILHETE'], false), 0, 24), 24, ' ', STR_PAD_RIGHT).' x'.str_pad($rs['QTD_INGRESSOS'], 2, ' ', STR_PAD_LEFT));
		}

		foreach ($lugares as $key => $value) {
			$confirmacao_options[] = $value;
		}

		$confirmacao_options[] = ' ';
		$confirmacao_options[999999999] = 'Voltar';
		$confirmacao_options[888888888] = 'Confirmar';

		echo_select('confirmacao', $confirmacao_options, 0);

		echo "<GET TYPE=HIDDEN NAME=id_pedido VALUE=".$_GET['pedido'].">";
		echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";

	} elseif (isset($_GET["confirmacao"])) {

		include('../settings/Log.class.php');

		$log = new Log($_SESSION['admin']);
	    $log->__set('funcionalidade', 'Reimpressão POS');
	    $log->__set('parametros', array($_GET['id_pedido']));
	    $log->__set('log', "Pedido ?");
	    $log->save($mainConnection);
		
		print_order($_GET['id_pedido'], true);

		echo "<GET TYPE=HIDDEN NAME=reset VALUE=1>";

	} elseif (isset($_GET["cpf"]) OR $_GET['tipo'] == 2) {

		if ($_GET['tipo'] == 2) {

			$query = "SELECT DISTINCT TOP 10
						PV.ID_PEDIDO_VENDA,                
						PV.DT_PEDIDO_VENDA, 
						PV.VL_TOTAL_PEDIDO_VENDA,
						CONVERT(datetime, A.dt_apresentacao + REPLACE(A.hr_apresentacao, 'h', ':'), 103) AS DT_APRESENTACAO
					FROM MW_PEDIDO_VENDA PV
					INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
					INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO AND A.IN_ATIVO = 1
					INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = 1
					WHERE ID_PEDIDO_IPAGARE = ? AND IN_SITUACAO = 'F'
					AND CONVERT(DATETIME, CONVERT(VARCHAR, A.DT_APRESENTACAO, 112) + ' ' + LEFT(A.HR_APRESENTACAO,2) + ':' + RIGHT(A.HR_APRESENTACAO,2) + ':00') >= GETDATE()
					ORDER BY ID_PEDIDO_VENDA DESC";

		    $params = array($_GET['pos_serial']);
		    $result = executeSQL($mainConnection, $query, $params);

		    if (!hasRows($result)) {
		    	display_error("Não existem vendas realizadas nesse POS.", utf8_decode("Atenção"));
		    	die();
		    }

		} else {

			$query = "SELECT ID_CLIENTE, DS_NOME, DS_SOBRENOME FROM MW_CLIENTE WHERE CD_CPF = ?";
		    $params = array($_GET['cpf']);
		    $rs = executeSQL($mainConnection, $query, $params, true);
		    $id_cliente = $rs['ID_CLIENTE'];
		    $nome_cliente = $rs['DS_NOME'] ." ". $rs['DS_SOBRENOME'];

		    // se foi vendido com o usuario generico
		    $query_aux = ($id_cliente == 493205 ? 'AND ID_PEDIDO_IPAGARE = ?' : '');

			$query = "SELECT DISTINCT PV.ID_PEDIDO_VENDA,                
						PV.DT_PEDIDO_VENDA, 
						PV.VL_TOTAL_PEDIDO_VENDA,
						CONVERT(datetime, A.dt_apresentacao + REPLACE(A.hr_apresentacao, 'h', ':'), 103) AS DT_APRESENTACAO
					FROM MW_PEDIDO_VENDA PV
					INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
					INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO AND A.IN_ATIVO = 1
					INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = 1
					WHERE ID_CLIENTE = ? AND IN_SITUACAO = 'F' $query_aux
					AND CONVERT(DATETIME, CONVERT(VARCHAR, A.DT_APRESENTACAO, 112) + ' ' + LEFT(A.HR_APRESENTACAO,2) + ':' + RIGHT(A.HR_APRESENTACAO,2) + ':00') >= GETDATE()
					ORDER BY DT_APRESENTACAO";

			// se foi vendido com o usuario generico
		    $params = ($id_cliente == 493205 ? array($id_cliente, $_GET['pos_serial']) : array($id_cliente));
		    $result = executeSQL($mainConnection, $query, $params);

		    if (!hasRows($result)) {
		    	display_error("Não existem ingressos para o CPF informado.", utf8_decode("Atenção"));
		    	die();
		    }

		}

	    $pedido_options = array(999999999 => 'Voltar');

		while ($rs = fetchResult($result)) {
			$pedido_options[$rs['ID_PEDIDO_VENDA'].'_'.count($pedido_options)] = $rs['ID_PEDIDO_VENDA'] ."   ". $rs['DT_APRESENTACAO']->format('d/m/y') . str_pad(number_format($rs['VL_TOTAL_PEDIDO_VENDA'], 2, ',', ''), 11, ' ', STR_PAD_LEFT);
		}

	    echo "<WRITE_AT LINE=5 COLUMN=0> $nome_cliente</WRITE_AT>";
	    echo "<WRITE_AT LINE=7 COLUMN=0> Selecione o Pedido:</WRITE_AT>";
		echo "<WRITE_AT LINE=9 COLUMN=0> Pedido | Dt. Apres. | Valor</WRITE_AT>";

	    echo_select('pedido', $pedido_options, 7);

	    echo "<GET TYPE=SERIALNO NAME=pos_serial>";

	} else {

		echo "<GET TYPE=SERIALNO NAME=pos_serial>";

		if ($_GET['tipo'] == 2) {
			// ultimos pedidos desse POS
		} else {
			echo "<WRITE_AT LINE=7 COLUMN=0> Informe o CPF:</WRITE_AT>";
			echo "<GET TYPE=FIELD NAME=cpf SIZE=11 COL=1 LIN=10>";
		}

	}
	break;
}

echo "<GET TYPE=HIDDEN NAME=tipo VALUE={$_GET['tipo']}>";

echo "<POST>";