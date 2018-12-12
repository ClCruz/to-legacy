<?php
require_once('../settings/settings.php');

if (acessoPermitido($mainConnection, $_SESSION['admin'], 320, true)) {

	if ($_GET['action'] == 'pessoas') {

		$conn = getConnection($_POST['cboTeatro']);

		$query = "
			SELECT 
				isnull(SUM(CASE WHEN DATHRENTRADA IS NOT NULL THEN 1 ELSE 0 END), 0) -
				isnull(SUM(CASE WHEN DATHRSAIDA IS NOT NULL THEN 1 ELSE 0 END), 0) as 'on'
				,isnull(SUM(CASE WHEN DATHRENTRADA IS NOT NULL THEN 1 ELSE 0 END), 0) AS 'in'
				,isnull(SUM(CASE WHEN DATHRSAIDA IS NOT NULL THEN 1 ELSE 0 END), 0) AS 'out'
			FROM 
				TABCONTROLESEQVENDA
			WHERE
				STATUSINGRESSO = 'U'
			AND
				CodApresentacao IN (SELECT 
										CodApresentacao
									FROM 
										tabApresentacao
									WHERE
									 	CODPECA = ? 
									AND 	convert(varchar(10), DATAPRESENTACAO, 112) = convert(varchar, convert(datetime,?,103), 112)
									AND 	HORSESSAO = ?) ";

/*
		$query = "SELECT STUFF ((
						SELECT ',' + CONVERT(VARCHAR, CODAPRESENTACAO)
						FROM 	TABAPRESENTACAO
						WHERE 	CODPECA = ? 
						AND 	convert(varchar(10), DATAPRESENTACAO, 112) = ? 
						AND 	HORSESSAO = ?
						FOR XML PATH('')
					),1,1,'')";
		$params = array($_POST['cboPeca'], $_POST['cboApresentacao'], $_POST['cboHorario']);
		$rs = executeSQL($conn, $query, $params, true);


		$query = "SELECT isnull(COUNT(DISTINCT(B.CODBAR)), 0) as 'in', isnull(SUM(CASE WHEN B.DATHRSAIDA IS NOT NULL THEN 1 ELSE 0 END), 0) as 'out'
					FROM TABCONTROLESEQVENDA A
					INNER JOIN TABCONTROLESEQVENDA B ON B.CODAPRESENTACAO = A.CODAPRESENTACAO AND B.INDICE = A.INDICE AND B.STATUSINGRESSO = A.STATUSINGRESSO
					WHERE A.CODAPRESENTACAO in ({$rs[0]}) AND A.STATUSINGRESSO = 'U'";

		$params = array($rs['CODAPRESENTACAO']);
		$rs = executeSQL($conn, $query, $params, true);

		$rs['on'] = $rs['in'] - $rs['out'];

*/

		$params = array($_POST['cboPeca'], $_POST['dataapresentacao'], $_POST['cboHorario']);
		$rs = executeSQL($conn, $query, $params, true);

		unset($rs[0]);
		unset($rs[1]);
		unset($rs[2]);

		echo json_encode($rs);

		die();

	} else if ($_POST['codigo'] != '') { /*------------ CHECAR BILHETE ------------*/

		$conn = getConnection($_POST['cboTeatro']);

		if (!is_numeric($_POST['codigo']) or strlen($_POST['codigo']) != 22) {
			echo json_encode(array(
				'class' => 'falha',
				'mensagem' => 'Código inválido.'
			));
			die();
		}


		// data confere?
		$query = "SELECT SUBSTRING(CONVERT(VARCHAR(10),DatApresentacao,112),5,4) as Data FROM TABAPRESENTACAO WHERE CodApresentacao = ?";
		$params = array($_POST['cboApresentacao']);
		$rs = executeSQL($conn, $query, $params, true);

		if ( $rs['Data'] != substr($_POST['codigo'], 6, 4)) {
			echo json_encode(array(
				'class' => 'falha',
				'mensagem' => 'Data do ingresso inválida para a apresentação.<br />Ingresso válido para: ' . substr($_POST['codigo'], 8, 2) .'/'. substr($_POST['codigo'], 6, 2)
			));
			die();
		}


		if ($_POST['cboSetor']!='TODOS' && $_POST['cboSetor'] != substr($_POST['codigo'], 5, 1) )  {

			echo json_encode(array(
				'class' => 'falha',
				'mensagem' => 'Setor inválido para a apresentação.<br />Ingresso válido para: '
			));
			die();
		}

		// hora confere?
		if (str_replace(':', '', $_POST['cboHorario']) != substr($_POST['codigo'], 10, 4)) {
			echo json_encode(array(
				'class' => 'falha',
				'mensagem' => 'Este ingresso pertence a outro horário.<br />Ingresso válido para: ' . substr($_POST['codigo'], 10, 2) .':'. substr($_POST['codigo'], 12, 2)
			));
			die();
		}

	
		
		// evento confere?
		$query = "SELECT CODPECA FROM TABAPRESENTACAO WHERE CODAPRESENTACAO = ?";
		$params = array(substr($_POST['codigo'], 0, 5));
		$rs = executeSQL($conn, $query, $params, true);

		if ($_POST['cboPeca'] != $rs['CODPECA']) {
			echo json_encode(array(
				'class' => 'falha',
				'mensagem' => 'Este ingresso pertence a outro evento.'
			));
			die();
		}
		
		$query = "SELECT B.NUMSEQ, B.CODAPRESENTACAO, B.INDICE, B.STATUSINGRESSO, B.DATHRENTRADA, B.DATHRSAIDA
					FROM TABCONTROLESEQVENDA A
					INNER JOIN TABCONTROLESEQVENDA B ON B.CODAPRESENTACAO = A.CODAPRESENTACAO AND B.INDICE = A.INDICE AND B.STATUSINGRESSO = A.STATUSINGRESSO
					WHERE A.CODBAR = ?";
		$params = array($_POST['codigo']);
		$result = executeSQL($conn, $query, $params);
		
		if (hasRows($result)) {
			// pode retornar 2 linhas no caso de complemento de ingressos, mas como sao o mesmo ingresso podem ser tratados como 1 so
			if ($_POST['sentido'] == 'entrada') {
				
				while ($rs = fetchResult($result)) {
					if ($rs['STATUSINGRESSO'] == 'L') {
						$query = "UPDATE TABCONTROLESEQVENDA SET
									DATHRENTRADA = GETDATE(),
									STATUSINGRESSO = 'U'
									WHERE NUMSEQ = ?
									AND CODAPRESENTACAO =?
									AND INDICE = ?";
						$params = array($rs['NUMSEQ'], $rs['CODAPRESENTACAO'], $rs['INDICE']);
						executeSQL($conn, $query, $params);

						$retorno = array('class' => 'sucesso', 'mensagem' => 'Acesso autorizado.');

					} elseif ($rs['STATUSINGRESSO'] == 'U') {

						$retorno = array('class' => 'falha', 'mensagem' => 'Este ingresso já foi processado em ' .$rs['DATHRENTRADA']->format("d/m/Y H:i:s"). '.<br />Acesso não permitido.');

					} elseif ($rs['STATUSINGRESSO'] == 'E') {

						$retorno = array('class' => 'falha', 'mensagem' => 'Ingresso estornado.<br />Acesso não permitido.');

					} else {

						$retorno = array('class' => 'falha', 'mensagem' => 'Ingresso com status desconhecido.<br />Acesso não permitido.');
					}
				}

			} elseif ($_POST['sentido'] == 'saida') {

				while ($rs = fetchResult($result)) {
					if ($rs['STATUSINGRESSO'] == 'L') {
						$retorno = array('class' => 'falha', 'mensagem' => 'Entrada não Registrada.<br />Operação de Saída não realizada.');

					} elseif ($rs['STATUSINGRESSO'] == 'U') {

						if ($rs['DATHRSAIDA'] != NULL) {
							$retorno = array('class' => 'falha', 'mensagem' => 'Saída já foi registrada.<br />Operação em duplicidade não permitida.');
						} else {
							$query = "UPDATE TABCONTROLESEQVENDA SET
										DATHRSAIDA = GETDATE()
										WHERE NUMSEQ = ?
										AND CODAPRESENTACAO =?
										AND INDICE = ?";
							$params = array($rs['NUMSEQ'], $rs['CODAPRESENTACAO'], $rs['INDICE']);
							executeSQL($conn, $query, $params);

							$retorno = array('class' => 'sucesso', 'mensagem' => 'Saída autorizada.');
						}

					} elseif ($rs['STATUSINGRESSO'] == 'E') {

						$retorno = array('class' => 'falha', 'mensagem' => 'Ingresso estornado.<br />Acesso não permitido.');

					} else {

						$retorno = array('class' => 'falha', 'mensagem' => 'Ingresso com status desconhecido.<br />Acesso não permitido.');
					}
				}
			}
		} else {
			$retorno = array('class' => 'falha', 'mensagem' => 'Código do ingresso não existe.<br />Acesso não permitido.');
		}

		echo json_encode($retorno);
		
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

		$query = "SELECT tbAp.CodApresentacao
						,tbAp.DatApresentacao
		            from tabApresentacao tbAp (nolock)
		            inner join tabPeca tbPc (nolock)
		                        on        tbPc.CodPeca = tbAp.CodPeca
		            inner join ci_middleway..mw_acesso_concedido iac (nolock)
		                        on                    iac.id_base = ?
										and			  iac.id_usuario = ?
										and			  iac.CodPeca = tbAp.CodPeca
		            where               tbPc.CodPeca = ?
		            -- comentar para homologacao
					-- AND CONVERT(DATETIME, CONVERT(VARCHAR(8), TBAP.DATAPRESENTACAO, 112) + ' ' + TBAP.HORSESSAO) >= CONVERT(DATETIME, CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112) + ' 22:00')
					-- AND TBAP.DATAPRESENTACAO <= GETDATE()
					----------------------------
		            group by tbAp.CodApresentacao,tbAp.DatApresentacao
		            order by tbAp.DatApresentacao";
		$params = array($_GET['cboTeatro'], $_SESSION['admin'], $_GET['cboPeca']);

		$result = executeSQL($conn, $query, $params);

		$html = '<option value="">Selecione...</option>';
		
		while($rs = fetchResult($result)){
			$html .= '<option value="'. $rs["CodApresentacao"].'">'. $rs["DatApresentacao"]->format("d/m/Y") .'</option>';	
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
		            -- comentar para homologacao
		            -- AND CONVERT(DATETIME, CONVERT(VARCHAR(8), TBAP.DATAPRESENTACAO, 112) + ' ' + TBAP.HORSESSAO) >= CONVERT(DATETIME, CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112) + ' 22:00')
					----------------------------
		            AND TBAP.CodApresentacao = ?
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
	
	} elseif ($_GET['action'] == 'cboSetor' and $_GET['cboApresentacao'] != null) {

		print_r($_GET);

		$conn = getConnection($_GET['cboTeatro']);


		$query = 	"SELECT	
						codsetor
						,nomsetor 
					FROM	
						tabSetor se
					INNER JOIN 
						tabApresentacao ap ON ap.CodSala = se.CodSala 
					WHERE	
						ap.codApresentacao = ?
					ORDER BY nomsetor";


		$params = array($_GET['cboApresentacao']);
		$result = executeSQL($conn, $query, $params);

		$html = "<option value=''>Selecione...</option>
				 <option value='TODOS'>&lt; TODOS &gt;</option>";

		//$html = "<option value=''>Selecione...</option>
		//		 <option value='TODOS'>" . $_GET['cboApresentacao'].  "</option>";
		 

		while($rs = fetchResult($result)){
			$html .= '<option value="'. $rs["codsetor"] .'">' . utf8_encode2($rs["nomsetor"]) .'</option>';	
		}
		echo $html ;
		die();

	}

}
?>