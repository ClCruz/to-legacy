<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
if (acessoPermitido($mainConnection, $_SESSION['admin'], 330, true)) {

	if ($_GET['action'] == 'getTable') { /*------------ REPORT ------------*/

		$conn = getConnection($_POST['cboTeatro']);

		$_POST['cboSala'] = $_POST['cboSala'] == 'TODOS' ? '' : $_POST['cboSala'];

		$query = "WITH RESULTADO AS (
					SELECT
						B.TIPBILHETE,
						L.VALPAGTO,
						B.STATIPBILHMEIA,
						ISNULL(TIA.VALOR, 0) AS VLRAGREGADOS,
						ISNULL((SELECT  
								(L.VALPAGTO - ISNULL(TIA.VALOR, 0)) * CASE TTLB.ICDEBCRE WHEN 'D' THEN (ISNULL(TTBTL.VALOR,0)/100) ELSE (ISNULL(TTBTL.VALOR,0)/100) * -1 END
							FROM
								TABTIPBILHTIPLCTO TTBTL
							INNER JOIN
								TABTIPLANCTOBILH TTLB
								ON TTLB.CODTIPLCT = TTBTL.CODTIPLCT
								AND TTLB.ICPERCVLR  = 'P'
								AND TTLB.ICUSOLCTO != 'C'
								AND TTLB.INATIVO    = 'A'
							WHERE
								TTBTL.CODTIPBILHETE = L.CODTIPBILHETE
								AND	TTBTL.DTINIVIG = (SELECT MAX(TTBTL1.DTINIVIG) 
														 FROM TABTIPBILHTIPLCTO  TTBTL1,
															  TABTIPLANCTOBILH   TTLB1
														WHERE TTBTL1.CODTIPBILHETE = TTBTL.CODTIPBILHETE
														  AND TTBTL1.CODTIPLCT     = TTBTL.CODTIPLCT
														  AND TTBTL1.DTINIVIG     <= L.DATMOVIMENTO
														  AND TTBTL1.INATIVO       = 'A'
														  AND TTLB1.CODTIPLCT     = TTBTL1.CODTIPLCT
														  AND TTLB1.ICPERCVLR     = 'P'
														  AND TTLB1.ICUSOLCTO    != 'C'
														  AND TTLB1.INATIVO       = 'A')
														AND TTBTL.INATIVO = 'A'), 0) AS OUTROSVALORES1,
						ISNULL((SELECT  
								CASE TTLB.ICDEBCRE WHEN 'D' THEN ISNULL(TTBTL.VALOR,0) ELSE ISNULL(TTBTL.VALOR,0) * -1 END
							FROM
								TABTIPBILHTIPLCTO TTBTL
							INNER JOIN
								TABTIPLANCTOBILH	TTLB
								ON  TTLB.CODTIPLCT  = TTBTL.CODTIPLCT
								AND TTLB.ICPERCVLR  = 'V'
								AND TTLB.ICUSOLCTO != 'C'
								AND TTLB.INATIVO    = 'A'
							WHERE
								TTBTL.CODTIPBILHETE = L.CODTIPBILHETE
							AND	TTBTL.DTINIVIG      = (SELECT MAX(TTBTL1.DTINIVIG) 
														 FROM TABTIPBILHTIPLCTO  TTBTL1,
															  TABTIPLANCTOBILH   TTLB1
														WHERE TTBTL1.CODTIPBILHETE = TTBTL.CODTIPBILHETE
														  AND TTBTL1.CODTIPLCT     = TTBTL.CODTIPLCT
														  AND TTBTL1.DTINIVIG     <= L.DATMOVIMENTO
														  AND TTBTL1.INATIVO       = 'A'
														  AND TTLB1.CODTIPLCT     = TTBTL1.CODTIPLCT
														  AND TTLB1.ICPERCVLR     = 'V'
														  AND TTLB1.ICUSOLCTO    != 'C'
														  AND TTLB1.INATIVO       = 'A')
														AND TTBTL.INATIVO        = 'A'), 0) AS OUTROSVALORES2
					FROM TABCONTROLESEQVENDA C
				   	INNER JOIN TABTIPBILHETE B ON B.CODTIPBILHETE = SUBSTRING(C.CODBAR, 15, 3)
				    INNER JOIN TABLANCAMENTO L ON L.CODAPRESENTACAO = SUBSTRING(C.CODBAR, 1, 5)
				            AND L.CODTIPBILHETE = SUBSTRING(C.CODBAR, 15, 3)
				            AND L.CODTIPLANCAMENTO IN (1, 4)
				            AND L.INDICE = C.INDICE
				            and not exists (SELECT 1 FROM TABLANCAMENTO LE 
							                WHERE LE.NUMLANCAMENTO = L.NUMLANCAMENTO
							                AND LE.CODAPRESENTACAO = L.CODAPRESENTACAO
							                AND LE.CODTIPBILHETE = L.CODTIPBILHETE
							                AND LE.CODTIPLANCAMENTO = 2
							                AND LE.INDICE = L.INDICE)
					INNER JOIN TABAPRESENTACAO A ON A.CODAPRESENTACAO = C.CODAPRESENTACAO

					INNER JOIN TABLUGSALA TLS ON TLS.CODAPRESENTACAO = L.CODAPRESENTACAO
												AND TLS.INDICE = L.INDICE
												AND TLS.CODTIPBILHETE = L.CODTIPBILHETE
					LEFT JOIN TABINGRESSOAGREGADOS TIA ON TIA.CODVENDA = TLS.CODVENDA
												AND TIA.INDICE = TLS.INDICE
					WHERE
						C.STATUSINGRESSO = 'U'
					AND A.DATAPRESENTACAO = ?
					AND A.HORSESSAO = ?
					AND (A.CODSALA = ? OR ? = '')
					AND A.CODPECA = ?
				)
				SELECT
					TIPBILHETE,
					COUNT(1) QTDE,
					(VALPAGTO - VLRAGREGADOS + OUTROSVALORES1 + OUTROSVALORES2) AS VALORUNITARIO,
					STATIPBILHMEIA,
					COUNT(1) * (VALPAGTO - VLRAGREGADOS + OUTROSVALORES1 + OUTROSVALORES2) AS TOTAL
				FROM RESULTADO
				GROUP BY 
					TIPBILHETE,
					(VALPAGTO - VLRAGREGADOS + OUTROSVALORES1 + OUTROSVALORES2),
					STATIPBILHMEIA
				ORDER BY TIPBILHETE";
		$params = array($_POST['cboApresentacao'], $_POST['cboHorario'], $_POST['cboSala'], $_POST['cboSala'], $_POST['cboPeca']);
		$result = executeSQL($conn, $query, $params);

		if (hasRows($result)) {

			$html = '<tr class="ui-widget-header">
							<th>Tipo de Bilhete</th>
							<th align="right">Qtde. Ingressos</th>
							<th align="right">Preço Unitário</th>
							<th align="right">Total</th>
						</tr>';

			$totalQuantidade = 0;
			$totalValor = 0;

			while ($rs = fetchResult($result)) {
				$html .= '<tr>
							<td>'.utf8_encode2($rs['TIPBILHETE']).'</td>
							<td align="right">'.$rs['QTDE'].'</td>
							<td align="right">'.number_format($rs['VALORUNITARIO'], 2, ',', '').'</td>
							<td align="right">'.number_format($rs['TOTAL'], 2, ',', '').'</td>
						</tr>';

				$totalQuantidade += $rs['STATIPBILHMEIA'] != 'S' ? $rs['QTDE'] : 0;
				$totalValor += $rs['TOTAL'];
			}

			$html .= '<tr>
						<th>Total de Acessos (Pessoas)</th>
						<th align="right">'.$totalQuantidade.'</th>
						<th></th>
						<th align="right">'.number_format($totalValor, 2, ',', '').'</th>
					</tr>';

		} else {
			$html = '<tr><td colspan="4" style="text-align: center; font-size: medium; font-weight: bold;">Nenhum acesso encontrado.</td></tr>';
		}

		$header .= '<tr class="print_only"><th colspan="4">'.multiSite_getName().' – Clique e Bom Espetáculo</th></tr>
					<tr class="print_only"><th colspan="4">CNPJ ' . multiSite_CNPJ() . '</th></tr>
					<tr><th colspan="4">Data e Horário: '.date("d/m/Y H:i").'</th></tr>
					<tr class="print_only"><th colspan="4">&nbsp;</th></tr>
					<tr class="print_only"><th colspan="4">Relatório e Atestado de Controle de Acesso</th></tr>
					<tr class="print_only"><td colspan="4">
						Atestamos para os devidos fins de direito e a quem possa interessar que o "EVENTO TAL" 
						realizado no "LOCAL TAL" no dia "DIA TAL" no horário "HORARIO TAL" no(s) setor(es) "SETOR TAL", teve o PÚBLICO 
						PRESENTE de '.($totalQuantidade ? $totalQuantidade : '0').' pessoas. Esse número foi gerado através dos equipamentos 
						de leitura onde abaixo segue o detalhamento:
					</td></tr>
					<tr class="print_only"><th colspan="4" align="right">&nbsp;</th></tr>';

		$footer = '<tr class="print_only">
						<td colspan="4">
							<table>
								<tr>
									<td colspan="3">Atenciosamente,</td>
								</tr>
								<tr>
									<td colspan="3">&nbsp;</td>
								</tr>
								<tr>
									<td width="33%"><hr /></td>
									<td width="33%"><hr /></td>
									<td width="33%"><hr /></td>
								</tr>
								<tr>
									<td>
										'.multiSite_getName().'<br />
										CONTROLADOR DE ACESSO
									</td>
									<td>LOCAL</td>
									<td>PRODUÇÃO</td>
								</tr>
							</table>
						</td>
					</th>
					<tr class="print_only"><td colspan="4">&nbsp;</td></tr>
					<tr class="print_only"><td colspan="4">Obs. Importante: Este relatório contabiliza apenas as pessoas que efetivamente compareceram ao evento, isto significa que a quantidade de ingressos e valores expressos no borderô podem ser diferentes dos demonstrados neste relatório, por razões de complemento de meia entrada e ausência do expectador ao evento.</td></tr>';
		echo $header.$html.$footer;
		die();
		
	} elseif ($_GET['action'] == 'cboTeatro') {

		$query = "SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO
					FROM MW_BASE B
					INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE
					WHERE AC.ID_USUARIO = ? AND B.IN_ATIVO = '1'
					ORDER BY B.DS_NOME_TEATRO";
		$result = executeSQL($mainConnection, $query, array($_SESSION['admin']));

		$combo = '<option value="">Selecione...</option>';
        while ($rs = fetchResult($result)) {
            $combo .= '<option value="' . $rs['ID_BASE'] . '"' . (($selected == $rs['ID_BASE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_NOME_TEATRO']) . '</option>';
        }

        echo $combo;
        die();

	} elseif ($_GET['action'] == 'cboPeca' and isset($_GET['cboTeatro'])) {

		$conn = getConnection($_GET['cboTeatro']);

		$query = "EXEC SP_PEC_CON009;8 ?, ?";
		$params = array($_SESSION['admin'], $_GET['cboTeatro']);
		$result = executeSQL($conn, $query, $params);

		$html = '<option value="">Selecione...</option>';

		while($rs = fetchResult($result)){
			$html .= '<option value="'. $rs["CodPeca"] .'">'. utf8_encode2($rs["nomPeca"]) .'</option>';	
		}

		echo $html;
		die();

	} elseif ($_GET['action'] == 'cboApresentacao' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca'])) {

		$conn = getConnection($_GET['cboTeatro']);

		$query = "SELECT tbAp.DatApresentacao
		            from tabApresentacao tbAp (nolock)
		            inner join tabPeca tbPc (nolock)
		                        on        tbPc.CodPeca = tbAp.CodPeca
		            inner join ci_middleway..mw_acesso_concedido iac (nolock)
		                        on                    iac.id_base = ?
										and			  iac.id_usuario = ?
										and			  iac.CodPeca = tbAp.CodPeca
		            where               tbPc.CodPeca = ?
					            /*AND CONVERT(DATETIME, CONVERT(VARCHAR(8), TBAP.DATAPRESENTACAO, 112) + ' ' + TBAP.HORSESSAO)
									>= CONVERT(DATETIME, CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112) + ' 22:00')
					            AND TBAP.DATAPRESENTACAO <= GETDATE()*/
		            group by tbAp.DatApresentacao
		            order by tbAp.DatApresentacao";
		$params = array($_GET['cboTeatro'], $_SESSION['admin'], $_GET['cboPeca']);
		$result = executeSQL($conn, $query, $params);

		$html = '<option value="">Selecione...</option>';
		
		while($rs = fetchResult($result)){
			$html .= '<option value="'. $rs["DatApresentacao"]->format("Ymd") .'">'. $rs["DatApresentacao"]->format("d/m/Y") .'</option>';	
		}
		
		echo $html;
		die();

	} elseif ($_GET['action'] == 'cboHorario' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca']) and isset($_GET['cboApresentacao'])) {

		$conn = getConnection($_GET['cboTeatro']);

		$query = "SELECT HorSessao
		            from tabApresentacao tbAp (nolock)
		            inner join tabPeca tbPc (nolock)
		                        on        tbPc.CodPeca = tbAp.CodPeca
		            inner join ci_middleway..mw_acesso_concedido iac (nolock)
		                        on                    iac.id_base = ?
										and			  iac.id_usuario = ?
										and			  iac.CodPeca = tbAp.CodPeca
		            where       tbPc.CodPeca = ?
				            /*AND CONVERT(DATETIME, CONVERT(VARCHAR(8), TBAP.DATAPRESENTACAO, 112) + ' ' + TBAP.HORSESSAO)
				            	>= CONVERT(DATETIME, CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112) + ' 22:00')*/
				            AND TBAP.DATAPRESENTACAO = CONVERT(DATETIME, ?, 112)
		            group by tbAp.HorSessao
		            order by tbAp.HorSessao";
		$params = array($_GET['cboTeatro'], $_SESSION['admin'], $_GET['cboPeca'], $_GET['cboApresentacao']);
		$result = executeSQL($conn, $query, $params);

		$html = '<option value="">Selecione...</option>';

		while($rs = fetchResult($result)){
			$html .= '<option value="'. $rs["HorSessao"] .'">'. $rs["HorSessao"] .'</option>';	
		}

		echo $html;
		die();

	} elseif ($_GET['action'] == 'cboSala' and isset($_GET['cboTeatro']) and isset($_GET['cboPeca']) and isset($_GET['cboApresentacao']) and isset($_GET['cboHorario'])) {

		$query = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ?";
		$rs = executeSQL($mainConnection, $query, array($_GET['cboTeatro']), true);

		$conn = getConnectionTsp();

		$query = "EXEC SP_REL_BORDERO_VENDAS;7 ?, ?, ?, ?";
		$params = array($_GET['cboApresentacao'], $_GET['cboPeca'], $_GET['cboHorario'], $rs['DS_NOME_BASE_SQL']);
		$result = executeSQL($conn, $query, $params);

		$html = "<option value=''>Selecione...</option>
				 <option value='TODOS'>&lt; TODOS &gt;</option>";

		while($rs = fetchResult($result)){
			$html .= '<option value="'. $rs["codsala"] .'">'. utf8_encode2($rs["nomSala"]) .'</option>';	
		}

		echo $html;
		die();

	}

}
?>