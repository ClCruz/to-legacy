<?php
if (isset($_GET['action'])) {
	require_once('../settings/functions.php');
	require_once('../settings/settings.php');
	session_start();
	$mainConnection = mainConnection();

	if ($_GET['action'] == 'add' or $_GET['action'] == 'noNum') {
		$query = "SELECT ID_EVENTO, DT_APRESENTACAO, HR_APRESENTACAO FROM MW_APRESENTACAO
					WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1'";
		$params = array($_POST['apresentacao']);
		$rs = executeSQL($mainConnection, $query, $params, true);

		$query = "SELECT TOP 1 A.ID_EVENTO, A.DT_APRESENTACAO, A.HR_APRESENTACAO FROM MW_APRESENTACAO A
					INNER JOIN MW_RESERVA R ON R.ID_APRESENTACAO = A.ID_APRESENTACAO
					WHERE R.ID_SESSION = ?";
		$params = array(session_id());
		$rs2 = executeSQL($mainConnection, $query, $params, true);

		$query = "SELECT 1 FROM MW_PACOTE R
					INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
					INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A2.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
					WHERE A2.ID_APRESENTACAO = ?";
		$params = array($_POST['apresentacao']);
		$rs3 = executeSQL($mainConnection, $query, $params, true);

		// verifica se a selecao atual pertence ao mesmo evento, data e hora ou se ainda nao fez reserva
		if ($rs2 === NULL or ($rs['ID_EVENTO'] == $rs2['ID_EVENTO'] and $rs['DT_APRESENTACAO'] == $rs2['DT_APRESENTACAO'] and $rs['HR_APRESENTACAO'] == $rs2['HR_APRESENTACAO'])) {
			
			// iniciou uma troca de assinatura e foi para um evento comum?
			if (isset($_SESSION['assinatura']) and $rs3 === NULL) {
				echo 'Já existe uma reserva em andamento.<br />Você deseja continuar com a seleção existente<br />ou iniciar uma nova reserva?';
				die();
			}

		} else {
			// ##123##
			// echo 'Não é possível comprar ingressos para apresentações diferentes no mesmo pedido, podemos cancelar a reserva efetuada para que você possa continuar sua compra nesta apresentação. Deseja cancelar suas reservas anteriores?';
			// die();

			// checagem para permitir que uma pessoa continue da etapa 1 para a 2 sem selecionar um lugar (atingiu limite de ingressos)
			if ($_REQUEST['checking']) {
				die('true');
			}
		}
	}
	
	if ($_GET['action'] == 'add' and isset($_REQUEST['id'])) {

		$query = 'SELECT E.QT_INGR_POR_PEDIDO FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO WHERE A.ID_APRESENTACAO = ?';
		$params = array($_POST['apresentacao']);
		$rs = executeSQL($mainConnection, $query, $params, true);
		$maxIngressos = $rs['QT_INGR_POR_PEDIDO'];

		$query = 'SELECT SUM(1) FROM MW_RESERVA R
					INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
					INNER JOIN MW_EVENTO E ON A.ID_EVENTO = E.ID_EVENTO
					WHERE A.ID_APRESENTACAO = ? AND R.ID_SESSION = ?';
		$params = array($_POST['apresentacao'], session_id());
		$rs = executeSQL($mainConnection, $query, $params, true);

		if ($_SESSION['assinatura']['tipo'] == 'troca') {
			$query = 'SELECT SUM(1) FROM MW_RESERVA WHERE ID_SESSION = ?';
			$params = array(session_id());
			$rs_assinatura = executeSQL($mainConnection, $query, $params, true);

			if ($rs_assinatura[0] == count($_SESSION['assinatura']['cadeira']))
				die('Você atingiu o limite para essa troca:<br/>' . count($_SESSION['assinatura']['cadeira']) . ' ingresso(s) selecionado(s).');
		}
		
		if ($rs[0] < $maxIngressos) {
			// não existe na mw_reserva?
			$query = 'SELECT 1 FROM MW_RESERVA WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ?';
			$params = array($_POST['apresentacao'], $_REQUEST['id']);
			$rs = executeSQL($mainConnection, $query, $params, true);

			// não existe na mw_pacote_reserva?
			$query = "SELECT 1 FROM MW_PACOTE_RESERVA PR
						INNER JOIN MW_PACOTE_APRESENTACAO PA ON PA.ID_PACOTE = PR.ID_PACOTE
						WHERE PA.ID_APRESENTACAO = ? AND PR.ID_CADEIRA = ? AND PR.IN_STATUS_RESERVA IN ('A', 'S') AND PR.ID_CLIENTE <> ?";
			$params = array($_POST['apresentacao'], $_REQUEST['id'], $_SESSION['user']);
			$rs2 = executeSQL($mainConnection, $query, $params, true);
			
			if (empty($rs) and empty($rs2)) {
				// não existe na tablugsala?
				$query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO AND A.IN_ATIVO = \'1\' WHERE A.ID_APRESENTACAO = ? AND E.IN_ATIVO = \'1\'';
				$params = array($_POST['apresentacao']);
				$rs = executeSQL($mainConnection, $query, $params, true);
				
				$codApresentacao = $rs['CODAPRESENTACAO'];
				$conn = getConnection($rs['ID_BASE']);
				
				$return = verificarLimitePorCPF($conn, $codApresentacao, $_SESSION['user']);
				($return != NULL) ? die($return) : '';
				
				$query = 'SELECT 1 FROM TABLUGSALA WHERE CODAPRESENTACAO = ? AND INDICE = ?';
				$params = array($codApresentacao, $_REQUEST['id']);
				$rs = executeSQL($conn, $query, $params, true);
				
				if (empty($rs)) {
					beginTransaction($mainConnection);
					beginTransaction($conn);
					
					$query = 'SELECT S.IN_VENDA_MESA, S.CODSALA FROM TABAPRESENTACAO A INNER JOIN TABSALA S ON S.CODSALA = A.CODSALA  WHERE CODAPRESENTACAO = ?';
					$params = array($codApresentacao);
					$rs = executeSQL($conn, $query, $params, true);
					
					if ($rs['IN_VENDA_MESA']) {
						$query = 'SELECT D.INDICE, D.NOMOBJETO, S.NOMSETOR
									 FROM TABSALDETALHE D
									 INNER JOIN TABSETOR S ON S.CODSETOR = D.CODSETOR AND S.CODSALA = D.CODSALA
									 WHERE D.NOMOBJETO = (SELECT NOMOBJETO FROM TABSALDETALHE WHERE CODSALA = ? AND INDICE = ?)
									 AND D.TIPOBJETO = \'C\' AND D.CODSALA = ?';
						$params = array($rs['CODSALA'], $_REQUEST['id'], $rs['CODSALA']);
						$registros = executeSQL($conn, $query, $params);
						
						$idsCadeiras = '';
						
						while ($rs = fetchResult($registros)) {
							$idsCadeiras .= $rs['INDICE'] . '|';
							
							$query = 'INSERT INTO MW_RESERVA (ID_APRESENTACAO,ID_CADEIRA,DS_CADEIRA,DS_SETOR,ID_SESSION,DT_VALIDADE) VALUES (?,?,?,?,?,DATEADD(MI, ?, GETDATE()))';
							$params = array($_POST['apresentacao'], $rs['INDICE'], $rs['NOMOBJETO'], $rs['NOMSETOR'], session_id(), $compraExpireTime);
							$result = executeSQL($mainConnection, $query, $params);
							
							// gravou direito na mw_reserva?
							if ($result) {
								$query = 'INSERT INTO TABLUGSALA
													  (CODAPRESENTACAO
													  ,INDICE
													  ,CODTIPBILHETE
													  ,CODCAIXA
													  ,CODVENDA
													  ,STAIMPRESSAO
													  ,STACADEIRA
													  ,CODUSUARIO
													  ,CODRESERVA
													  ,ID_SESSION)
											  VALUES
													  (?,?,?,?,?,?,?,?,?,?)';
								$params = array($codApresentacao, $rs['INDICE'], NULL, 255, NULL, 0, 'T', NULL, NULL, session_id());
								$result = executeSQL($conn, $query, $params);
							}
						}
					} else {
						$query = 'INSERT INTO MW_RESERVA (ID_APRESENTACAO,ID_CADEIRA,DS_CADEIRA,DS_SETOR,ID_SESSION,DT_VALIDADE) VALUES (?,?,?,?,?,DATEADD(MI, ?, GETDATE()))';
						$params = array($_POST['apresentacao'], $_REQUEST['id'], utf8_encode2($_POST['name']), $_POST['setor'], session_id(), $compraExpireTime);
						$result = executeSQL($mainConnection, $query, $params);
						
						// gravou direito na mw_reserva?
						if ($result) {
							$query = 'INSERT INTO TABLUGSALA
												  (CODAPRESENTACAO
												  ,INDICE
												  ,CODTIPBILHETE
												  ,CODCAIXA
												  ,CODVENDA
												  ,STAIMPRESSAO
												  ,STACADEIRA
												  ,CODUSUARIO
												  ,CODRESERVA
												  ,ID_SESSION)
										  VALUES
												  (?,?,?,?,?,?,?,?,?,?)';
							$params = array($codApresentacao, $_REQUEST['id'], NULL, 255, NULL, 0, 'T', NULL, NULL, session_id());
							$result = executeSQL($conn, $query, $params);
						}
					}
				} else {
					$errors2[] = 'reservado';
				}
			} else {
				$errors2[] = 'reservado';
			}
			
			$errors = sqlErrors();
			if (empty($errors) and empty($errors2)) {// completou todas as operações com sucesso?
				commitTransaction($mainConnection);
				commitTransaction($conn);
				extenderTempo();
				echo 'true?' . (isset($idsCadeiras) ? substr($idsCadeiras, 0, -1) : $_REQUEST['id']);
			} else {
				rollbackTransaction($mainConnection);
				rollbackTransaction($conn);
				
				$query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO AND A.IN_ATIVO = \'1\' WHERE A.ID_APRESENTACAO = ? AND E.IN_ATIVO = \'1\'';
				$params = array($_POST['apresentacao']);
				$rs = executeSQL($mainConnection, $query, $params, true);
				
				$codApresentacao = $rs['CODAPRESENTACAO'];
				$conn = getConnection($rs['ID_BASE']);
				
				$query = 'SELECT S.IN_VENDA_MESA, S.CODSALA FROM TABAPRESENTACAO A INNER JOIN TABSALA S ON S.CODSALA = A.CODSALA  WHERE CODAPRESENTACAO = ?';
				$params = array($codApresentacao);
				$rs = executeSQL($conn, $query, $params, true);
				
				if ($rs['IN_VENDA_MESA']) {
					$query = 'SELECT D.INDICE, D.NOMOBJETO, S.NOMSETOR
								 FROM TABSALDETALHE D
								 INNER JOIN TABSETOR S ON S.CODSETOR = D.CODSETOR AND S.CODSALA = D.CODSALA
								 WHERE D.NOMOBJETO = (SELECT NOMOBJETO FROM TABSALDETALHE WHERE CODSALA = ? AND INDICE = ?)
								 AND D.TIPOBJETO = \'C\'';
					$params = array($rs['CODSALA'], $_REQUEST['id']);
					$registros = executeSQL($conn, $query, $params);
					
					$idsCadeiras = '';
					
					while ($rs = fetchResult($registros)) {
						$idsCadeiras .= $rs['INDICE'] . '|';
					}
				}

				echo (isset($idsCadeiras) ? substr($idsCadeiras, 0, -1) : $_REQUEST['id']) . '?Esta posição já foi ocupada.';
			}
		} else {
			echo 'Você já selecionou o máximo <br> de ingressos permitidos. Para  <br> selecionar mais ingressos  <br> finalize a compra atual.';
		}
	
	} else if ($_GET['action'] == 'update' and ((isset($_POST['apresentacao']) and isset($_POST['cadeira'])) or ($_POST['entrega'] or $_POST['estado']))) {

		$retorno = '';
		$result = true;

		// nao checar quantidade de ingressos na tela de enderecos
		if (!($_POST['entrega'] or $_POST['estado'])) {
			if ($_SESSION['assinatura']['tipo'] == 'troca' and count($_SESSION['assinatura']['cadeira']) != count($_POST['cadeira'])) {
				die('Você selecionou ' . count($_SESSION['assinatura']['cadeira']) . ' ingresso(s) para troca. Favor retornar e selecionar apenas a quantidade informada.');
			}
		}

		$selectInfoVB = 'SELECT E.ID_BASE, AB.CODTIPBILHETE, A.CODAPRESENTACAO
					 FROM MW_APRESENTACAO_BILHETE AB
					 INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = AB.ID_APRESENTACAO
					 INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
					 WHERE AB.ID_APRESENTACAO_BILHETE = ? AND AB.ID_APRESENTACAO = ?';

		$updateVB = 'UPDATE TABLUGSALA SET CODTIPBILHETE = ? WHERE CODAPRESENTACAO = ? AND INDICE = ? AND ID_SESSION = ?';

		$queryTipoBilhete = "SELECT STATIPBILHMEIAESTUDANTE, QTDVENDAPORLOTE FROM TABTIPBILHETE WHERE CODTIPBILHETE = ? AND STATIPBILHETE = 'A'";

		$queryMeiaEstudanteNoCarrinho = "SELECT COUNT(1) AS NO_CARRINHO FROM TABLUGSALA L
							INNER JOIN TABTIPBILHETE B ON L.CODTIPBILHETE = B.CODTIPBILHETE
							WHERE B.STATIPBILHMEIAESTUDANTE = 'S' AND B.STATIPBILHETE = 'A'
							AND B.CODTIPBILHETE = ? AND L.CODAPRESENTACAO = ? AND L.INDICE = ? AND L.ID_SESSION = ?";

		$queryIsBilheteMeiaEstudante = "SELECT COUNT(1) AS BILHETE_MEIA FROM TABTIPBILHETE WHERE STATIPBILHMEIAESTUDANTE = 'S' AND STATIPBILHETE = 'A' AND CODTIPBILHETE = ?";

		$queryLoteNoCarrinho = "SELECT COUNT(1) AS NO_CARRINHO FROM TABLUGSALA L
							INNER JOIN TABTIPBILHETE B ON L.CODTIPBILHETE = B.CODTIPBILHETE
							WHERE B.STATIPBILHMEIAESTUDANTE = 'N' AND B.STATIPBILHETE = 'A' AND B.QTDVENDAPORLOTE > 0
							AND B.CODTIPBILHETE = ? AND L.CODAPRESENTACAO = ? AND L.INDICE = ? AND L.ID_SESSION = ?";

		$queryIsLote = "SELECT COUNT(1) AS BILHETE_LOTE FROM TABTIPBILHETE WHERE STATIPBILHMEIAESTUDANTE = 'N' AND QTDVENDAPORLOTE > 0 AND STATIPBILHETE = 'A' AND CODTIPBILHETE = ?";
		
		//beginTransaction($mainConnection);
		
		for ($i = 0; $i < count($_POST['apresentacao']); $i++) {
			if (!isset($_POST['valorIngresso'][$i])) {
				$retorno = "Não existem tipos de ingressos disponíveis para a operação. Por favor, entre em contato conosco.";
			}

			$rs = executeSQL($mainConnection, $selectInfoVB, array($_POST['valorIngresso'][$i], $_POST['apresentacao'][$i]), true);
			$conn = getConnection($rs['ID_BASE']);

			//identifica o bilhete como meia ou lote
			$rs1 = executeSQL($conn, $queryTipoBilhete, array($rs['CODTIPBILHETE']), true);

			//MEIA ESTUDANTE
			if ($rs1['STATIPBILHMEIAESTUDANTE'] == 'S') {
				//checar se o bilhete atual é meia estudante e está no carrinho
				$rs2 = executeSQL($conn, $queryMeiaEstudanteNoCarrinho, array($rs['CODTIPBILHETE'], $rs['CODAPRESENTACAO'], $_POST['cadeira'][$i], session_id()), true);
				$rs3 = executeSQL($conn, $queryIsBilheteMeiaEstudante, array($rs['CODTIPBILHETE']), true);

				//checar se o numero disponivel de meia estudante esta zerado, ou seja, se a pessoa selecionou um bilhete nao disponivel
				if (($rs2['NO_CARRINHO'] == 0 and $rs3['BILHETE_MEIA'] == 1) and getTotalMeiaEntradaDisponivel($_POST['apresentacao'][$i]) <= 0) {
					
					$retorno = 'Quantidade de meia entrada de estudante superou a cota disponível, altere um ou mais tipos de ingresso para efetuar a compra.';

				}

			//LOTE
			} else if ($rs1['STATIPBILHMEIAESTUDANTE'] == 'N' and $rs1['QTDVENDAPORLOTE'] > 0) {
				//checar se o bilhete atual é de lote e está no carrinho
				$rs2 = executeSQL($conn, $queryLoteNoCarrinho, array($rs['CODTIPBILHETE'], $rs['CODAPRESENTACAO'], $_POST['cadeira'][$i], session_id()), true);
				$rs3 = executeSQL($conn, $queryIsLote, array($rs['CODTIPBILHETE']), true);

				//checar se o numero disponivel de lote esta zerado, ou seja, se a pessoa selecionou um bilhete nao disponivel
				if (($rs2['NO_CARRINHO'] == 0 and $rs3['BILHETE_LOTE'] == 1) and getTotalLoteDisponivel($_POST['valorIngresso'][$i]) <= 0) {

					$retorno = 'Ingresso esgotado, selecione outro tipo de ingresso para efetuar a compra.<span class="bilhete_lote_indisponivel">'.$_POST['valorIngresso'][$i].'</span>';

				}
			}

			if ($retorno == '') {

				$result = (executeSQL($conn, $updateVB, array($rs['CODTIPBILHETE'], $rs['CODAPRESENTACAO'], $_POST['cadeira'][$i], session_id())) and $result);

				// verifica se a selecao atual do bilhete utiliza a promocao do itau
				$query = 'SELECT TOP 1 1 AS USA_BIN
							FROM CI_MIDDLEWAY..MW_RESERVA R 
							INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.IN_ATIVO = 1 AND AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
							INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
							INNER JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = TTB.ID_PROMOCAO_CONTROLE AND PC.CODTIPPROMOCAO in (4, 7)
							WHERE R.ID_APRESENTACAO_BILHETE = ? AND R.ID_APRESENTACAO = ? AND R.ID_CADEIRA = ? AND R.ID_SESSION = ?';

				$params = array($_POST['valorIngresso'][$i], $_POST['apresentacao'][$i], $_POST['cadeira'][$i], session_id());

				$ticket = executeSQL($conn, $query, $params, true);
				
				// verifica se a selecao atual utiliza codigo promocional
				$query = 'SELECT TOP 1 1 AS USA_COD_PROMOCIONAL
							FROM CI_MIDDLEWAY..MW_RESERVA R 
							INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.IN_ATIVO = 1 AND AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
							INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
							INNER JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = TTB.ID_PROMOCAO_CONTROLE
							WHERE R.ID_APRESENTACAO_BILHETE = ? AND R.ID_APRESENTACAO = ? AND R.ID_CADEIRA = ? AND R.ID_SESSION = ?';

				$ticket2 = executeSQL($conn, $query, $params, true);


				// ---- apaga a sessao da mw_promocao de acordo com os bilhetes na reserva
				$query = "SELECT COUNT(1) AS QTD, CD_PROMOCIONAL FROM MW_PROMOCAO WHERE ID_SESSION = ? GROUP BY CD_PROMOCIONAL";
				$resultPromocao = executeSQL($mainConnection, $query, array(session_id()));

				while ($rs = fetchResult($resultPromocao)) {
					$qtdCarimbado = $rs['QTD'];
					$cdPromocional = $rs['CD_PROMOCIONAL'];

					$query = "SELECT COUNT(1) AS QTD FROM MW_RESERVA WHERE NR_BENEFICIO = ? AND ID_SESSION = ?";

					$rs = executeSQL($mainConnection, $query, array($cdPromocional, session_id()), true);

					$qtdReserva = $rs['QTD'];

					if ($qtdReserva == 0) {
						$query = "UPDATE MW_PROMOCAO SET ID_SESSION = NULL WHERE CD_PROMOCIONAL = ? and ID_SESSION = ?";
						executeSQL($mainConnection, $query, array($cdPromocional, session_id()));

					} else if ($qtdReserva != $qtdCarimbado) {
						$qtdApagar = $qtdCarimbado - $qtdReserva;

						$query = "UPDATE P
									SET ID_SESSION = NULL
									FROM MW_PROMOCAO P
									WHERE P.ID_PROMOCAO IN (
										SELECT TOP $qtdApagar P2.ID_PROMOCAO
										FROM MW_PROMOCAO P2
										INNER JOIN MW_RESERVA R ON R.ID_SESSION = P2.ID_SESSION AND R.NR_BENEFICIO = P2.CD_PROMOCIONAL
										WHERE R.ID_APRESENTACAO = ? AND R.ID_CADEIRA = ? AND R.ID_SESSION = ?
									)";
						executeSQL($mainConnection, $query, array($_POST['apresentacao'][$i], $_POST['cadeira'][$i], session_id()));
					}
				}
				// ----

				// use bin ou codigo promocional? (se sim nao pode apagar o codigo ja gravado na tabela)
				if ($ticket['USA_BIN'] || $ticket2['USA_COD_PROMOCIONAL']){
					$query = 'UPDATE MW_RESERVA SET
							ID_APRESENTACAO_BILHETE = ?
							WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ? AND ID_SESSION = ?';
					$params = array(
						$_POST['valorIngresso'][$i],
						$_POST['apresentacao'][$i],
						$_POST['cadeira'][$i],
						session_id()
					);
				} else {
					$query = 'UPDATE MW_RESERVA SET
								ID_APRESENTACAO_BILHETE = ?,
								CD_BINITAU = ?,
								NR_BENEFICIO = ?
								WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ? AND ID_SESSION = ?';
					$params = array(
						$_POST['valorIngresso'][$i],
						NULL,
						NULL,
						$_POST['apresentacao'][$i],
						$_POST['cadeira'][$i],
						session_id()
					);
				}
				
				$result = (executeSQL($mainConnection, $query, $params) and $result);

				if ($_GET['pos'] and $_GET['bin']) {
					$query = 'UPDATE MW_RESERVA SET CD_BINITAU = ?
							WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ? AND ID_SESSION = ? AND ID_APRESENTACAO_BILHETE = ?';
					$params = array(
						$_GET['bin'],
						$_POST['apresentacao'][$i],
						$_POST['cadeira'][$i],
						session_id(),
						$_POST['valorIngresso'][$i]
					);
				} elseif ($_GET['pos'] and $_GET['codigo']) {
					$query = 'UPDATE MW_RESERVA SET NR_BENEFICIO = ?
							WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ? AND ID_SESSION = ? AND ID_APRESENTACAO_BILHETE = ?';
					$params = array(
						$_GET['codigo'][$i],
						$_POST['apresentacao'][$i],
						$_POST['cadeira'][$i],
						session_id(),
						$_POST['valorIngresso'][$i]
					);
				}

				if ($_GET['pos']) {
					executeSQL($mainConnection, $query, $params);
				}
			}
		}
		
		$errors = sqlErrors();
		if (empty($errors)) {
			//commitTransaction($mainConnection);
			if (!isset($_GET['pos'])) {
				if ($_POST['entrega']) {
					setcookie('entrega', $_POST['entrega']);
				} else {
					setcookie('entrega', '', -1);
				}
				
				if ($_POST['estado']) {
					setcookie('entrega', '-2');
				}
			}
			
			//extenderTempo();
			$retorno = $retorno ? $retorno : 'true';
		} else {
			//rollbackTransaction($mainConnection);
			$retorno = $retorno ? $retorno : 'Seu pedido contém erro(s)!<br><br>Favor revisá-lo.<br><br>Se o erro persistir, favor entrar em contato com o suporte.';
		}
		// var_dump($errors, $query, $params);

		echo $retorno;

	} else if ($_GET['action'] == 'delete' and isset($_REQUEST['apresentacao']) and isset($_REQUEST['id'])) {
		
		$query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO WHERE A.ID_APRESENTACAO = ?';
		$params = array($_REQUEST['apresentacao']);
		$rs = executeSQL($mainConnection, $query, $params, true);
		
		$codApresentacao = $rs['CODAPRESENTACAO'];
		$idBase = $rs['ID_BASE'];
		$conn = getConnection($idBase);
		
		$query = 'SELECT S.IN_VENDA_MESA, S.CODSALA FROM TABAPRESENTACAO A INNER JOIN TABSALA S ON S.CODSALA = A.CODSALA  WHERE CODAPRESENTACAO = ?';
		$params = array($codApresentacao);
		$rs = executeSQL($conn, $query, $params, true);
		
		if ($rs['IN_VENDA_MESA']) {
			$query = 'SELECT INDICE
						 FROM TABSALDETALHE
						 WHERE NOMOBJETO = (SELECT NOMOBJETO FROM TABSALDETALHE WHERE CODSALA = ? AND INDICE = ?)
						 AND TIPOBJETO = \'C\'';
			$params = array($rs['CODSALA'], $_REQUEST['id']);
			$registros = executeSQL($conn, $query, $params);
			
			$idsCadeiras = '';
			
			beginTransaction($mainConnection);
			beginTransaction($conn);
			
			while ($rs = fetchResult($registros)) {
				$idsCadeiras .= $rs['INDICE'] . '|';

				// remove o id_session de um codigo promocional se o mesmo estiver em uso no momento do delete
				$query = 'UPDATE P
							SET ID_SESSION = NULL
							FROM MW_PROMOCAO P
							WHERE P.ID_PROMOCAO IN (
								SELECT TOP 1 P2.ID_PROMOCAO
								FROM MW_PROMOCAO P2
								INNER JOIN MW_RESERVA R ON R.ID_SESSION = P2.ID_SESSION AND R.NR_BENEFICIO = P2.CD_PROMOCIONAL
								WHERE R.ID_APRESENTACAO = ? AND R.ID_CADEIRA = ? AND R.ID_SESSION = ?
							)';
				$params = array($_REQUEST['apresentacao'], $rs['INDICE'], session_id());
				$result = executeSQL($mainConnection, $query, $params);
				
				$query = 'DELETE FROM MW_RESERVA
							 WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ?
							 AND ID_SESSION = ?';
				$params = array($_REQUEST['apresentacao'], $rs['INDICE'], session_id());
				$result = executeSQL($mainConnection, $query, $params);

				$query = 'DELETE FROM TABLUGSALA
							 WHERE CODAPRESENTACAO = ? AND INDICE = ?
							 AND ID_SESSION = ?';
				$params = array($codApresentacao, $rs['INDICE'], session_id());
				$result = executeSQL($conn, $query, $params);
			}
		} else {
			beginTransaction($mainConnection);

			// remove o id_session de um codigo promocional se o mesmo estiver em uso no momento do delete
			$query = 'UPDATE P
						SET ID_SESSION = NULL
						FROM MW_PROMOCAO P
						WHERE P.ID_PROMOCAO IN (
							SELECT TOP 1 P2.ID_PROMOCAO
							FROM MW_PROMOCAO P2
							INNER JOIN MW_RESERVA R ON R.ID_SESSION = P2.ID_SESSION AND R.NR_BENEFICIO = P2.CD_PROMOCIONAL
							WHERE R.ID_APRESENTACAO = ? AND R.ID_CADEIRA = ? AND R.ID_SESSION = ?
						)';
			$params = array($_REQUEST['apresentacao'], $_REQUEST['id'], session_id());
			$result = executeSQL($mainConnection, $query, $params);

			$query = 'DELETE FROM MW_RESERVA
						 WHERE ID_APRESENTACAO = ? AND ID_CADEIRA = ?
						 AND ID_SESSION = ?';
			$params = array($_REQUEST['apresentacao'], $_REQUEST['id'], session_id());
			$result = executeSQL($mainConnection, $query, $params);
			
			if ($result) {
				$query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO WHERE A.ID_APRESENTACAO = ?';
				$params = array($_REQUEST['apresentacao']);
				$rs = executeSQL($mainConnection, $query, $params, true);
				
				$codApresentacao = $rs['CODAPRESENTACAO'];
				$conn = getConnection($rs['ID_BASE']);
				
				beginTransaction($conn);
				
				$query = 'DELETE FROM TABLUGSALA
							 WHERE CODAPRESENTACAO = ? AND INDICE = ?
							 AND ID_SESSION = ?';
				$params = array($codApresentacao, $_REQUEST['id'], session_id());
				$result = executeSQL($conn, $query, $params);
			}
		}
		
		$errors = sqlErrors();
		if (empty($errors)) {// completou todas as operações com sucesso?
			commitTransaction($mainConnection);
			commitTransaction($conn);
			//extenderTempo();
			echo 'true?' . (isset($idsCadeiras) ? substr($idsCadeiras, 0, -1) : $_REQUEST['id']);
		} else {
			rollbackTransaction($mainConnection);
			rollbackTransaction($conn);
			echo 'false';
		}
		
	} else if ($_GET['action'] == 'noNum' and isset($_POST['numIngressos']) and is_numeric($_POST['numIngressos'])) {

		$query = 'SELECT E.QT_INGR_POR_PEDIDO FROM MW_EVENTO E INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO WHERE A.ID_APRESENTACAO = ?';
		$params = array($_POST['apresentacao']);
		$rs = executeSQL($mainConnection, $query, $params, true);
		$maxIngressos = $rs['QT_INGR_POR_PEDIDO'];

		if ($_GET['pos']) {
			$cod_caixa = 250;
			$cod_tip_bilhete = $_GET['codtipbilhete'];
			$id_apresentacao_bilhete = $_GET['bilhete'];
			$bin = isset($_GET['bin']) ? $_GET['bin'] : null;
		} else {
			$cod_caixa = 255;
			$cod_tip_bilhete = NULL;
			$id_apresentacao_bilhete = NULL;
			$bin = null;
		}
		
		if ($_POST['numIngressos'] <= $maxIngressos) {
			$conn = getConnection($_POST['teatro']);
			
			$return = verificarLimitePorCPF($conn, $_POST['codapresentacao'], $_SESSION['user']);
			($return != NULL) ? die($return) : '';
			
			//ainda existe o numero selecionado de ingressos disponiveis?
			$query = 'SELECT SUM(1) FROM TABSALDETALHE D
						INNER JOIN TABAPRESENTACAO A ON A.CODSALA = D.CODSALA
						WHERE D.TIPOBJETO = \'C\' AND A.CODAPRESENTACAO = ?
						AND NOT EXISTS (SELECT 1 FROM TABLUGSALA L
											WHERE L.INDICE = D.INDICE
											AND L.CODAPRESENTACAO = A.CODAPRESENTACAO)';
			$params = array($_POST['codapresentacao']);
			$ingressosDisponiveis = executeSQL($conn, $query, $params, true);
			$ingressosDisponiveis = $ingressosDisponiveis[0];
			
			if ($ingressosDisponiveis >= $_POST['numIngressos']) {
				beginTransaction($mainConnection);
				beginTransaction($conn);
				$errors = true;
				
				// se for pos apagar apenas os bilhetes iguais da mesma apresentacao
				if ($_GET['pos']) {
					$query = 'UPDATE P
								SET ID_SESSION = NULL
								FROM MW_PROMOCAO P
								WHERE P.ID_PROMOCAO IN (
									SELECT P2.ID_PROMOCAO
									FROM MW_PROMOCAO P2
									INNER JOIN MW_RESERVA R ON R.ID_SESSION = P2.ID_SESSION AND R.NR_BENEFICIO = P2.CD_PROMOCIONAL
									WHERE R.ID_APRESENTACAO = ? AND R.ID_APRESENTACAO_BILHETE = ? AND R.ID_SESSION = ?
								)';
					$params = array($_POST['apresentacao'], $id_apresentacao_bilhete, session_id());
					$result = executeSQL($mainConnection, $query, $params);
					
					$errors = $result and $errors;

					$query = 'DELETE FROM MW_RESERVA WHERE ID_APRESENTACAO = ? AND ID_APRESENTACAO_BILHETE = ? AND ID_SESSION = ?';
					$result = executeSQL($mainConnection, $query, $params);
					
					$errors = $result and $errors;
					
					$query = 'DELETE FROM TABLUGSALA WHERE CODAPRESENTACAO = ? AND CODTIPBILHETE = ? AND ID_SESSION = ?';
					$params = array($_POST['codapresentacao'], $cod_tip_bilhete, session_id());
					$result = executeSQL($conn, $query, $params);
					
					$errors = $result and $errors;
				}
				// se nao for pos apagar todos os bilhetes da apresentacao
				else {
					$query = 'UPDATE P
								SET ID_SESSION = NULL
								FROM MW_PROMOCAO P
								WHERE P.ID_PROMOCAO IN (
									SELECT P2.ID_PROMOCAO
									FROM MW_PROMOCAO P2
									INNER JOIN MW_RESERVA R ON R.ID_SESSION = P2.ID_SESSION AND R.NR_BENEFICIO = P2.CD_PROMOCIONAL
									WHERE R.ID_APRESENTACAO = ? AND R.ID_SESSION = ?
								)';
					$params = array($_POST['apresentacao'], session_id());
					$result = executeSQL($mainConnection, $query, $params);
					
					$errors = $result and $errors;
					$query = 'DELETE FROM MW_RESERVA WHERE ID_APRESENTACAO = ? AND ID_SESSION = ?';
					$result = executeSQL($mainConnection, $query, $params);
					
					$errors = $result and $errors;
					
					$query = 'DELETE FROM TABLUGSALA WHERE CODAPRESENTACAO = ? AND ID_SESSION = ?';
					$params = array($_POST['codapresentacao'], session_id());
					$result = executeSQL($conn, $query, $params);
					
					$errors = $result and $errors;
				}
				
				$query = 'SELECT TOP ' . $_POST['numIngressos'] . ' D.INDICE, D.NOMOBJETO, S.NOMSETOR FROM TABSALDETALHE D
							INNER JOIN TABAPRESENTACAO A ON A.CODSALA = D.CODSALA
							INNER JOIN TABSETOR S ON S.CODSALA = D.CODSALA AND S.CODSETOR = D.CODSETOR
							WHERE D.TIPOBJETO = \'C\' AND A.CODAPRESENTACAO = ?
							AND NOT EXISTS (SELECT 1 FROM TABLUGSALA L
												WHERE L.INDICE = D.INDICE
												AND L.CODAPRESENTACAO = A.CODAPRESENTACAO)';
				$params = array($_POST['codapresentacao']);
				$result = executeSQL($conn, $query, $params);
				
				$errors = $result and $errors;
				
				while ($rs = fetchResult($result)) {
					$codigo = ((isset($_GET['pos']) and !isset($_GET['bin']) and isset($_GET['codigo'])) ? array_pop($_GET['codigo']) : null);

					$query = 'INSERT INTO MW_RESERVA (ID_APRESENTACAO,ID_CADEIRA,DS_CADEIRA,DS_SETOR,ID_SESSION,DT_VALIDADE,ID_APRESENTACAO_BILHETE,CD_BINITAU,NR_BENEFICIO) VALUES (?,?,?,?,?,DATEADD(MI, ?, GETDATE()),?,?,?)';
					$params = array($_POST['apresentacao'], $rs['INDICE'], $rs['NOMOBJETO'], $rs['NOMSETOR'], session_id(), $compraExpireTime, $id_apresentacao_bilhete, $bin, $codigo);
					$errors = executeSQL($mainConnection, $query, $params) and $errors;
					
					$query = 'INSERT INTO TABLUGSALA
										  (CODAPRESENTACAO
										  ,INDICE
										  ,CODTIPBILHETE
										  ,CODCAIXA
										  ,CODVENDA
										  ,STAIMPRESSAO
										  ,STACADEIRA
										  ,CODUSUARIO
										  ,CODRESERVA
										  ,ID_SESSION)
								  VALUES
										  (?,?,?,?,?,?,?,?,?,?)';
					$params = array($_POST['codapresentacao'], $rs['INDICE'], $cod_tip_bilhete, $cod_caixa, NULL, 0, 'T', NULL, NULL, session_id());
					$errors = executeSQL($conn, $query, $params) and $errors;
				}
				
				if ($errors) {
					commitTransaction($mainConnection);
					commitTransaction($conn);
					echo 'true';
				} else {
					rollbackTransaction($mainConnection);
					rollbackTransaction($conn);
					echo 'Não foi possível selecionar o(s) ingresso(s) desejado(s).<br><br>Por favor, tente novamente.<br><br>Se o erro persistir, favor informar o suporte.';
				}
			} else {
				echo 'Neste momento esta(ão) disponível(is) apenas ' . $ingressosDisponiveis . ' ingresso(s)!';
			}
		} else {
			echo 'Você ainda tem ' . ($maxIngressos - $rs[0]) . ' ingresso(s) <br/> para atingir a quantidade limite do pedido. <br/> Por favor, altere a quantidade <br/> selecionada antes de continuar.';
		}

	} else if ($_GET['action'] == 'atualizarCaixaMeiaEntrada' and isset($_REQUEST['id'])) {
		echo getCaixaTotalMeiaEntrada($_REQUEST['id']);
	} else if ($_GET['action'] == 'apresentacaoAtual') {
		echo getURLApresentacaoAtual();
	}
}
?>