<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

echo comboMunicipio("cboMunicipio", "", $_POST["idEstado"]);

?>
