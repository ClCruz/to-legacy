<?php

$mainConnection = mainConnection();

echo "<GET TYPE=SERIALNO NAME=pos_serial>";

// se a quantidade estiver definida entao selecionar os ingressos (nao numerados/marcados)
if (isset($_GET['quantidade']) and $_GET['quantidade'] != '') {

	// define se a reserva pode ser feita
	$reservar = false;

	// se a quantidade for 0 entao apagar todos os bilhetes do tipo selecionado
	if ($_GET['quantidade'] == 0) {

		$mainConnectionAux = $mainConnection;

		$query = "SELECT ID_APRESENTACAO, ID_CADEIRA FROM MW_RESERVA WHERE ID_APRESENTACAO_BILHETE = ? AND ID_SESSION = ?";
		$params = array($_GET['bilhete'], session_id());
		$resultApagar = executeSQL($mainConnectionAux, $query, $params);
		
		$_GET['action'] = 'delete';

		while ($rsApagar = fetchResult($resultApagar)) {
			$_REQUEST['apresentacao'] = $rsApagar['ID_APRESENTACAO'];
			$_REQUEST['id'] = $rsApagar['ID_CADEIRA'];

			ob_start();
			require '../comprar/atualizarPedido.php';
			$response = ob_get_clean();

			if (substr($response, 0, 4) != 'true') {
				display_error($response);
			}
		}
	}

	// se a quantidade nao for 0 continuar
	else {
		$query = "SELECT COUNT(R.ID_APRESENTACAO_BILHETE) QT_TIPO_BILHETE FROM MW_RESERVA R WHERE ID_SESSION = ? AND R.ID_APRESENTACAO_BILHETE = ?";
		$rs = executeSQL($mainConnection, $query, array(session_id(), $_GET['bilhete']), true);

		$bilhetes_reservados = $rs['QT_TIPO_BILHETE'];
			
		// checar limites promocionais
		$query = "SELECT E.ID_BASE
					FROM MW_APRESENTACAO_BILHETE AB
					INNER JOIN MW_APRESENTACAO A ON AB.ID_APRESENTACAO = A.ID_APRESENTACAO AND AB.IN_ATIVO = 1
					INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
					WHERE AB.ID_APRESENTACAO_BILHETE = ?";
		$rs = executeSQL($mainConnection, $query, array($_GET['bilhete']), true);

		$conn = getConnection($rs['ID_BASE']);
		$query = "SELECT
						P.CODTIPPROMOCAO,
						ISNULL(CE.QT_PROMO_POR_CPF, P.QT_PROMO_POR_CPF) AS QT_PROMO_POR_CPF
					FROM TABTIPBILHETE B
					INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.CODTIPBILHETE = B.CODTIPBILHETE
					INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = AB.ID_APRESENTACAO
					INNER JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE P ON P.ID_PROMOCAO_CONTROLE = B.ID_PROMOCAO_CONTROLE
					LEFT JOIN CI_MIDDLEWAY..MW_CONTROLE_EVENTO CE ON CE.ID_PROMOCAO_CONTROLE = P.ID_PROMOCAO_CONTROLE
						AND CE.ID_EVENTO = A.ID_EVENTO
					WHERE AB.ID_APRESENTACAO_BILHETE = ?";
		$rs = executeSQL($conn, $query, array($_GET['bilhete']), true);
		
		// se codigos promocionais foram enviados entao validar
		if (isset($_GET['validar_codigos'])) {

			// se for promocao BIN
			if (in_array($rs['CODTIPPROMOCAO'], array(4, 7))) {

				$query = "SELECT 1 FROM MW_RESERVA R WHERE R.ID_SESSION = ? AND CD_BINITAU IS NOT NULL AND CD_BINITAU != ?";
			    $result = executeSQL($mainConnection, $query, array(session_id(), $_GET['codigo'][0]));

			    // se outro BIN já foi informado
			    if (hasRows($result)) {
			        display_error("Não é possível utilizar dois ou mais códigos promocionais de cartões diferentes.<br/><br/>Por favor, retorne e selecione outro tipo de ingresso.");
			    }

			    // se este foi o unico BIN informado
			    else {
					$query = "SELECT TOP 1 1
			                FROM
			                CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB
							INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.ID_APRESENTACAO = AB.ID_APRESENTACAO
			                INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
			                INNER JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = TTB.ID_PROMOCAO_CONTROLE AND A.DT_APRESENTACAO BETWEEN PC.DT_INICIO_PROMOCAO AND PC.DT_FIM_PROMOCAO
			                INNER JOIN CI_MIDDLEWAY..MW_CARTAO_PATROCINADO CP ON CP.ID_PATROCINADOR = PC.ID_PATROCINADOR
			                WHERE (
			                    (PC.CODTIPPROMOCAO in (4, 7) AND CP.CD_BIN = ?)
			                    OR
			                    (PC.CODTIPPROMOCAO = 7 AND CP.CD_BIN = SUBSTRING(?, 1, 5))
			                )
			                AND AB.ID_APRESENTACAO_BILHETE = ?";
			        $params = array($_GET['codigo'][0], $_GET['codigo'][0], $_GET['bilhete']);

			        $result = executeSQL($conn, $query, $params);

			        // se for um BIN valido
			        if (hasRows($result)) {

			    		// quantidade nova for maior que o limite permitido
			    		if ($_GET['quantidade'] > $rs['QT_PROMO_POR_CPF']) {

		    				$_GET['quantidade'] = $rs['QT_PROMO_POR_CPF'];
		    				
		    				display_error("Apenas {$_GET['quantidade']} ingresso(s) promocional(is) foi(ram) selecionado(s) devido ao limite da promoção.", "Aviso");

		    				$reservar = true;
		    				$_GET['bin'] = $_GET['codigo'][0];
		    			}
			    		// se a quantidade estiver dentro do limite
			    		else {
			    			$reservar = true;
			    			$_GET['bin'] = $_GET['codigo'][0];
			    		}
			        }
			        // se nao for um BIN valido
			        else {
			        	display_error("Este cartão não é participante da promoção vigente para esta apresentação!<br>Informe outro cartão ou indique outro tipo de ingresso não participante da promoção.");
			        }
			    }
			}

			// se nao for promocao BIN
			else {

				$bilhetes_validos = array();

				foreach ($_GET['codigo'] as $key => $codigo) {

					$query = "SELECT TOP 1 P.ID_PROMOCAO,
		                        P.ID_SESSION,
		                        P.ID_PEDIDO_VENDA,
		                        PC.CODTIPPROMOCAO
		                    FROM CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB
		                    INNER JOIN TABTIPBILHETE TTB
		                        ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
		                    INNER JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC
		                        ON PC.ID_PROMOCAO_CONTROLE = TTB.ID_PROMOCAO_CONTROLE
		                            AND PC.IN_ATIVO = 1
		                    INNER JOIN CI_MIDDLEWAY..MW_PROMOCAO P
		                        ON P.ID_PROMOCAO_CONTROLE = PC.ID_PROMOCAO_CONTROLE
		                    WHERE AB.ID_APRESENTACAO_BILHETE = ?
		                        AND P.CD_PROMOCIONAL = ?
		                    ORDER BY P.ID_SESSION,
		                        P.ID_PEDIDO_VENDA";

			        $result = executeSQL($conn, $query, array($_GET['bilhete'], $codigo));

			        if (hasRows($result)) {
			            $rs = fetchResult($result);

			            $erros = array(
			                // codigo fixo
			                '1' => 'Não existem mais ingressos disponíveis para este tipo de promoção. Por favor, selecione outro tipo de ingresso.',
			                // codigo aleatorio
			                '2' => 'Este código promocional já foi utilizado. Por favor, informe outro código promocional ou selecione outro tipo de ingresso.',
			                // importacao do csv
			                '3' => 'Este código promocional já foi utilizado. Por favor, informe outro código promocional ou selecione outro tipo de ingresso.',
			                // convite
			                '5' => 'Convites esgotados.'
			            );

			            // se tiver alguma reserva ou pedido utilizando esse codigo
			            if (!empty($rs['ID_SESSION']) || !empty($rs['ID_PEDIDO_VENDA'])) {

			            	display_error($erros[$rs['CODTIPPROMOCAO']] . " Código: $codigo");

			            	unset($_GET['codigo'][$key]);

			            }
			            // se nao tiver nenhuma reserva ou pedido utilizando esse codigo
			            else {

				            $query = "UPDATE MW_PROMOCAO SET ID_SESSION = ? WHERE ID_PROMOCAO = ?";
				            executeSQL($mainConnection, $query, array(session_id(), $rs['ID_PROMOCAO']));

				            $bilhetes_validos[] = $rs['ID_PROMOCAO'];
				        }
			        } else {

			            display_error("Código promocional inexistente: $codigo");

			            unset($_GET['codigo'][$key]);
			        }
			    }

			    if (count($bilhetes_validos) > 0) {

			    	if ($_GET['quantidade'] != count($bilhetes_validos)) {
			    		display_error("Apenas ".count($bilhetes_validos)." bilhete(s) foi(ram) selecionado(s).", "Aviso");
			    	}

			    	$reservar = true;
			    	$_GET['quantidade'] = count($bilhetes_validos);
			    }
			}
		}

		// senao checar se o bilhete pertence a uma promocao
		else {

			// se for um bilhete promocional
			if (isset($rs['CODTIPPROMOCAO'])) {

				// para bilhetes numerados/marcados
				if (isset($_GET['cadeira']) and $rs['QT_PROMO_POR_CPF'] < $_GET['quantidade'] + $bilhetes_reservados) {

    				$_GET['quantidade'] = 0;
    				
    				display_error("Devido ao limite da promoção você só pode selecionar {$rs['QT_PROMO_POR_CPF']} bilhetes desse tipo.", "Aviso");
				}

				// para bilhetes nao numerados/marcados
				elseif ($rs['QT_PROMO_POR_CPF'] < $_GET['quantidade']) {

    				$_GET['quantidade'] = $rs['QT_PROMO_POR_CPF'];
    				
    				display_error("Apenas {$_GET['quantidade']} ingresso(s) promocional(is) foi(ram) selecionado(s) devido ao limite da promoção.", "Aviso");

				}

				// se for convite checar pela necessidade de codigo promocional
				if ($rs['CODTIPPROMOCAO'] == 5) {
					$bilhetes = comboPrecosIngresso(null, $_GET['apresentacao'], null, $_GET['bilhete'], false, true);
					$selecionado = $bilhetes[$_GET['bilhete']];
					$codPreValidado = $selecionado['codPreValidado'];
				}

				// se for bin pegar o codigo promocional apenas uma vez
				$codes_to_get = ((in_array($rs['CODTIPPROMOCAO'], array(4, 7)) and $_GET['quantidade'] > 0) ? 1 : $_GET['quantidade']);

				if ($codes_to_get > 0) {
					for ($i=1; $i <= $codes_to_get; $i++) {
						if (isset($codPreValidado)) {
							echo "<GET TYPE=HIDDEN NAME=codigo[] VALUE=$codPreValidado>";
						} else {

							echo_header();
							
							$line = $header_lines;
							echo utf8_decode("<WRITE_AT LINE=$line COLUMN=0> Código promocional/BIN:</WRITE_AT>");

							if ($rs[0] != 4) {
								$line += 2;
								echo utf8_decode("<WRITE_AT LINE=$line COLUMN=0> Bilhete $i:</WRITE_AT>");
							}

							$line += 3;
							echo "<GET TYPE=FIELD NAME=codigo[] SIZE=28 COL=1 LIN=$line>";
						}
					}

					foreach ($_GET as $key => $value) {
						if (is_array($value)) {
							foreach ($value as $v) {
								echo "<GET TYPE=HIDDEN NAME={$key}[] VALUE=$v>";
							}
						} else {
							echo "<GET TYPE=HIDDEN NAME=$key VALUE=$value>";
						}
					}

					echo "<GET TYPE=HIDDEN NAME=validar_codigos VALUE=1>";

					echo "<POST>";
					die();
				}
			}

			// se nao for um bilhete promocional
			else {

				$reservar = true;
			}
		}
	}

	// reservar bilhetes/lugares
	if ($reservar) {

		// para bilhetes numerados/marcados
		if (isset($_GET['cadeira'])) {

			$query = "SELECT ID_RESERVA
						FROM MW_RESERVA
						WHERE ID_SESSION = ? AND ID_CADEIRA = ? AND ID_APRESENTACAO_BILHETE IS NULL";

			$rs = executeSQL($mainConnection, $query, array(session_id(), $_GET['cadeira']), true);

			$_GET['action'] = 'update';
			$_POST['apresentacao'][0] = $_GET['apresentacao'];
			$_POST['cadeira'][0] = $_GET['cadeira'];
			$_POST['reserva'][0] = $rs['ID_RESERVA'];
			$_POST['valorIngresso'][0] = $_GET['bilhete'];
			$_POST['bin'][0] = $_GET['bin'];
			$_POST['tipoBin'][0] = ($_GET['bin'] == null ? (count($bilhetes_validos) > 0 ? 'promocao' : null) : 'itau');
			$_GET['pos'] = 1;
			$_POST['estado'] = '';

			ob_start();
			require_once '../comprar/atualizarPedido.php';
			$response = ob_get_clean();

			// tratar erro
			if ($response != 'true') {
				display_error($response);
			} else {
				// bilhetes_validos = array contendo os ids da tabela mw_promocao que foram validados e precisam ser reinseridos
				if (isset($bilhetes_validos)) {
					$query = "UPDATE MW_PROMOCAO SET ID_SESSION = ? WHERE ID_PROMOCAO = ?";

					foreach ($bilhetes_validos as $value) {
						$params = array(session_id(), $value);
						executeSQL($mainConnection, $query, $params);
					}
				}
			}

		}

		// para bilhetes nao numerados/marcados
		else {

			$query = "SELECT AB.CODTIPBILHETE, E.ID_BASE, A.CODAPRESENTACAO
						FROM MW_APRESENTACAO_BILHETE AB
						INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = AB.ID_APRESENTACAO
						INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
						WHERE AB.ID_APRESENTACAO_BILHETE = ?";

			$rs = executeSQL($mainConnection, $query, array($_GET['bilhete']), true);

			$_POST['numIngressos'] = $_GET['quantidade'];
			$_GET['codtipbilhete'] = $rs['CODTIPBILHETE'];
			$_POST['teatro'] = $rs['ID_BASE'];
			$_POST['codapresentacao'] = $rs['CODAPRESENTACAO'];
			$_POST['apresentacao'] = $_GET['apresentacao'];
			$_GET['action'] = 'noNum';
			$_GET['pos'] = 1;

			ob_start();
			require_once '../comprar/atualizarPedido.php';
			$response = ob_get_clean();

			// tratar erro
			if ($response != 'true') {
				display_error($response);
			} else {
				// bilhetes_validos = array contendo os ids da tabela mw_promocao que foram validados e precisam ser reinseridos
				if (isset($bilhetes_validos)) {
					$query = "UPDATE MW_PROMOCAO SET ID_SESSION = ? WHERE ID_PROMOCAO = ?";

					foreach ($bilhetes_validos as $value) {
						$params = array(session_id(), $value);
						executeSQL($mainConnection, $query, $params);
					}
				}
			}

		}
	} else {
		// para bilhetes numerados/marcados quando a selecao do tipo de bilhete falha
		if (isset($_GET['cadeira'])) {
			echo "<GET TYPE=HIDDEN NAME=apresentacao VALUE={$_GET['apresentacao']}>";
			echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";
			echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=bilhete>";
			echo "<GET TYPE=HIDDEN NAME=cadeira VALUE={$_GET['cadeira']}>";
			echo "<GET TYPE=HIDDEN NAME=bilhete_m VALUE=1>";

			echo "<POST>";

			die();
		}
	}
}


