<?php
session_start();
if (isset($_SESSION['operador']) and is_numeric($_SESSION['operador']) and isset($_GET['teatro'])) {
	require_once('../settings/functions.php');

        $queryAux=" ";
        if(isset($_SESSION['usuario_pdv']) and $_SESSION['usuario_pdv'] == 1){
            $queryAux = "INNER JOIN mw_acesso_concedido iac
                          on iac.id_base = E.ID_BASE
                          and iac.id_usuario = ". $_SESSION['operador'] ."
                          and iac.CodPeca = E.CodPeca";
        }

	$mainConnection = mainConnection();
	$query = "WITH RESULTADO AS
		    (
		    SELECT
                      E.ID_EVENTO, E.DS_EVENTO, A.ID_APRESENTACAO, A.DT_APRESENTACAO, A.DS_PISO, A.HR_APRESENTACAO
                      FROM MW_EVENTO E
                      INNER JOIN MW_APRESENTACAO A
                        ON A.ID_EVENTO = E.ID_EVENTO
                        AND A.IN_ATIVO = 1 ".
                        $queryAux ."
                      WHERE E.IN_ATIVO = 1 AND E.ID_BASE = ?
		      AND CONVERT(CHAR(8), A.DT_APRESENTACAO,112) IN (SELECT CONVERT(CHAR(8), min(A1.DT_APRESENTACAO),112)
				      FROM
				      MW_APRESENTACAO A1
				      WHERE A1.ID_EVENTO = A.ID_EVENTO
                                        AND A1.IN_ATIVO = '1'
					AND CONVERT(CHAR(8), A1.DT_APRESENTACAO,112) >= CONVERT(CHAR(8), GETDATE(),112))
		    )
		    SELECT DS_EVENTO, MIN(ID_APRESENTACAO) ID_APRESENTACAO
		    FROM RESULTADO R
		    WHERE R.HR_APRESENTACAO IN (SELECT MIN(HR_APRESENTACAO) FROM RESULTADO R3 WHERE R3.ID_EVENTO = R.ID_EVENTO GROUP BY R3.ID_EVENTO)
		    GROUP BY DS_EVENTO
		    ORDER BY DS_EVENTO";
	$params = array($_GET['teatro']);
	$result = executeSQL($mainConnection, $query, $params);
	
	if (hasRows($result)) {
	?>

	<p>Selecione o evento desejado:</p>
	<select id="evento">
	<option />
	<?php while ($rs = fetchResult($result)) { ?>
		<option value="etapa1.php?apresentacao=<?php echo $rs['ID_APRESENTACAO']; ?>&eventoDS=<?php echo utf8_encode2($rs['DS_EVENTO']); ?>"><?php echo utf8_encode2($rs['DS_EVENTO']); ?></option>
	<?php } ?>
	</select>
	<?php
	} else {
	?>
	<div class="titulo">
		<h1>Nenhum evento dispon&iacute;vel para esse local.</h1>
	</div>
	<?php
	}
}
?>