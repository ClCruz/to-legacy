<?php
	require_once('../settings/functions.php');
	$mainConnection = mainConnection();
	$result = executeSQL($mainConnection, "SELECT DS_NOME_BASE_SQL FROM CI_MIDDLEWAY..MW_BASE WHERE IN_ATIVO = 1 AND ID_BASE = ?", array($_POST["local"]));
	while($dadosBase = fetchResult($result)){
		$nomeBase = $dadosBase["DS_NOME_BASE_SQL"];
	}
	comboEventos($_POST["local"], $nomeBase, $_POST["idUsuario"]);
?>