// se as cadeiras estiverem definidas entao verificar disponibilidade e reservar
if (isset($_GET['cadeira']) and isset($_GET['reservar'])) {

	$query = "SELECT E.ID_BASE, A.CODAPRESENTACAO FROM MW_APRESENTACAO A INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO WHERE A.ID_APRESENTACAO = ?";
	$params = array($_GET['apresentacao']);
	$rs = executeSQL($mainConnection, $query, $params, true);

	$conn = getConnection($rs['ID_BASE']);

	$query = 'SELECT 1 FROM TABLUGSALA WHERE CODAPRESENTACAO = ? AND INDICE = ?';
	$params = array($rs['CODAPRESENTACAO'], $_GET['cadeira']);
	$rs2 = executeSQL($conn, $query, $params, true);

	$reservar = empty($rs2);

	// reservar bilhetes/lugares
	if ($reservar) {

		$query = "SELECT S.NOMOBJETO, SE.NOMSETOR
					FROM TABSALDETALHE S
					INNER JOIN TABSETOR SE ON SE.CODSALA = S.CODSALA AND SE.CODSETOR = S.CODSETOR
					INNER JOIN TABAPRESENTACAO A ON A.CODSALA = S.CODSALA
					WHERE A.CODAPRESENTACAO = ? AND S.INDICE = ?";
		$params = array($rs['CODAPRESENTACAO'], $_GET['cadeira']);
		$rs2 = executeSQL($conn, $query, $params, true);

		$_POST['apresentacao'] = $_GET['apresentacao'];
		$_GET['action'] = 'add';
		$_REQUEST['id'] = $_GET['cadeira'];
		$_POST['name'] = $rs2['NOMOBJETO'];
		$_POST['setor'] = $rs2['NOMSETOR'];

		ob_start();
		require '../comprar/atualizarPedido.php';
		$response = ob_get_clean();

		// tratar erro
		if (substr($response, 0, 4) != 'true') {
			$error = true;
		}
	}

	// se algum erro for encontrado
	if (!$reservar or $error) {

		display_error("O lugar selecionado está indisponíveis no momento. Favor selecionar outro lugar.");
		
		echo "<GET TYPE=HIDDEN NAME=history VALUE=999999999>";
	}
	// se nenhum erro for encontrado
	else {

		echo "<GET TYPE=HIDDEN NAME=apresentacao VALUE={$_GET['apresentacao']}>";
		echo "<GET TYPE=HIDDEN NAME=cadeira VALUE={$_GET['cadeira']}>";
		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=bilhete>";
		echo "<GET TYPE=HIDDEN NAME=bilhete_m VALUE=1>";
		echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";

	}

	echo "<POST>";

	die();
}


