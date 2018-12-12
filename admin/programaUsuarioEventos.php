<?php
require_once('actions/programaUsuarioEventos.php');
require_once('../settings/functions.php');

$mainConnection = mainConnection();

$arrayBase = explode("*", $_POST["local"]);
$idBase = $arrayBase[0];
$nomeBase = $arrayBase[1];

// Alterar programas 
if(isset($_GET["action"]) && $_GET["action"] == "cad"){
	if(isset($_GET["tipo"]) && $_GET["tipo"] == "todos")
		return cadastrarAcessoEvento($_POST["usuario"], $idBase, $nomeBase, $_POST["eventos"], $mainConnection);
	else if(isset($_GET["tipo"]) && $_GET["tipo"] == "geral")
		return cadastrarAcessoEvento($_POST["usuario"], $idBase, $nomeBase, "geral", $mainConnection);
	else
		return cadastrarAcessoEvento($_POST["usuario"], $idBase, $nomeBase, $_GET["idevento"], $mainConnection);
}
else if(isset($_GET["action"]) && $_GET["action"] == "del"){
	if(isset($_GET["tipo"]) && $_GET["tipo"] == "todos")
		return deletarAcessoEvento($_POST["usuario"], $idBase, $_POST["eventos"], $mainConnection);		
	else if(isset($_GET["tipo"]) && $_GET["tipo"] == "geral")
		return deletarAcessoEvento($_POST["usuario"], $idBase, "geral", $mainConnection);
	else
		return deletarAcessoEvento($_POST["usuario"], $idBase, $_GET["idevento"], $mainConnection);	
}
?>