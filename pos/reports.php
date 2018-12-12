<?php

$mainConnection = mainConnection();

echo_header();

if ($_GET['subscreen']) {

	switch ($_GET['subscreen']) {
		// PDV - Vendas do Usuário Logado
		case 380:

			$data_inicial = substr($_GET['data_inicial'], -4) . substr($_GET['data_inicial'], 2, 2) . substr($_GET['data_inicial'], 0, 2);
			$data_final = substr($_GET['data_final'], -4) . substr($_GET['data_final'], 2, 2) . substr($_GET['data_final'], 0, 2);

			if (isset($_GET['data_inicial']) and isset($_GET['data_final'])
				and $data_final >= $data_inicial and $data_final != '' and $data_inicial != '') {

				$data_inicial = substr($_GET['data_inicial'], 0, 2) .'/'. substr($_GET['data_inicial'], 2, 2) .'/'. substr($_GET['data_inicial'], -4);
				$data_final = substr($_GET['data_final'], 0, 2) .'/'. substr($_GET['data_final'], 2, 2) .'/'. substr($_GET['data_final'], -4);

				echo utf8_decode("<CONSOLE><BR> Imprimindo relatório<BR> PDV - Vendas do Usuário Logado<BR><BR> Data inicial: $data_inicial<BR> Data final:   $data_final</CONSOLE>");
				
				$strSql = "SELECT
								UI.DS_NOME,
								ISNULL(LE.DS_LOCAL_EVENTO, '" . utf8_decode('Não informado no cadastro de evento') . "') DS_LOCAL_EVENTO,
								ISNULL(E.DS_EVENTO, '" . utf8_decode('Não informado no cadastro de evento') . "') DS_EVENTO,
								MP.DS_MEIO_PAGAMENTO,
								SUM(IPV.QT_INGRESSOS) QT_INGRESSOS,
								SUM(IPV.VL_UNITARIO) TOTAL_VENDA,
								SUM(IPV.VL_TAXA_CONVENIENCIA) TOTAL_CONVENIENCIA
								
							FROM MW_PEDIDO_VENDA PV
								INNER JOIN MW_USUARIO UI
								ON UI.ID_USUARIO = PV.ID_USUARIO_CALLCENTER

								INNER JOIN MW_ITEM_PEDIDO_VENDA IPV
								ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA

								INNER JOIN MW_APRESENTACAO A
								ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
								
								INNER JOIN MW_EVENTO E
								ON E.ID_EVENTO = A.ID_EVENTO

								INNER JOIN MW_ACESSO_CONCEDIDO AC
								ON AC.ID_USUARIO = UI.ID_USUARIO
								AND AC.ID_BASE = E.ID_BASE
								AND AC.CODPECA = E.CODPECA
								
								LEFT JOIN MW_LOCAL_EVENTO LE
								ON LE.ID_LOCAL_EVENTO = E.ID_LOCAL_EVENTO
								
								INNER JOIN MW_MEIO_PAGAMENTO MP
								ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO

							WHERE DT_HORA_CANCELAMENTO IS NULL
							AND DT_PEDIDO_VENDA BETWEEN CONVERT(DATETIME, ? + ' 00:00:00', 103) AND CONVERT(DATETIME, ? + ' 23:59:59', 103)
							AND PV.IN_SITUACAO = 'F'
							AND PV.ID_USUARIO_CALLCENTER = ?
							GROUP BY 
								UI.DS_NOME,
								LE.DS_LOCAL_EVENTO,
								E.DS_EVENTO,
								MP.DS_MEIO_PAGAMENTO
							ORDER BY LE.DS_LOCAL_EVENTO, UI.DS_NOME, E.DS_EVENTO, TOTAL_VENDA DESC";
				$params = array($data_inicial, $data_final, $_SESSION['admin']);
				$result = executeSQL($mainConnection, $strSql, $params);
				
				$query = "SELECT
								SUM(IPV.QT_INGRESSOS) QT_INGRESSOS,
								SUM(IPV.VL_UNITARIO) TOTAL_VENDA,
								SUM(IPV.VL_TAXA_CONVENIENCIA) TOTAL_CONVENIENCIA
								
							FROM MW_PEDIDO_VENDA PV
								INNER JOIN MW_USUARIO UI
								ON UI.ID_USUARIO = PV.ID_USUARIO_CALLCENTER

								INNER JOIN MW_ITEM_PEDIDO_VENDA IPV
								ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA

								INNER JOIN MW_APRESENTACAO A
								ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
								
								INNER JOIN MW_EVENTO E
								ON E.ID_EVENTO = A.ID_EVENTO

								INNER JOIN MW_ACESSO_CONCEDIDO AC
								ON AC.ID_USUARIO = UI.ID_USUARIO
								AND AC.ID_BASE = E.ID_BASE
								AND AC.CODPECA = E.CODPECA
								
								INNER JOIN MW_MEIO_PAGAMENTO MP
								ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO

							WHERE PV.DT_HORA_CANCELAMENTO IS NULL
							AND PV.DT_PEDIDO_VENDA BETWEEN CONVERT(DATETIME, ? + ' 00:00:00', 103) AND CONVERT(DATETIME, ? + ' 23:59:59', 103)
							AND PV.IN_SITUACAO = 'F'
							AND PV.ID_USUARIO_CALLCENTER = ?";
				$rs = executeSQL($mainConnection, $query, $params, true);

				$total['TOTAL_PEDIDO'] = $rs['TOTAL_VENDA'];
				$total['QUANTIDADE'] = $rs['QT_INGRESSOS'];
				$total['TOTAL_CONVENIENCIA'] = $rs['TOTAL_CONVENIENCIA'];

				if ($result) {

					echo "<CHGPRNFNT SIZE=4 FACE=FONTE3 BOLD>";
					echo "<PRINTER>".str_pad(date('d/m/Y H:i:s'), 41, ' ', STR_PAD_LEFT)."<BR><BR>";
					echo "PDV - Vendas do Usuario Logado<BR><BR> Data inicial: $data_inicial<BR> Data final:   $data_final<BR><BR></PRINTER>";

					echo "<CHGPRNFNT SIZE=4 FACE=FONTE1>";

					$lastLocal = '';
					$lastUsuario = '';
					$lastEvento = '';
					$somaTotal = $somaTotalUsuario = 0;
					$somaQuant = $somaQuantUsuario = 0;
					$somaServico = $somaServicoUsuario = 0;
					$meio_pagamento = array();
					
					echo "<PRINTER>";

					while($rs = fetchResult($result)) {
						// quebra por usuario
						if ($lastUsuario != $rs['DS_NOME'] and $lastUsuario != '') {
							echo " Sub-Total (usuario)<BR>";
							echo "    Quantidade ".str_pad(' '.$somaQuantUsuario, 26, '.', STR_PAD_LEFT)."<BR>";
							echo "    Ingressos ".str_pad(' '.number_format($somaTotalUsuario, 2, ',', '.'), 27, '.', STR_PAD_LEFT)."<BR>";
							echo "    Servico ".str_pad(' '.number_format($somaServicoUsuario, 2, ',', '.'), 29, '.', STR_PAD_LEFT)."<BR>";
							echo "<BR>";

							$somaTotalUsuario = 0;
							$somaQuantUsuario = 0;
							$somaServicoUsuario = 0;
						}
						// quebra por local
						if ($lastLocal != $rs['DS_LOCAL_EVENTO'] and $lastLocal != '') {
							echo " Sub-Total (local)<BR>";
							echo "    Quantidade ".str_pad(' '.$somaQuant, 26, '.', STR_PAD_LEFT)."<BR>";
							echo "    Ingressos ".str_pad(' '.number_format($somaTotal, 2, ',', '.'), 27, '.', STR_PAD_LEFT)."<BR>";
							echo "    Servico ".str_pad(' '.number_format($somaServico, 2, ',', '.'), 29, '.', STR_PAD_LEFT)."<BR>";
							echo "<BR>";

							$somaTotal = $somaTotalUsuario = 0;
							$somaQuant = $somaQuantUsuario = 0;
							$somaServico = $somaServicoUsuario = 0;
						}

						if ($rs['DS_LOCAL_EVENTO'] != $lastLocal) {
							echo "Local: ".remove_accents($rs['DS_LOCAL_EVENTO'], false)."<BR>";
							$lastLocal = $rs['DS_LOCAL_EVENTO'];
						}

						if ($rs['DS_NOME'] != $lastUsuario) {
							echo substr(' '.remove_accents($rs['DS_NOME'], false), 0, 41)."<BR>";
							$lastUsuario = $rs['DS_NOME'];
						}

						if ($rs['DS_EVENTO'] != $lastEvento) {
							echo "</PRINTER>";
							echo "<CHGPRNFNT SIZE=4 FACE=FONTE3 BOLD>";
							echo "<PRINTER>";
							echo '  '.substr(remove_accents($rs['DS_EVENTO'], false), 0, 40)."<BR>";
							echo "</PRINTER>";
							echo "<CHGPRNFNT SIZE=4 FACE=FONTE1>";
							echo "<PRINTER>";
							$lastEvento = $rs['DS_EVENTO'];
						}
						
						echo '   '.substr(remove_accents($rs['DS_MEIO_PAGAMENTO'], false), 0, 39)."<BR>";

						$meio_pagamento[$rs['DS_MEIO_PAGAMENTO']]['quantidade'] += $rs['QT_INGRESSOS'];
						$meio_pagamento[$rs['DS_MEIO_PAGAMENTO']]['ingressos'] += $rs['TOTAL_VENDA'];
						$meio_pagamento[$rs['DS_MEIO_PAGAMENTO']]['servico'] += $rs['TOTAL_CONVENIENCIA'];
						
						echo "    Quantidade ".str_pad(' '.$rs['QT_INGRESSOS'], 26, '.', STR_PAD_LEFT)."<BR>";
						echo "    Ingressos ".str_pad(' '.number_format($rs['TOTAL_VENDA'], 2, ',', '.'), 27, '.', STR_PAD_LEFT)."<BR>";
						echo "    Servico ".str_pad(' '.number_format($rs['TOTAL_CONVENIENCIA'], 2, ',', '.'), 29, '.', STR_PAD_LEFT)."<BR>";

						$somaTotal += $rs['TOTAL_VENDA'];
						$somaQuant += $rs['QT_INGRESSOS'];
						$somaServico += $rs['TOTAL_CONVENIENCIA'];

						$somaTotalUsuario += $rs['TOTAL_VENDA'];
						$somaQuantUsuario += $rs['QT_INGRESSOS'];
						$somaServicoUsuario += $rs['TOTAL_CONVENIENCIA'];
					}

					echo " Sub-Total (usuario)<BR>";
					echo "    Quantidade ".str_pad(' '.$somaQuantUsuario, 26, '.', STR_PAD_LEFT)."<BR>";
					echo "    Ingressos ".str_pad(' '.number_format($somaTotalUsuario, 2, ',', '.'), 27, '.', STR_PAD_LEFT)."<BR>";
					echo "    Servico ".str_pad(' '.number_format($somaServicoUsuario, 2, ',', '.'), 29, '.', STR_PAD_LEFT)."<BR>";
					
					echo " Sub-Total (local)<BR>";
					echo "    Quantidade ".str_pad(' '.$somaQuant, 26, '.', STR_PAD_LEFT)."<BR>";
					echo "    Ingressos ".str_pad(' '.number_format($somaTotal, 2, ',', '.'), 27, '.', STR_PAD_LEFT)."<BR>";
					echo "    Servico ".str_pad(' '.number_format($somaServico, 2, ',', '.'), 29, '.', STR_PAD_LEFT)."<BR>";
					
					echo "<BR>";
					echo " Total geral<BR>";
					echo "    Quantidade ".str_pad(' '.$total['QUANTIDADE'], 26, '.', STR_PAD_LEFT)."<BR>";
					echo "    Ingressos ".str_pad(' '.number_format($total['TOTAL_PEDIDO'], 2, ',', '.'), 27, '.', STR_PAD_LEFT)."<BR>";
					echo "    Servico ".str_pad(' '.number_format($total['TOTAL_CONVENIENCIA'], 2, ',', '.'), 29, '.', STR_PAD_LEFT)."<BR>";
					echo "<BR>";
					
					echo str_repeat("-=", 21);
					echo "<BR><BR>";

					echo "</PRINTER>";
					echo "<PRINTER>";

					echo " Total por Forma de Pagamento<BR><BR>";

					foreach ($meio_pagamento as $key => $value) {
						echo '  '.substr(remove_accents($key, false), 0, 40)."<BR>";

						echo "    Quantidade ".str_pad(' '.$value['quantidade'], 26, '.', STR_PAD_LEFT)."<BR>";
						echo "    Ingressos ".str_pad(' '.number_format($value['ingressos'], 2, ',', '.'), 27, '.', STR_PAD_LEFT)."<BR>";
						echo "    Servico ".str_pad(' '.number_format($value['servico'], 2, ',', '.'), 29, '.', STR_PAD_LEFT)."<BR>";
					}

					echo "<BR><BR><BR><BR><BR><BR><BR>";

					echo "</PRINTER>";

					echo "<CONSOLE><BR> Finalizado!</CONSOLE>";

				}

			} else {

				if ($data_inicial > $data_final) {
					display_error("A data final deve ser maior que a data inicial.");

					echo_header();
				}

				echo "<GET TYPE=HIDDEN NAME=subscreen VALUE=380>";

				echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Informe a dt inicial de venda</WRITE_AT>");
				echo utf8_decode("<WRITE_AT LINE=6 COLUMN=0> (DD/MM/AAAA):</WRITE_AT>");
				echo "<GET TYPE=DATA NAME=data_inicial SIZE=8 COL=10 LIN=9>";
				
				echo utf8_decode("<WRITE_AT LINE=10 COLUMN=0> Informe a dt final de venda</WRITE_AT>");
				echo utf8_decode("<WRITE_AT LINE=11 COLUMN=0> (DD/MM/AAAA):</WRITE_AT>");
				echo "<GET TYPE=DATA NAME=data_final SIZE=8 COL=10 LIN=14>";
			}

		break;

		// retorna ao menu
		default:
			echo "<GET TYPE=HIDDEN NAME=reset VALUE=1>";
		break;
	}

	echo "<POST>";

	die();
}

// menu
echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> Selecione o relatório para</WRITE_AT>");
echo utf8_decode("<WRITE_AT LINE=6 COLUMN=0> impressão:</WRITE_AT>");

$report_options	= array(
	999999999	=> 'Voltar',
	380			=> 'PDV - Vendas do Usuário Logado'
);

echo_select('subscreen', $report_options, 4);

echo "<POST>";