echo_header();


// se o valor de bilhete for 888888888 ou 777777777 entao finalizar a venda
if ($_GET['bilhete'] == 888888888 or $_GET['bilhete'] == 777777777
	or $_GET['fileira'] == 888888888 or $_GET['fileira'] == 777777777) {

	$query = "SELECT DS_CADEIRA FROM MW_RESERVA WHERE ID_SESSION = ?";
	$result = executeSQL($mainConnection, $query, array(session_id()));

	$lugares = array();
	$linha = '';
	while ($rs = fetchResult($result)) {
		if (strlen($linha.$rs['DS_CADEIRA'].', ') <= 28) {
			$linha .= $rs['DS_CADEIRA'].', ';
		} else {
			$lugares[] = $linha;
			$linha = $rs['DS_CADEIRA'].', ';
		}
	}
	$lugares[] = $linha;

	end($lugares);
	$key = key($lugares);
	$lugares[$key] = preg_replace('/\,?\s?$/', '', $lugares[$key]);
	reset($lugares);

	$query = "SELECT
					E.DS_EVENTO,
					A.DT_APRESENTACAO,
					A.HR_APRESENTACAO,
					A.DS_PISO,
					R.ID_APRESENTACAO_BILHETE,
					COUNT(R.ID_RESERVA) AS QTD_INGRESSOS,
					AB.DS_TIPO_BILHETE,
					AB.VL_LIQUIDO_INGRESSO
				FROM MW_RESERVA R
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
				INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
				WHERE ID_SESSION = ?
				GROUP BY
					E.DS_EVENTO,
					A.DT_APRESENTACAO,
					A.HR_APRESENTACAO,
					A.DS_PISO,
					R.ID_APRESENTACAO_BILHETE,
					AB.DS_TIPO_BILHETE,
					AB.VL_LIQUIDO_INGRESSO";

	$result = executeSQL($mainConnection, $query, array(session_id()));

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

		$valores = str_pad($rs['QTD_INGRESSOS'].'x', 8, ' ', STR_PAD_LEFT) . str_pad(number_format(($rs['VL_LIQUIDO_INGRESSO'] + obterValorServico($rs['ID_APRESENTACAO_BILHETE'], false, null, true)), 2, ',', '').' ', 21, ' ', STR_PAD_LEFT);
		$confirmacao_options[] = utf8_encode2($rs['DS_TIPO_BILHETE']);
		$confirmacao_options[] = $valores;

		$total_ingressos += $rs['VL_LIQUIDO_INGRESSO'] * $rs['QTD_INGRESSOS'];
		$total_servico += obterValorServico($rs['ID_APRESENTACAO_BILHETE'], false, null, true) * $rs['QTD_INGRESSOS'];

		$last_bilhete = $rs['ID_APRESENTACAO_BILHETE'];
	}

	foreach ($lugares as $key => $value) {
		$confirmacao_options[] = $value;
	}

	if ($total_servico == 0) {
		$total_servico += obterValorServico($last_bilhete, true, null, true);
	}

	echo "<WRITE_AT LINE=15 COLUMN=0> TOTAL INGRESSOS".str_pad(number_format($total_ingressos, 2, ',', '').' ', 14, ' ', STR_PAD_LEFT)."</WRITE_AT>";
	echo utf8_decode("<WRITE_AT LINE=16 COLUMN=0> TOTAL SERVIÇO".str_pad(number_format($total_servico, 2, ',', '').' ', 16, ' ', STR_PAD_LEFT)."</WRITE_AT>");
	echo "<WRITE_AT LINE=17 COLUMN=0> TOTAL".str_pad(number_format($total_ingressos+$total_servico, 2, ',', '').' ', 24, ' ', STR_PAD_LEFT)."</WRITE_AT>";

	$confirmacao_options[999999999] = 'Voltar';
	$confirmacao_options[] = 'Confirmar';

	echo_select('confirmacao', $confirmacao_options, 0, 10);

	echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";
	if ($_GET['bilhete'] == 777777777 or $_GET['fileira'] == 777777777) {
		echo "<GET TYPE=HIDDEN NAME=venda_dinheiro VALUE=1>";
	}

	echo "<POST>";
	die();
}

