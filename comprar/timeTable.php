<?php
header('Content-type: application/json');

if (isset($_GET['evento']) and is_numeric($_GET['evento'])) {
	require_once('../settings/functions.php');
	require_once('../settings/settings.php');

	$mainConnection = mainConnection();

	// Verifica a base de dados de origem do evento para poder verificar se ela ainda está habilitada para venda na web
	$query = 'SELECT DS_EVENTO, DS_NOME_TEATRO, DS_NOME_BASE_SQL, CODPECA
				 FROM MW_EVENTO E
				 INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
				 WHERE E.ID_EVENTO = ? AND E.IN_ATIVO = \'1\'';

	$params = array($_GET['evento']);
	$rs = executeSQL($mainConnection, $query, $params, true);

	$evento_info = getEvento($_GET['evento']);
	
	$nomeBase = $rs['DS_NOME_BASE_SQL'];
	$nomeTeatro = $evento_info['nome_teatro'];
	$nomeEvento = $rs['DS_EVENTO'];
	
	if (!empty($rs)) {
		if ($_POST['pos']) {
			$rs['QT_HR_ANTECED'] = 0;
		} else {
			// Verifica se o evento está ativo e se pode vender pela web
			$query = "SELECT (ISNULL(QT_HR_ANTECED, 24) * -1) AS QT_HR_ANTECED
						 FROM ".$nomeBase."..TABPECA
						 WHERE CODPECA = ? AND STAPECA = 'A' AND CONVERT(CHAR(8), DATFINPECA,112) >= CONVERT(CHAR(8), GETDATE(),112)";
			
			$params = array($rs['CODPECA']);
			$rs = executeSQL($mainConnection, $query, $params, true);
		}
		
		if (!empty($rs)) {
			$query = "WITH RESULTADO AS (
						SELECT E.DS_EVENTO, A.ID_APRESENTACAO, CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) DT_APRESENTACAO,
								A.HR_APRESENTACAO, A.DT_APRESENTACAO DT_APRESENTACAO_ORDER,
								DATEDIFF(HH, DATEADD(HH, ?, CONVERT(DATETIME, CONVERT(VARCHAR, A.DT_APRESENTACAO, 112) + ' '
								+ LEFT(HR_APRESENTACAO,2) + ':' + RIGHT(HR_APRESENTACAO,2) + ':00')) ,GETDATE() ) AS TELEFONE,
								DS_PISO
						FROM MW_EVENTO E
						INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO AND A.IN_ATIVO = '1'
						INNER JOIN ".$nomeBase."..TABPECA TP ON TP.CODPECA = E.CODPECA
						WHERE E.ID_EVENTO = ? AND E.IN_ATIVO = '1'
						AND CONVERT(CHAR(8), A.DT_APRESENTACAO,112) >= CONVERT(CHAR(8), GETDATE(),112)
						AND A.ID_APRESENTACAO IN
						(
							SELECT A1.ID_APRESENTACAO
							FROM MW_APRESENTACAO A1
							WHERE A1.ID_EVENTO = A.ID_EVENTO
							AND A1.IN_ATIVO = '1'
							AND CONVERT(CHAR(8), A1.DT_APRESENTACAO,112) >= CONVERT(CHAR(8), GETDATE(),112)
							AND DS_PISO IN (
								SELECT DISTINCT A2.DS_PISO
								FROM MW_APRESENTACAO A2
								WHERE A2.IN_ATIVO = '1'
								AND A2.ID_EVENTO = A1.ID_EVENTO
								AND A2.DT_APRESENTACAO = A1.DT_APRESENTACAO
							)
						)
						AND  (
							(TP.QT_DIAS_APRESENTACAO <> 0 AND A.DT_APRESENTACAO <= DATEADD(DAY, TP.QT_DIAS_APRESENTACAO, GETDATE()))
							OR
							(TP.QT_DIAS_APRESENTACAO = 0)
						)
					)
					SELECT DS_EVENTO, MIN(ID_APRESENTACAO) AS ID_APRESENTACAO, DT_APRESENTACAO, HR_APRESENTACAO, DT_APRESENTACAO_ORDER, TELEFONE, DS_PISO
					FROM RESULTADO R1
					WHERE DS_PISO = (SELECT TOP 1 DS_PISO FROM RESULTADO R2 WHERE R1.DT_APRESENTACAO = R2.DT_APRESENTACAO AND R1.HR_APRESENTACAO = R2.HR_APRESENTACAO ORDER BY DS_PISO ".(in_array($_GET['evento'], array(17898,17896,17897)) ? 'DESC' : '').")
					GROUP BY DS_EVENTO, DT_APRESENTACAO, HR_APRESENTACAO, DT_APRESENTACAO_ORDER, TELEFONE, DS_PISO
					ORDER BY DT_APRESENTACAO_ORDER, HR_APRESENTACAO";
			
			$params = array($rs['QT_HR_ANTECED'], $_GET['evento']);
			$result = executeSQL($mainConnection, $query, $params);
			
			if (hasRows($result)) {
				$pageURL = 'http';
				if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
				$pageURL .= "://";
				if ($_SERVER["SERVER_PORT"] != "80") {
					$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
				} else {
					$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
				}
				$url = str_replace(basename($pageURL), '', $pageURL);
				$tag = '';

                if($isContagemAcessos){
                    //Carregar xml para evento
                    $xml = simplexml_load_file("campanha.xml");
                    foreach($xml->item as $item){
                        if($_GET["evento"] == $item->id){
                            $tag = "&tag=". $item->tag ."&tag2=1._Escolha_de_assentos_-_Avançar-TAG";
                        }
                    }
                }

			    if ($_GET['mc_eid'] and $_GET['mc_cid']) {
					$tag .= '&mc_cid=' . $_GET['mc_cid'] . '&mc_eid=' . $_GET['mc_eid'];
			    }

				$horarios = array();	

				while ($rs = fetchResult($result)) {
					$hora = explode('h', $rs['HR_APRESENTACAO']);
					$data = explode('/', $rs['DT_APRESENTACAO']);
					$tempo = mktime($hora[0], $hora[1], 0, $data[1], $data[0], $data[2]);

					if ($rs['TELEFONE'] < 0) {
						$horarios[] = array(
							"nDia" => date('d', $tempo),
							"nMes" => date('m', $tempo),
							"tMes" => utf8_encode2(strtoupper(getDateToString($tempo,"month-small"))),
							"nAno" => date('Y', $tempo),
							"tSemana" => utf8_encode2(strtoupper(getDateToString($tempo,"week-small"))),
							"nHora" => $hora[0],
							"nMinuto" => $hora[1],
							"idApresentacao" => $rs['ID_APRESENTACAO']
						);
					}
				}

				echo json_encode(
					array(
						'evento' => array(
							'nome' => utf8_encode2($nomeEvento),
							'local' => utf8_encode2($nomeTeatro),
							'endereco' => utf8_encode2($evento_info['endereco']),
							'bairro' => utf8_encode2($evento_info['bairro']),
							'cidade' => utf8_encode2($evento_info['cidade']),
							'sigla_estado' => utf8_encode2($evento_info['sigla_estado']),
							'duracao' => $evento_info['duracao'],
							'genero' => utf8_encode2($evento_info['genero']),
							'classificacao' => utf8_encode2($evento_info['classificacao'])
						),
						'horarios' => $horarios
					)
				);
			}
		}
	} else {
		//echo '<p>Evento não localizado ou fora do prazo de exibição.</p>';
	}
}
?>