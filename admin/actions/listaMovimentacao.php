<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 12, true)) {
	if ($_GET['action'] == 'reemail') {
		callapi_resendmail($_GET['pedido'], $_POST['emailInformado']);
		echo "ok";
	}  else if ($_GET['action'] == 'cboPeca') {

		$conn = getConnection($_GET['cboTeatro']);

		$query = "SELECT DISTINCT
        e.id_evento
        ,e.ds_evento
        FROM CI_MIDDLEWAY..mw_evento e
        INNER JOIN CI_MIDDLEWAY..mw_acesso_concedido ac ON ac.CodPeca=e.CodPeca
        WHERE e.id_base=?
        AND ac.id_usuario=?
        ORDER BY e.ds_evento, e.id_evento";
		$params = array($_GET['cboTeatro'], $_SESSION['admin']);
		$result = executeSQL($conn, $query, $params);

		$html = '<option value="">Selecione...</option>';

		while($rs = fetchResult($result)){
            $selected = "";
            if (strval($_GET["id_evento"]) == strval($rs["id_evento"])) {
                $selected = "selected";
            }
			$html .= '<option value="'. $rs["id_evento"] .'" '.$selected.'>'. utf8_encode2($rs["ds_evento"]) .'</option>';	
		}

		echo $html;
		die();

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