// confirmacao do pedido - pagamento
if (isset($_GET['confirmacao'])) {

	$query = 'SELECT TOP 1 1
				FROM MW_RESERVA R
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
				WHERE ID_SESSION = ? AND E.IN_OBRIGA_CPF_POS = 1';
	$rs = executeSQL($mainConnection, $query, array(session_id()), true);

	if ($rs[0]) {
		$validar_limite_cpf = true;
	} else {
		$_SESSION['user'] = 493205;
		$validar_limite_cpf = false;
	}

	if (verificaCPF($_GET['cpf']) and strlen($_GET['telefone']) > 7) {

		$query = "SELECT ID_CLIENTE FROM MW_CLIENTE WHERE CD_CPF = ?";
		$rs = executeSQL($mainConnection, $query, array($_GET['cpf']), true);

		if (isset($rs['ID_CLIENTE'])) {
			$_SESSION['user'] = $rs['ID_CLIENTE'];
		} else {
			$dados = array(
				'cpf' => $_GET['cpf'],
				'ddd_celular' => substr($_GET['telefone'], 0, 2),
				'celular' => substr($_GET['telefone'], 2)
			);

			$id = create_user($dados);

			if (is_numeric($id)) {
				$_SESSION['user'] = $id;
			} else {
				display_error("Não foi possível criar o usuário. Por favor, tente novamente.");
			}
		}

	}

	if (isset($_SESSION['user'])) {

		// validacoes da compra normal
		ob_start();
		require('../comprar/verificarBilhetes.php');
		$response = ob_get_clean();
		if ($msgBilheteInvalido) {
			$error[] = $msgBilheteInvalido;
		}

		ob_start();
		require('../comprar/verificarServicosPedido.php');
		$response = ob_get_clean();
		if ($response != '') {
			$error[] = $msgServicosPorPedido;
		}

		if ($validar_limite_cpf) {
			ob_start();
			require('../comprar/verificarLimitePorCPF.php');
			$response = ob_get_clean();
			if (count($limitePorCPF_POS) > 0) {
				foreach ($limitePorCPF_POS as $value) {
					$error[] = $value;
				}
			}
		}

		$bin = executeSQL($mainConnection, "SELECT CD_BINITAU FROM MW_RESERVA WHERE ID_SESSION = ? AND CD_BINITAU IS NOT NULL", array(session_id()), true);
		$bin = $bin[0];

		ob_start();
		$_POST['numCartao'] = $bin;
		$_POST['pos'] = 1;
		require('../comprar/validarBin.php');
		$response = ob_get_clean();
		if ($response != '') {
			$error[] = $response;
		}

		ob_start();
		require('../comprar/validarLote.php');
		$response = ob_get_clean();
		if ($response != '') {
			$error[] = $response;
		}

		// se estiver tudo ok pedir pelo pagarmento
		if (!isset($error)) {

			$query = "SELECT
							R.ID_APRESENTACAO_BILHETE,
							COUNT(R.ID_RESERVA) AS QTD_INGRESSOS,
							AB.VL_LIQUIDO_INGRESSO
						FROM MW_RESERVA R
						INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
						INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
						WHERE ID_SESSION = ?
						GROUP BY
							R.ID_APRESENTACAO_BILHETE,
							AB.VL_LIQUIDO_INGRESSO";

			$result = executeSQL($mainConnection, $query, array(session_id()));

			$total_ingressos = 0;
			$total_servico = 0;

			while ($rs = fetchResult($result)) {
				$total_ingressos += $rs['VL_LIQUIDO_INGRESSO'] * $rs['QTD_INGRESSOS'];
				$total_servico += obterValorServico($rs['ID_APRESENTACAO_BILHETE'], false, null, true) * $rs['QTD_INGRESSOS'];

				$last_bilhete = $rs['ID_APRESENTACAO_BILHETE'];
			}

			if ($total_servico == 0) {
				$total_servico += obterValorServico($last_bilhete, true, null, true);
			}

			$total_geral = $total_ingressos + $total_servico;

			$query = "SELECT COUNT(1) FROM MW_PROMOCAO WHERE ID_SESSION = ?";
			$rs = executeSQL($mainConnection, $query, array(session_id()), true);
			$is_promocional = ($rs[0] > 0);

			if (($total_geral == 0 and $is_promocional) or $_GET['venda_dinheiro'] == 1) {
				echo "<GET TYPE=HIDDEN NAME=RESPAG VALUE=APROVADO>";
				echo "<GET TYPE=HIDDEN NAME=BIN VALUE=000000>";
				echo "<GET TYPE=HIDDEN NAME=NOMEINST VALUE=0>";
				echo "<GET TYPE=HIDDEN NAME=NSUAUT VALUE=0>";
				echo "<GET TYPE=HIDDEN NAME=CAUT VALUE=0>";
				echo "<GET TYPE=HIDDEN NAME=PARC VALUE=0>";
				echo "<GET TYPE=HIDDEN NAME=TIPOTRANS VALUE=CHEQUE>";
			} else {
				$idterm_tef = getIDPOS($_GET['pos_serial']);
				$valor = number_format($total_geral * 100, 0, '', '');
				
				echo "<PAGAMENTO IPTEF=$ip_tef PORTATEF=$porta_tef CODLOJA=$codloja_tef IDTERM=$idterm_tef TIPO=MENU VALOR=$valor PAGRET=RESPAG BIN=BINCARTAO NINST=NOMEINST NSU=NSUAUT AUT=CAUT NPAR=PARC MODPAG=TIPOTRANS>";
			}
		}
		// se tiver algum problema, exibir o erro e voltar pelo historico
		else {
			foreach ($error as $value) {
				display_error($value);
			}

			echo "<GET TYPE=HIDDEN NAME=history VALUE=999999999>";
		}
	} else {

		echo "<WRITE_AT LINE=5 COLUMN=0> Informe o CPF:</WRITE_AT>";

		echo "<GET TYPE=CPF NAME=cpf COL=2 LIN=9>";

		echo_header();

		echo "<WRITE_AT LINE=5 COLUMN=0> Informe o telefone:</WRITE_AT>";
		echo utf8_decode("<WRITE_AT LINE=6 COLUMN=0>  (código de área + número)</WRITE_AT>");

		echo "<GET TYPE=FIELD NAME=telefone SIZE=11 COL=2 LIN=9>";

		echo_header();
		echo "<WRITE_AT LINE=9 COLUMN=0>          Aguarde...</WRITE_AT>";

		echo "<GET TYPE=HIDDEN NAME=confirmacao VALUE=1>";

		if ($_GET['venda_dinheiro'] == 1) {
			echo "<GET TYPE=HIDDEN NAME=venda_dinheiro VALUE=1>";
		}
	}

	echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";

	echo "<POST>";
	die();
}

