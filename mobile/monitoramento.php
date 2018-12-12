<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
$mainConnection = mainConnection();

if (acessoPermitido($mainConnection, $_POST['admin'], 330, true)) {
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
        $totalQuantidade = 0;
        $totalValor = 0;
        while ($rs = fetchResult($result)) {
            $json[] = array(
                "TIPBILHETE" => utf8_encode2($rs['TIPBILHETE']),
                "QTDE" => $rs['QTDE'],
                "VALORUNITARIO" => number_format($rs['VALORUNITARIO'], 2, ',', '.'),
                "TOTAL" => number_format($rs['TOTAL'], 2, ',', '.')
            );
            $totalQuantidade += $rs['STATIPBILHMEIA'] != 'S' ? $rs['QTDE'] : 0;
            $totalValor += $rs['TOTAL'];
        }
        $json[] = array(
            "TOTALQTDE" => $totalQuantidade,
            "TOTALVALOR" => number_format($totalValor, 2, ',', '.')
        );
    } else {
        $json[] = array("resultado" => "Nenhum acesso encontrado.");
    }
    echo json_encode($json);
}else{
    $json[] = array("resultado" => "Acesso Negado");
    echo json_encode($json);
}
?>