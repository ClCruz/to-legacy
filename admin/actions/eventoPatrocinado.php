<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 24, true)) {

if ($_GET['action'] == 'add' or ($_GET['action'] == 'update' and $_POST['idCartaoPatrocinado'] == 'TODOS')) { /*------------ INSERT ------------*/
	
	if ($_POST['idCartaoPatrocinado'] == 'TODOS') {
		$query = "SELECT C.ID_CARTAO_PATROCINADO, E.ID_CARTAO_PATROCINADO TO_UPDATE
					FROM MW_CARTAO_PATROCINADO C
					INNER JOIN MW_PATROCINADOR P
						ON C.ID_PATROCINADOR = P.ID_PATROCINADOR
					LEFT JOIN MW_EVENTO_PATROCINADO E
						ON C.ID_CARTAO_PATROCINADO = E.ID_CARTAO_PATROCINADO
						AND E.ID_BASE = ?
						AND E.CODPECA = ?
					WHERE C.ID_PATROCINADOR = ?";
		$params = array($_POST['teatro'], $_POST['codpeca'], $_POST['idPatrocinador']);
		$result = executeSQL($mainConnection, $query, $params);
		
		while ($rs = fetchResult($result)) {
			if ($rs['TO_UPDATE']) {
				$query = 'UPDATE MW_EVENTO_PATROCINADO
							SET DT_INICIO = CONVERT(DATETIME, ?, 103),
							DT_FIM = CONVERT(DATETIME, ?, 103)
							WHERE ID_BASE = ? AND CODPECA = ? AND ID_CARTAO_PATROCINADO = ?';
				$params = array($_POST['dtInicio'], $_POST['dtFim'], $_POST['teatro'], $_POST['codpeca'], $rs['ID_CARTAO_PATROCINADO']);
				executeSQL($mainConnection, $query, $params);
			} else if ($_GET['action'] != 'update') {
				$query = 'INSERT INTO MW_EVENTO_PATROCINADO
							(ID_CARTAO_PATROCINADO, ID_BASE, CODPECA, DT_INICIO, DT_FIM)
							VALUES (?, ?, ?, CONVERT(DATETIME, ?, 103), CONVERT(DATETIME, ?, 103))';
				$params = array($rs['ID_CARTAO_PATROCINADO'], $_POST['teatro'], $_POST['codpeca'], $_POST['dtInicio'], $_POST['dtFim']);
				executeSQL($mainConnection, $query, $params);
			}
			
			$errors = sqlErrors();
			
			if (!empty($errors)) {
				break;
			} else {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Eventos Patrocinados');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);
			};
		}
	} else {
		$query = "INSERT INTO MW_EVENTO_PATROCINADO
						(ID_CARTAO_PATROCINADO, ID_BASE, CODPECA, DT_INICIO, DT_FIM)
						VALUES (?, ?, ?, CONVERT(DATETIME, ?, 103), CONVERT(DATETIME, ?, 103))";
		$params = array($_POST['idCartaoPatrocinado'], $_POST['teatro'], $_POST['codpeca'], $_POST['dtInicio'], $_POST['dtFim']);
		executeSQL($mainConnection, $query, $params);
	}
	
	$errors = sqlErrors();
	
	if (empty($errors)) {
	    $log = new Log($_SESSION['admin']);
	    $log->__set('funcionalidade', 'Eventos Patrocinados');
	    $log->__set('parametros', $params);
	    $log->__set('log', $query);
	    $log->save($mainConnection);

		$retorno = 'true?idCartaoPatrocinado='.$_POST['idCartaoPatrocinado'].'&teatro='.$_POST['teatro'].'&codpeca='.$_POST['codpeca'].'&idPatrocinador='.$_POST['idPatrocinador'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'update' and isset($_GET['idCartaoPatrocinado']) and isset($_GET['teatro']) and isset($_GET['codpeca'])) { /*------------ UPDATE ------------*/
	
	$query = "UPDATE MW_EVENTO_PATROCINADO
				 SET
				 ID_CARTAO_PATROCINADO = ?
				 ,ID_BASE = ?
				 ,CODPECA = ?
				 ,DT_INICIO = CONVERT(DATETIME, ?, 103)
				 ,DT_FIM = CONVERT(DATETIME, ?, 103)
				 WHERE ID_CARTAO_PATROCINADO = ? AND ID_BASE = ? AND CODPECA = ?";
	$params = array($_POST['idCartaoPatrocinado'], $_POST['teatro'], $_POST['codpeca'], $_POST['dtInicio'], $_POST['dtFim'],
					$_GET['idCartaoPatrocinado'], $_GET['teatro'], $_GET['codpeca']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Eventos Patrocinados');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

		$retorno = 'true?idCartaoPatrocinado='.$_POST['idCartaoPatrocinado'].'&teatro='.$_POST['teatro'].'&codpeca='.$_POST['codpeca'].'&idPatrocinador='.$_POST['idPatrocinador'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['idCartaoPatrocinado']) and isset($_GET['teatro']) and isset($_GET['codpeca'])) { /*------------ DELETE ------------*/
	
	$query = 'DELETE FROM MW_EVENTO_PATROCINADO WHERE ID_CARTAO_PATROCINADO = ? AND ID_BASE = ? AND CODPECA = ?';
	$params = array($_GET['idCartaoPatrocinado'], $_GET['teatro'], $_GET['codpeca']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Eventos Patrocinados');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);
        
		$retorno = 'true';
	} else {
		$retorno = sqlErrors();
	}
	
}

if (is_array($retorno)) {
	if ($retorno[0]['code'] == 547) {
		echo utf8_encode2('Não foi possível excluir!<br/><br/>Esse registro já está em uso.');
	} else {
		echo $retorno[0]['message'];
	}
} else {
	echo $retorno;
}

}
?>