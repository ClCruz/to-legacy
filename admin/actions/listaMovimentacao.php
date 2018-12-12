<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 12, true)) {
	if ($_GET['action'] == 'reemail') {
		
		if ($_GET['emailAtual'] != $_POST['emailInformado']) {
			$query = 'SELECT 1 FROM MW_CLIENTE WHERE CD_EMAIL_LOGIN = ?';
			$params = array($_POST['emailInformado']);
			$result = executeSQL($mainConnection, $query, $params);

			if (hasRows($result)) {
				die("O e-mail informado já está cadastrado. Favor informar outro e-mail.");
			}
		}

		$successMail = sendSuccessMail($_GET['pedido']);

		if ($successMail === true) {
			$query = 'UPDATE MW_CLIENTE SET CD_EMAIL_LOGIN = ? WHERE CD_EMAIL_LOGIN = ?';
			$params = array($_POST['emailInformado'], $_GET['emailAtual']);
			executeSQL($mainConnection, $query, $params);
			
			echo "ok";
		} else {
			echo $successMail;
		}
		
	} else if ($_GET['action'] == 'load_evento_combo') {

	    $queryEvento = 'SELECT E.ID_EVENTO, E.DS_EVENTO FROM MW_EVENTO E WHERE IN_ATIVO = 1 ORDER BY DS_EVENTO ASC';
	    $resultEventos = executeSQL($mainConnection, $queryEvento, null);
	    
	    $options = '<option value="">Selecione um evento...</option>';
	    while ($rs = fetchResult($resultEventos)) {
	        $options .= '<option value="' . $rs['ID_EVENTO'] . '"' .
	                (($_GET["nm_evento"] == $rs['ID_EVENTO']) ? ' selected' : '' ) .
	                '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
	    }

	    echo $options;
	}

}