// confirmacao do pagamento
if (isset($_GET['RESPAG'])) {

	if ($_GET['RESPAG'] == "APROVADO") {
		
		// efetivar a venda

		$meio_pagamento['CREDITO'] = 69;
		$meio_pagamento['DEBITO'] = 70;
		$meio_pagamento['DINHEIRO'] = 71;
		$meio_pagamento['OUTRO'] = $meio_pagamento['CARTAO DE CREDITO'];
		$meio_pagamento['CHEQUE'] = $meio_pagamento['DINHEIRO'];
		$meio_pagamento['VOUCHER'] = $meio_pagamento['CARTAO DE CREDITO'];
		$meio_pagamento['FIDELIDADE'] = $meio_pagamento['CARTAO DE CREDITO'];

		$meio_keys = array_keys($meio_pagamento);

		foreach ($meio_keys as $value) {
			if (strpos($_GET['TIPOTRANS'], $value) !== false) {
				$id_meio_pagamento = $meio_pagamento[$value];
				break;
			}
		}

		// se nenhum valor for encontrado no retorno entao CREDITO sera o padrao
		// $id_meio_pagamento = ($id_meio_pagamento ? $id_meio_pagamento : $meio_pagamento['CREDITO']);

		$_GET['NPAR'] = ($_GET['NPAR'] < 1 ? 1 : $_GET['NPAR']);

		//Dados dos itens de pedido
		$query = "SELECT R.ID_RESERVA, R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, R.ID_CADEIRA, R.DS_CADEIRA, R.DS_SETOR, E.ID_EVENTO, E.DS_EVENTO, ISNULL(LE.DS_LOCAL_EVENTO, B.DS_NOME_TEATRO) DS_NOME_TEATRO, CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) DT_APRESENTACAO, A.HR_APRESENTACAO,
		            AB.VL_LIQUIDO_INGRESSO, AB.DS_TIPO_BILHETE, R.NR_BENEFICIO
		            FROM MW_RESERVA R
		            INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO AND A.IN_ATIVO = '1'
		            INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = '1'
		            INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
		            INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE AND AB.IN_ATIVO = '1'
		            LEFT JOIN MW_LOCAL_EVENTO LE ON E.ID_LOCAL_EVENTO = LE.ID_LOCAL_EVENTO
		            WHERE R.ID_SESSION = ? AND R.DT_VALIDADE >= GETDATE()
		            ORDER BY E.DS_EVENTO, R.ID_APRESENTACAO, AB.VL_LIQUIDO_INGRESSO DESC, R.DS_CADEIRA";
		$params = array(session_id());
		$result = executeSQL($mainConnection, $query, $params);

		$queryServicos = "SELECT TOP 1 IN_TAXA_POR_PEDIDO
		                    FROM MW_RESERVA R
		                    INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
		                    LEFT JOIN MW_TAXA_CONVENIENCIA T ON T.ID_EVENTO = A.ID_EVENTO AND T.DT_INICIO_VIGENCIA <= GETDATE()
		                    WHERE R.ID_SESSION = ?
		                    ORDER BY DT_INICIO_VIGENCIA DESC";
		$rsServicos = executeSQL($mainConnection, $queryServicos, array(session_id()), true);

		$itensPedido = 0;
		$nr_beneficio = null;
		while ($itens = fetchResult($result)) {
		    $itensPedido++;

		    $nr_beneficio = $itens['NR_BENEFICIO'] ? $itens['NR_BENEFICIO'] : $nr_beneficio;
		    
		    if ($itensPedido == 1) {
		        if ($rsServicos['IN_TAXA_POR_PEDIDO'] == 'S') {
		            $valorConveniencia = $valorConvenienciaAUX = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], true, null, true);

		            $valorConveniencia = 0;
		            $itensPedido++;
		        } else {
		            $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], false, null, true);
		        }
		    } else {
		        $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], false, null, true);
		        $valorConvenienciaAUX = 0;
		    }

		    $totalIngressos += $itens['VL_LIQUIDO_INGRESSO'];
		    $totalConveniencia += $valorConveniencia + $valorConvenienciaAUX;

		    $params2[$itensPedido] = array($itens['ID_RESERVA'], $itens['ID_APRESENTACAO'], $itens['ID_APRESENTACAO_BILHETE'], $itens['DS_CADEIRA'], $itens['DS_SETOR'], 1, $itens['VL_LIQUIDO_INGRESSO'], $valorConveniencia + $valorConvenienciaAUX, 'XXXXXXXXXX', $itens['ID_CADEIRA']);
		}

		// se o valor total for zero meio de pagamento é convite/cortesia
		$id_meio_pagamento = (($totalIngressos + $totalConveniencia) == 0 ? 72 : $id_meio_pagamento);

		for ($i = 0; $i < 3; $i++) { 
				
			$newMaxId = executeSQL($mainConnection, 'SELECT ISNULL(MAX(ID_PEDIDO_VENDA), 0) + 1 FROM MW_PEDIDO_VENDA', array(), true);
			$newMaxId = $newMaxId[0];

			$query = "INSERT INTO MW_PEDIDO_VENDA
									(ID_PEDIDO_VENDA
									,ID_CLIENTE
									,ID_USUARIO_CALLCENTER
									,DT_PEDIDO_VENDA
									,VL_TOTAL_PEDIDO_VENDA
									,IN_SITUACAO
									,IN_RETIRA_ENTREGA
									,VL_TOTAL_INGRESSOS
									,VL_FRETE
									,VL_TOTAL_TAXA_CONVENIENCIA
									,IN_SITUACAO_DESPACHO
									,CD_BIN_CARTAO
									,ID_IP
			                        ,NR_PARCELAS_PGTO
			                        ,NR_BENEFICIO

			                        ,ID_TRANSACTION_BRASPAG
									,ID_PEDIDO_IPAGARE
									,CD_NUMERO_AUTORIZACAO
									,CD_NUMERO_TRANSACAO
						            ,ID_MEIO_PAGAMENTO)
									VALUES
									(?, ?, ?, GETDATE(), ?, 'P', 'R', ?, 0, ?, 'N', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$params = array($newMaxId, $_SESSION['user'], $_SESSION['pos_user']['id'],
							($totalIngressos + $totalConveniencia), $totalIngressos, $totalConveniencia,
							$_GET['BINCARTAO'], $_SERVER["HTTP_X_FORWARDED_FOR"], ($_GET['NPAR'] <= 1 ? 1 : $_GET['NPAR']),
							$nr_beneficio, 'POS', $_GET['pos_serial'], $_GET['CAUT'], $_GET['NSUAUT'], $id_meio_pagamento);
			$result = executeSQL($mainConnection, $query, $params);

			if ($result) {
				break;
			} else {
				sleep(1);
			}

		}

		$query = 'INSERT INTO MW_ITEM_PEDIDO_VENDA (
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
                         )
                         VALUES
                         (?, ?, ?, ?, ?, ?, ?, ?, ISNULL(?, 0), ?, ?)';
		if ($itensPedido > 0) {
		    foreach($params2 as $params) {
		    	array_unshift($params, $newMaxId);
		        executeSQL($mainConnection, $query, $params);
		    }
		}

		// 250 = codcaixa POS
		executeSQL($mainConnection, 'EXEC prc_vender_pedido ?, 250', array($newMaxId), true);

		executeSQL($mainConnection, 'UPDATE MW_PROMOCAO SET ID_SESSION = NULL, ID_PEDIDO_VENDA = ? WHERE ID_SESSION = ?', array($newMaxId, session_id()));

		executeSQL($mainConnection, 'DELETE MW_RESERVA WHERE ID_SESSION = ?', array(session_id()));

		// assinatura
		$parametros['OrderData']['OrderId'] = $newMaxId;
		require('../comprar/concretizarAssinatura.php');

		// limpa antes da impressao
		echo_header();
		echo "<WRITE_AT LINE=$header_lines COLUMN=0> </WRITE_AT>";

		// imprimir
		print_order($newMaxId);

		// limpar tudo e redirecionar para o menu
		echo "<GET TYPE=HIDDEN NAME=reset VALUE=1>";
	} else {
		echo "<GET TYPE=HIDDEN NAME=confirmacao VALUE=1>";
	}

	echo "<POST>";
	die();
}


