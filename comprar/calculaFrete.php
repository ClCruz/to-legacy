<?php

session_start();

if (isset($_GET['id']) and is_numeric($_GET['id']) and isset($_SESSION['user'])) {
    require_once('../settings/functions.php');

    $mainConnection = mainConnection();

    $query = 'SELECT F.VL_TAXA_FRETE
					FROM MW_TAXA_FRETE F
					INNER JOIN MW_REGIAO_GEOGRAFICA R ON R.ID_REGIAO_GEOGRAFICA = F.ID_REGIAO_GEOGRAFICA
					INNER JOIN MW_ESTADO E ON E.ID_REGIAO_GEOGRAFICA = R.ID_REGIAO_GEOGRAFICA ';
    if ($_GET['id'] != -1) {
	$query .= 'INNER JOIN MW_ENDERECO_CLIENTE EC ON EC.ID_ESTADO = E.ID_ESTADO
					WHERE EC.ID_CLIENTE = ? AND EC.ID_ENDERECO_CLIENTE = ?';
	$params = array($_SESSION['user'], $_GET['id']);
    } else {
	$query .= 'INNER JOIN MW_CLIENTE C ON C.ID_ESTADO = E.ID_ESTADO
					WHERE C.ID_CLIENTE = ?';
	$params = array($_SESSION['user']);
    }
    $query .= ' AND F.DT_INICIO_VIGENCIA <= GETDATE()
					ORDER BY F.DT_INICIO_VIGENCIA DESC';

    if ($rs = executeSQL($mainConnection, $query, $params, true)) {
	echo str_replace('.', ',', $rs[0]);
    }
} else if (isset($_GET['estado']) and is_numeric($_GET['estado'])) {
    require_once('../settings/functions.php');

    $mainConnection = mainConnection();

    $query = 'SELECT F.VL_TAXA_FRETE
					FROM MW_TAXA_FRETE F
					INNER JOIN MW_REGIAO_GEOGRAFICA R ON R.ID_REGIAO_GEOGRAFICA = F.ID_REGIAO_GEOGRAFICA
					INNER JOIN MW_ESTADO E ON E.ID_REGIAO_GEOGRAFICA = R.ID_REGIAO_GEOGRAFICA
					WHERE E.ID_ESTADO = ? AND F.DT_INICIO_VIGENCIA <= GETDATE()
					ORDER BY F.DT_INICIO_VIGENCIA DESC';
    $params = array($_GET['estado']);

    if ($rs = executeSQL($mainConnection, $query, $params, true)) {
	echo str_replace('.', ',', $rs[0]);
    }
} else if ((isset($_GET["action"]) && $_GET["action"] == "verificatempo")
	|| (isset($action) && $action == "verificatempo")) {
    require_once('../settings/functions.php');
    $mainConnection = mainConnection();
    if ($_GET["etapa"] == 2) {
		$query = 'SELECT QT_HORAS_LIMITE FROM MW_LIMITE_ENTREGA WHERE ID_ESTADO = ?';
		$params = array($_POST["idestado"]);
    } else if ($_GET["etapa"] == 4 || $etapa == 4) {
		$idestado = (isset($_POST["idestado"])) ? $_POST["idestado"] : $idestado;
		if ($idestado != -1) {
		    $query = 'SELECT LE.QT_HORAS_LIMITE
						  FROM MW_ENDERECO_CLIENTE EC
						  INNER JOIN MW_LIMITE_ENTREGA LE ON LE.ID_ESTADO = EC.ID_ESTADO
						  WHERE EC.ID_ENDERECO_CLIENTE = ?';
		    $params = array($idestado);
		} else {
		    $query = 'SELECT LE.QT_HORAS_LIMITE
						  FROM MW_CLIENTE C
						  INNER JOIN MW_LIMITE_ENTREGA LE ON LE.ID_ESTADO = C.ID_ESTADO
						  WHERE C.ID_CLIENTE = ?';
		    $params = array($_SESSION["user"]);
		}
    }

    if ($rs = executeSQL($mainConnection, $query, $params, true)) {
    	$errors = sqlErrors();
		if (empty($errors)) {
		    $diasLimite = ceil($rs[0] / 24);
		    $dataLimiteTemp = strtotime('+'.$diasLimite.' days', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		    
		    if ($_SESSION["dataEvento"] < $dataLimiteTemp
			    && basename($_SERVER['SCRIPT_FILENAME']) == 'etapa5.php') {
				header("Location: etapa4.php");
		    } else if ($_SESSION["dataEvento"] < $dataLimiteTemp
			    && basename($_SERVER['SCRIPT_FILENAME']) == 'etapa4.php') {
				$scriptTempoLimiteFrete = '<script type="text/javascript">
							$(function(){
								$.dialog({title:"Aviso...", text:\'Tempo não suficiente para entrega dos ingressos.<br>Favor alterar o tipo de forma de entrega.\', uiOptions:{width:500}});
							});
						</script>';
		    } else if (basename($_SERVER['SCRIPT_FILENAME']) !== 'etapa4.php'
				&& basename($_SERVER['SCRIPT_FILENAME']) !== 'etapa5.php') {
				if ($_SESSION["dataEvento"] >= $dataLimiteTemp)
				    echo "true";
				else {
				    $msg = array(
				    	"DT Atual" => $_SESSION["dataEvento"],
						"Dias Limite" => $diasLimite,
						"Data Limite" => $dataLimite,
						'text' => 'Tempo não suficiente para entrega dos ingressos.',
						'detail' => 'Favor alterar o estado ou o tipo de forma de entrega.'
					);
				    echo json_encode($msg);
				}
		    }
		} else {
		    print_r(sqlErrors());
		}
    }
}
?>