// verificar se o evento tem lugar marcado e redirecionar para a tela correta
if ($_GET['subscreen'] == 'bilhete' and !isset($_GET['bilhete_m'])) {

	$query = "SELECT E.ID_BASE, A.CODAPRESENTACAO FROM MW_APRESENTACAO A INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO WHERE A.ID_APRESENTACAO = ?";
	$params = array($_GET['apresentacao']);
	$rs = executeSQL($mainConnection, $query, $params, true);

	$conn = getConnection($rs['ID_BASE']);

	$query = "SELECT INGRESSONUMERADO
				FROM TABAPRESENTACAO A
				INNER JOIN TABSALA S ON S.CODSALA = A.CODSALA
				INNER JOIN TABPECA P ON P.CODPECA = A.CODPECA
				WHERE CODAPRESENTACAO = ?";
	$params = array($rs['CODAPRESENTACAO']);
	$rs = executeSQL($conn, $query, $params, true);

	// se for lugar marcado
	if ($rs['INGRESSONUMERADO']) {
		$_GET['subscreen'] = 'fileira';
	}
}


switch ($_GET['subscreen']) {
	case 'evento':

		echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione um evento:</WRITE_AT>");

		$query = "SELECT DISTINCT E.ID_EVENTO, E.DS_EVENTO
					FROM MW_EVENTO E
					INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO AND A.IN_ATIVO = 1
					INNER JOIN MW_ACESSO_CONCEDIDO IAC ON IAC.ID_BASE = E.ID_BASE AND IAC.ID_USUARIO = ? AND IAC.CODPECA = E.CODPECA
					WHERE E.IN_ATIVO = 1 AND E.ID_BASE = ?
					AND CONVERT(CHAR(8), A.DT_APRESENTACAO,112) IN (
					  	SELECT CONVERT(CHAR(8), min(A1.DT_APRESENTACAO),112)
						FROM MW_APRESENTACAO A1
						WHERE A1.ID_EVENTO = A.ID_EVENTO AND A1.IN_ATIVO = '1'
						AND CONVERT(CHAR(8), A1.DT_APRESENTACAO,112) >= CONVERT(CHAR(8), GETDATE(),112)
					)
					ORDER BY DS_EVENTO";

		$result = executeSQL($mainConnection, $query, array($_SESSION['pos_user']['id'], $_GET['local']));

		$evento_options = array(999999999 => 'Voltar');

		while ($rs = fetchResult($result)) {
			$evento_options[$rs['ID_EVENTO']] = utf8_encode2($rs['DS_EVENTO']);
		}

		echo_select('evento', $evento_options, 3);

		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=apresentacao>";

		echo "<POST>";
	break;

	case 'apresentacao':

		echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione uma apresentação:</WRITE_AT>");

		ob_start();
		$_POST['pos'] = 1;
		require_once '../comprar/timeTable.php';
		$json = ob_get_clean();

		$array = json_decode($json, true);

		$apresentacao_options = array(999999999 => 'Voltar');

		foreach ($array['horarios'] as $value) {
			$apresentacao_options[$value['idApresentacao']] = utf8_encode2($value['nDia'].'/'.$value['nMes'].'/'.$value['nAno'].' '.$value['nHora'].':'.$value['nMinuto']);
		}

		echo_select('apresentacao', $apresentacao_options, 3);

		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=setor>";

		echo "<POST>";
	break;

	case 'setor':

		echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione um setor:</WRITE_AT>");

		$query = "SELECT ID_APRESENTACAO, DS_PISO FROM MW_APRESENTACAO A
					INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = '1'
					WHERE A.ID_EVENTO = (SELECT ID_EVENTO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
					AND DT_APRESENTACAO = (SELECT DT_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
					AND HR_APRESENTACAO = (SELECT HR_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
					AND A.IN_ATIVO = '1'
					ORDER BY DS_PISO";

		$result = executeSQL($mainConnection, $query, array($_GET['apresentacao'], $_GET['apresentacao'], $_GET['apresentacao']));

		$setor_options = array(999999999 => 'Voltar');

		while ($rs = fetchResult($result)) {
			$setor_options[$rs['ID_APRESENTACAO']] = utf8_encode2(preg_replace('/^(\d*)?([\s]*)?-?([\s]*)?/', '', $rs['DS_PISO']));
		}

		echo_select('apresentacao', $setor_options, 3);

		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=bilhete>";

		echo "<POST>";
	break;

	case 'bilhete':

		// bilhete numerado/marcado
		if (isset($_GET['bilhete_m'])) {

			$query = "SELECT COUNT(1) AS BILHETES, MIN(ID_CADEIRA) AS ID_CADEIRA FROM MW_RESERVA WHERE ID_APRESENTACAO_BILHETE IS NULL AND ID_SESSION = ?";
			$params = array(session_id());
			$rs = executeSQL($mainConnection, $query, $params, true);
			
			echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Você tem {$rs['BILHETES']} bilhete(s) para</WRITE_AT>");
			echo utf8_decode("<WRITE_AT LINE=6 COLUMN=0> selecionar o tipo:</WRITE_AT>");

			if ($rs['BILHETES'] > 0) {
				echo "<GET TYPE=HIDDEN NAME=cadeira VALUE={$rs['ID_CADEIRA']}>";
				echo "<GET TYPE=HIDDEN NAME=quantidade VALUE=1>";

				echo "<GET TYPE=HIDDEN NAME=bilhete_m VALUE=1>";
				echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=bilhete>";
			} else {
				echo_header();

				echo "<GET TYPE=HIDDEN NAME=apresentacao VALUE={$_GET['apresentacao']}>";
				echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=fileira>";
				echo "<POST>";
				die();
			}

		}

		// bilhetes nao numerados/marcados
		else {

			echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione o tipo de bilhete:</WRITE_AT>");

			$bilhete_options[999999999] = 'Voltar';

			$query = "SELECT COUNT(1) FROM MW_RESERVA WHERE ID_SESSION = ?";
			$rs = executeSQL($mainConnection, $query, array(session_id()), true);
			
			if ($rs[0] > 0) {
				$bilhete_options[888888888] = 'Finalizar Venda';
				
				// verifica se o pos pode vender em dinheiro
				$query = "SELECT VENDA_DINHEIRO, CD_BINITAU
							FROM MW_POS, MW_RESERVA
							WHERE SERIAL = ? AND ID_SESSION = ?
							ORDER BY CD_BINITAU DESC";
				$rs = executeSQL($mainConnection, $query, array($_GET['pos_serial'], session_id()), true);
				$venda_dinheiro = ($rs['VENDA_DINHEIRO'] == 1 and $rs['CD_BINITAU'] == null);

				if ($venda_dinheiro) {
					$bilhete_options[777777777] = 'Finalizar Venda em Dinheiro';
				}
			}

			echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=quantidade>";
		}

		// verifica se o pos pode vender convite
		$query = "SELECT VENDA_PROMO_CONVITE FROM MW_POS WHERE SERIAL = ?";
		$rs = executeSQL($mainConnection, $query, array($_GET['pos_serial']), true);
		$venda_convite = ($rs[0] == 1);

		foreach (comboPrecosIngresso(null, $_GET['apresentacao'], (isset($_GET['cadeira']) ? $_GET['cadeira'] : null), null, false, true) as $key => $value) {
			// se nao puder vender convite, ignorar o bilhete
			if ($value['codTipPromocao'] == 5 and !$venda_convite) continue;

			if ($value['exibicao'] == null or $value['exibicao'] == 'T' or $value['exibicao'] == 'P') {
				$valor = ' '.number_format(($value['valor'] + obterValorServico($key, false, null, true)), 2, ',', '');
				$descricao = substr(substr(remove_accents($value['descricao']).str_repeat(" ", 28), 0, 28), 0, (strlen($valor)*-1)).$valor;
				$bilhete_options[$key] = $descricao;
			}
		}

		echo_select('bilhete', $bilhete_options, 3);
		
		echo "<GET TYPE=HIDDEN NAME=apresentacao VALUE={$_GET['apresentacao']}>";
		echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";

		echo "<POST>";
	break;

	// lugares nao numerados/marcados
	case 'quantidade':

		echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Informe a quantidade:</WRITE_AT>");
		echo utf8_decode("<WRITE_AT LINE=11 COLUMN=0> Observação:</WRITE_AT>");
		echo utf8_decode("<WRITE_AT LINE=12 COLUMN=0> (0 para apagar todos os</WRITE_AT>");
		echo utf8_decode("<WRITE_AT LINE=13 COLUMN=0>  ingressos deste tipo já </WRITE_AT>");
		echo utf8_decode("<WRITE_AT LINE=14 COLUMN=0>  selecionados)</WRITE_AT>");

		echo "<GET TYPE=FIELD NAME=quantidade SIZE=2 COL=15 LIN=9>";

		echo "<GET TYPE=HIDDEN NAME=apresentacao VALUE={$_GET['apresentacao']}>";
		echo "<GET TYPE=HIDDEN NAME=bilhete VALUE={$_GET['bilhete']}>";
		echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";
		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=bilhete>";

		echo "<POST>";
	break;

	case 'fileira':

		/*display_error("A venda de lugares marcados não é permitida no momento. Favor selecionar outro setor.");
		echo "<GET TYPE=HIDDEN NAME=history VALUE=999999999>";
		echo "<POST>";
		die();*/

		echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione a fileira: </WRITE_AT>");

		$query = "SELECT E.ID_BASE, A.CODAPRESENTACAO FROM MW_APRESENTACAO A INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO WHERE A.ID_APRESENTACAO = ?";
		$params = array($_GET['apresentacao']);
		$rs = executeSQL($mainConnection, $query, $params, true);

		$conn = getConnection($rs['ID_BASE']);

		$query = "SELECT MIN(INDICE) AS INDICE, SUBSTRING(D.NOMOBJETO, 1, CHARINDEX('-', D.NOMOBJETO)-1) AS FILEIRA, COUNT(1) AS LUGARES_DISPONIVEIS
					FROM TABAPRESENTACAO A
					INNER JOIN TABSALDETALHE D ON D.CODSALA = A.CODSALA
					WHERE A.CODAPRESENTACAO = ?
					AND D.INDICE NOT IN (SELECT L.INDICE FROM TABLUGSALA L WHERE L.CODAPRESENTACAO = A.CODAPRESENTACAO)
					AND D.TIPOBJETO = 'C'
					GROUP BY SUBSTRING(D.NOMOBJETO, 1, CHARINDEX('-', D.NOMOBJETO)-1)
					ORDER BY FILEIRA";

		$result = executeSQL($conn, $query, array($rs['CODAPRESENTACAO']));

		$fileira_options[999999999] = 'Voltar';

		$query = "SELECT COUNT(1) FROM MW_RESERVA WHERE ID_SESSION = ?";
		$rs = executeSQL($mainConnection, $query, array(session_id()), true);
		
		if ($rs[0] > 0) {
			$fileira_options[888888888] = 'Finalizar Venda';
			
			// verifica se o pos pode vender em dinheiro
			$query = "SELECT VENDA_DINHEIRO, CD_BINITAU
						FROM MW_POS, MW_RESERVA
						WHERE SERIAL = ? AND ID_SESSION = ?
						ORDER BY CD_BINITAU DESC";
			$rs = executeSQL($mainConnection, $query, array($_GET['pos_serial'], session_id()), true);
			$venda_dinheiro = ($rs['VENDA_DINHEIRO'] == 1 and $rs['CD_BINITAU'] == null);

			if ($venda_dinheiro) {
				$fileira_options[777777777] = 'Finalizar Venda em Dinheiro';
			}
		}

		while ($rs = fetchResult($result)) {
			$fileira_options[$rs['INDICE']] = utf8_encode2("{$rs['FILEIRA']} ({$rs['LUGARES_DISPONIVEIS']} lugares disp.)");
		}

		echo_select('fileira', $fileira_options, 3);

		echo "<GET TYPE=HIDDEN NAME=apresentacao VALUE={$_GET['apresentacao']}>";
		echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";
		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=cadeira>";

		echo "<POST>";
	break;

	case 'cadeira':

		$query = "SELECT E.ID_BASE, A.CODAPRESENTACAO FROM MW_APRESENTACAO A INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO WHERE A.ID_APRESENTACAO = ?";
		$params = array($_GET['apresentacao']);
		$rs = executeSQL($mainConnection, $query, $params, true);

		$conn = getConnection($rs['ID_BASE']);

		$query = 'SELECT S.IN_VENDA_MESA FROM TABAPRESENTACAO A INNER JOIN TABSALA S ON S.CODSALA = A.CODSALA  WHERE CODAPRESENTACAO = ?';
		$params = array($rs['CODAPRESENTACAO']);
		$rs2 = executeSQL($conn, $query, $params, true);

		if ($rs2['IN_VENDA_MESA']) {
			$query = "SELECT MIN(INDICE) AS INDICE, SUBSTRING(D.NOMOBJETO, CHARINDEX('-', D.NOMOBJETO)+1, LEN(D.NOMOBJETO)) AS CADEIRA, COUNT(1) AS LUGARES
						FROM TABAPRESENTACAO A
						INNER JOIN TABSALDETALHE D ON D.CODSALA = A.CODSALA
						WHERE A.CODAPRESENTACAO = ?
						AND SUBSTRING(D.NOMOBJETO, 1, CHARINDEX('-', D.NOMOBJETO)-1) IN (SELECT SUBSTRING(D2.NOMOBJETO, 1, CHARINDEX('-', D2.NOMOBJETO)-1) FROM TABSALDETALHE D2 WHERE D2.CODSALA = A.CODSALA AND D2.INDICE = ?)
						AND D.INDICE NOT IN (SELECT L.INDICE FROM TABLUGSALA L WHERE L.CODAPRESENTACAO = A.CODAPRESENTACAO)
						AND D.TIPOBJETO = 'C'
						GROUP BY SUBSTRING(D.NOMOBJETO, CHARINDEX('-', D.NOMOBJETO)+1, LEN(D.NOMOBJETO))
						ORDER BY CADEIRA";
		} else {
			$query = "SELECT INDICE, SUBSTRING(D.NOMOBJETO, CHARINDEX('-', D.NOMOBJETO)+1, LEN(D.NOMOBJETO)) AS CADEIRA
						FROM TABAPRESENTACAO A
						INNER JOIN TABSALDETALHE D ON D.CODSALA = A.CODSALA
						WHERE A.CODAPRESENTACAO = ?
						AND SUBSTRING(D.NOMOBJETO, 1, CHARINDEX('-', D.NOMOBJETO)-1) IN (SELECT SUBSTRING(D2.NOMOBJETO, 1, CHARINDEX('-', D2.NOMOBJETO)-1) FROM TABSALDETALHE D2 WHERE D2.CODSALA = A.CODSALA AND D2.INDICE = ?)
						AND D.INDICE NOT IN (SELECT L.INDICE FROM TABLUGSALA L WHERE L.CODAPRESENTACAO = A.CODAPRESENTACAO)
						AND D.TIPOBJETO = 'C'
						ORDER BY INDICE";
		}

		$result = executeSQL($conn, $query, array($rs['CODAPRESENTACAO'], $_GET['fileira']));

		$cadeira_options[999999999] = 'Voltar';

		while ($rs = fetchResult($result)) {
			$descricao = ($rs2['IN_VENDA_MESA'] ? utf8_encode2($rs['CADEIRA']) . " (mesa para {$rs['LUGARES']})" : utf8_encode2($rs['CADEIRA']));
			$cadeira_options[$rs['INDICE']] = $descricao;
		}

		echo "<WRITE_AT LINE=5 COLUMN=0> Selecione a cadeira: </WRITE_AT>";

		echo_select('cadeira', $cadeira_options, 3);

		echo "<GET TYPE=HIDDEN NAME=apresentacao VALUE={$_GET['apresentacao']}>";
		echo "<GET TYPE=HIDDEN NAME=ignore_history VALUE=1>";
		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=bilhete>";
		echo "<GET TYPE=HIDDEN NAME=reservar VALUE=1>";
		echo "<GET TYPE=HIDDEN NAME=bilhete_m VALUE=1>";

		echo "<POST>";
	break;

	default:

		echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione um local:</WRITE_AT>");

		$query = "SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO
					FROM MW_BASE B
					INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE
					WHERE AC.ID_USUARIO = ? AND B.IN_ATIVO = '1'
					ORDER BY B.DS_NOME_TEATRO";

		$result = executeSQL($mainConnection, $query, array($_SESSION['pos_user']['id']));

		$local_options = array();

		while ($rs = fetchResult($result)) {
			$local_options[$rs['ID_BASE']] = utf8_encode2($rs['DS_NOME_TEATRO']);
		}

		echo_select('local', $local_options, 3);

		echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=evento>";

		echo "<POST>";
}